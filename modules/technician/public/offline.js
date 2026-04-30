/* Offline-first: local cache + action queue + sync */
(function () {
  const LS = {
    cacheWorkOrders: 'mrtsp.cache.workorders.v1',
    cacheWorkOrderDetail: (id) => `mrtsp.cache.wo.${id}.v1`,
    queue: 'mrtsp.queue.v1',
  };

  function nowMs() { return Date.now(); }
  function uid() { return `q_${nowMs()}_${Math.random().toString(16).slice(2)}`; }

  function loadQueue() {
    try { return JSON.parse(localStorage.getItem(LS.queue) || '[]'); } catch { return []; }
  }
  function saveQueue(items) {
    localStorage.setItem(LS.queue, JSON.stringify(items));
    window.dispatchEvent(new Event('mrtsp:queuechange'));
  }

  function queueAction(type, workOrderId, data = {}, meta = {}) {
    const items = loadQueue();
    items.push({ id: uid(), type, workOrderId, data, meta, ts: nowMs(), synced: false });
    saveQueue(items);
  }

  function markSynced(ids = []) {
    const items = loadQueue();
    const set = new Set(ids);
    const next = items.map((it) => (set.has(it.id) ? { ...it, synced: true } : it));
    saveQueue(next.filter((it) => !it.synced)); // keep queue small for prototype
  }

  function queueCount() { return loadQueue().length; }

  async function isReallyOnline() {
    if (!navigator.onLine) return false;
    try {
      const r = await fetch(window.MRTS.APP_BASE + 'modules/technician/api/ping.php', { cache: 'no-store' });
      return r.ok;
    } catch {
      return false;
    }
  }

  async function sha256Hex(str) {
    // Best-effort: if WebCrypto is unavailable, return empty string.
    try {
      const enc = new TextEncoder();
      const bytes = enc.encode(String(str));
      const digest = await crypto.subtle.digest('SHA-256', bytes);
      const arr = Array.from(new Uint8Array(digest));
      return arr.map((b) => b.toString(16).padStart(2, '0')).join('');
    } catch {
      return '';
    }
  }

  async function ensureAuditHashes(items) {
    // Spec: keep a local audit hash for offline entries until synced.
    // We compute it at sync time (syncNow is async) so queueAction stays synchronous.
    const userId = (window.MRTS && window.MRTS.USER_ID) ? String(window.MRTS.USER_ID) : '';
    for (const it of items) {
      it.meta = it.meta && typeof it.meta === 'object' ? it.meta : {};
      if (it.meta.auditHash) continue;
      const payload = userId + '|' + String(it.workOrderId) + '|' + String(it.type) + '|' + String(it.ts) + '|' + JSON.stringify(it.data || {});
      const h = await sha256Hex(payload);
      if (h) it.meta.auditHash = h;
    }
  }

  async function syncNow() {
    const items = loadQueue();
    if (!items.length) return { ok: true, results: [], conflicts: [] };
    await ensureAuditHashes(items);

    // Collect all items that have blobs
    const itemsWithBlobs = items.filter((it) => it.meta && it.meta.hasBlob);
    
    // Build FormData payload with blobs if needed
    let fetchOptions = { method: 'POST' };
    
    if (itemsWithBlobs.length > 0 && window.MRTS.idbStorage) {
      const formData = new FormData();
      
      // Process each item with its blob separately for evidence/config
      for (const item of items) {
        // Use correct property names - type instead of action, workOrderId instead of wo_id
        const actionType = item.type || item.action || '';
        const woId = item.workOrderId || item.wo_id || '';
        
        if (actionType === 'evidence_add' && item.meta && item.meta.blobId) {
          try {
            // Get blob from IndexedDB
            const blobRecord = await window.MRTS.idbStorage.getBlob(item.meta.blobId);
            if (blobRecord && blobRecord.blob) {
              // Append item data
              formData.append(`item_${item.id}_action`, actionType);
              formData.append(`item_${item.id}_wo_id`, woId);
              formData.append(`item_${item.id}_side`, item.data.side || '');
              formData.append(`item_${item.id}_kind`, item.data.kind || 'image');
              formData.append(`item_${item.id}_name`, item.data.name || blobRecord.fileName || '');
              // Append the actual blob file
              formData.append(`item_${item.id}_file`, blobRecord.blob, blobRecord.fileName);
            } else {
              console.warn('[v0] Blob record not found for evidence:', item.meta.blobId);
            }
          } catch (e) {
            console.error('[v0] Failed to attach evidence blob for sync:', e);
          }
        } else if (actionType === 'config_add' && item.meta && item.meta.blobId) {
          try {
            // Get blob from IndexedDB
            const blobRecord = await window.MRTS.idbStorage.getBlob(item.meta.blobId);
            if (blobRecord && blobRecord.blob) {
              // Append item data
              formData.append(`item_${item.id}_action`, actionType);
              formData.append(`item_${item.id}_wo_id`, woId);
              formData.append(`item_${item.id}_name`, item.data.name || blobRecord.fileName || '');
              // Append the actual blob file
              formData.append(`item_${item.id}_file`, blobRecord.blob, blobRecord.fileName);
            } else {
              console.warn('[v0] Blob record not found for config:', item.meta.blobId);
            }
          } catch (e) {
            console.error('[v0] Failed to attach config blob for sync:', e);
          }
        } else {
          // Non-blob actions, append as form fields
          formData.append(`item_${item.id}_action`, actionType);
          formData.append(`item_${item.id}_wo_id`, woId);
          Object.keys(item.data || {}).forEach((k) => {
            formData.append(`item_${item.id}_${k}`, item.data[k]);
          });
        }
      }
      
      fetchOptions.body = formData;
      // Don't set Content-Type header; fetch will set it with boundary
    } else {
      // No blobs, use JSON
      const payload = { items };
      fetchOptions.body = JSON.stringify(payload);
      fetchOptions.headers = { 'Content-Type': 'application/json' };
    }

    const data = await window.MRTS.api('/modules/technician/api/sync.php', fetchOptions);
    const results = data.results || [];
    
    // Process results and update draft state
    const okIds = [];
    const errorResults = [];
    
    for (const result of results) {
      if (result.ok) {
        okIds.push(result.id);
        // Update draft item with serverUrl if available
        if (result.serverUrl && typeof window.updateDraftItemAfterSync === 'function') {
          window.updateDraftItemAfterSync(result.id, result.action, result.serverUrl);
        }
        // Clean up blob from IndexedDB after successful sync
        const item = items.find((i) => i.id === result.id);
        if (item && item.meta && item.meta.blobId) {
          try {
            await window.MRTS.idbStorage.deleteBlob(item.meta.blobId);
          } catch (e) {
            console.warn('[v0] Failed to clean up blob after sync:', e);
          }
        }
      } else {
        errorResults.push(result);
        // Update draft item with error state if handler exists
        if (typeof window.updateDraftItemError === 'function') {
          window.updateDraftItemError(result.id, result.action, result.error || 'Sync failed');
        }
      }
    }
    
    markSynced(okIds);
    return { ok: true, results, conflicts: data.conflicts || [], errors: errorResults };
  }

  function cacheSet(key, value) {
    try { localStorage.setItem(key, JSON.stringify({ v: value, ts: nowMs() })); } catch {}
  }
  function cacheGet(key) {
    try {
      const raw = JSON.parse(localStorage.getItem(key) || 'null');
      return raw && raw.v ? raw.v : null;
    } catch { return null; }
  }

  function updateNetUI() {
    const dot = document.getElementById('offlineDot');
    const txt = document.getElementById('offlineText');
    if (!dot || !txt) return;
    isReallyOnline().then((ok) => {
      if (ok) {
        dot.style.background = 'var(--success)';
        txt.textContent = 'Online';
      } else {
        dot.style.background = 'var(--warn)';
        txt.textContent = 'Offline';
      }
    });
  }

  function wireGlobal() {
    const btn = document.getElementById('syncNowBtn');
    if (btn) {
      btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.textContent = 'Syncing…';
        try {
          const online = await isReallyOnline();
          if (!online) throw new Error('You are offline');
          const result = await syncNow();
          if (result.conflicts.length) {
            alert(`Sync completed with ${result.conflicts.length} conflict(s) (prototype simulation).`);
          } else {
            alert('Sync complete');
          }
        } catch (e) {
          alert(e.message || 'Sync failed');
        } finally {
          btn.disabled = false;
          btn.textContent = 'Sync';
        }
      });
    }

    window.addEventListener('online', updateNetUI);
    window.addEventListener('offline', updateNetUI);
    window.addEventListener('mrtsp:queuechange', () => {
      const el = document.getElementById('queueCount');
      if (el) el.textContent = String(queueCount());
    });
    // Background-ish sync attempt when we become online (best-effort)
    window.addEventListener('online', async () => {
      try {
        const ok = await isReallyOnline();
        if (!ok) return;
        await syncNow();
      } catch {}
    });
    updateNetUI();
    const el = document.getElementById('queueCount');
    if (el) el.textContent = String(queueCount());
  }

  // Register service worker (best-effort)
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(window.MRTS.APP_BASE + '/public/sw.js').catch(() => {});
    });
  }

  window.MRTS = window.MRTS || {};
  window.MRTS.offline = {
    LS,
    cacheSet,
    cacheGet,
    queueAction,
    queueCount,
    syncNow,
    isReallyOnline,
    wireGlobal,
  };

  document.addEventListener('DOMContentLoaded', wireGlobal);
})();

