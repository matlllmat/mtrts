<style>
/* ── Inventory module styles ──────────────────────────────── */

/* Badges & Pills */
.stock-pill{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:11.5px;font-weight:600;line-height:1.4;}
.stock-pill .bdot{width:6px;height:6px;border-radius:50%;margin-right:5px;flex-shrink:0;display:inline-block;}
.stock-ok  {background:#dcfce7;color:#166534;} .stock-ok  .bdot{background:#16a34a;}
.stock-low {background:#fef3c7;color:#b45309;} .stock-low .bdot{background:#d97706;}
.stock-out {background:#fee2e2;color:#b91c1c;} .stock-out .bdot{background:#dc2626;}

tr.row-low td{background:#fffbeb;}
tr.row-out td{background:#fef2f2;}

/* Stats Cards */
.inv-stat{background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:transform .15s, box-shadow .15s;}
.inv-stat:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.04);}
.stat-ico{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.inv-stat-lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;}
.inv-stat-val{font-size:24px;font-weight:800;color:#111827;line-height:1.1;font-family:'Inter', sans-serif;}

/* Form & Inputs */
.flbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;display:block;margin-bottom:4px;}
.fin{width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px;font-family:inherit;color:#1f2937;outline:none;transition:all .15s;background:#fff;}
.fin:focus{border-color:#1a5c2a;box-shadow:0 0 0 3px rgba(26,92,42,.1);background:#fff;}
.fin.fin-err{border-color:#dc2626;}
.fin[readonly]{background:#f9fafb;color:#6b7280;cursor:default;border-color:#f3f4f6;}

.fsel{width:100%;padding:9px 32px 9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px;font-family:inherit;color:#1f2937;outline:none;
      background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right 10px center;
      appearance:none;cursor:pointer;transition:all .15s;}
.fsel:focus{border-color:#1a5c2a;box-shadow:0 0 0 3px rgba(26,92,42,.1);}

.ferr-msg{font-size:11px;color:#dc2626;display:flex;align-items:center;gap:4px;margin-top:4px;font-weight:500;}
.fhint{font-size:11px;color:#9ca3af;margin-top:4px;line-height:1.4;}

/* Chips */
.chip{padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;
      border:1.5px solid #e5e7eb;background:#fff;color:#4b5563;transition:all .15s;white-space:nowrap;display:inline-flex;align-items:center;gap:5px;}
.chip:hover{border-color:#1a5c2a;color:#1a5c2a;background:#f0fdf4;}
.chip.chip-on{background:#1a5c2a;color:#fff;border-color:#1a5c2a;box-shadow:0 2px 6px rgba(26,92,42,0.2);}

/* Tables */
.wo-tag{font-family:ui-monospace,'Cascadia Code',monospace;font-size:12px;color:#15803d;font-weight:600;background:#f0fdf4;padding:2px 6px;border-radius:4px;}
tbody tr:hover td{background:#f9fafb;}

/* Pagination */
.pg-wrap{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid #f3f4f6;font-size:13px;color:#6b7280;}
.pg-btns{display:flex;gap:4px;}
.pg-btn{min-width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:6px;
        border:1px solid #e5e7eb;background:#fff;cursor:pointer;font-size:13px;font-weight:600;color:#4b5563;transition:all .12s;}
.pg-btn:hover{background:#f9fafb;border-color:#d1d5db;}
.pg-btn.pg-on{background:#1a5c2a;color:#fff;border-color:#1a5c2a;}

/* Audit/History */
.audit-row{padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:13px;}
.audit-row:last-child{border-bottom:none;}
.audit-delta-pos{color:#15803d;font-weight:700;}
.audit-delta-neg{color:#dc2626;font-weight:700;}

/* Alerts */
.alert-card{background:#fff;border:1px solid #f3f4f6;border-left:4px solid #d97706;border-radius:12px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px;box-shadow:0 1px 2px rgba(0,0,0,0.03);}
.alert-card.alert-out{border-left-color:#dc2626;}

/* Shared Layout helpers */
.card-premium{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);border:1px solid #f3f4f6;}
.s-hdr{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;padding-bottom:8px;border-bottom:1px solid #f3f4f6;margin-bottom:16px;}
</style>

