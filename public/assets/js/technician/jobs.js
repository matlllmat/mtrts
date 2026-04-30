document.addEventListener('DOMContentLoaded', () => {
  const grid = document.getElementById('jobsGrid');
  if (!grid) return;

  // Module-native filtering: do NOT fetch or re-render.
  // We filter the server-rendered cards using `data-status`.
  const filterBtns = Array.from(document.querySelectorAll('[data-filter]'));
  const cards = () => Array.from(grid.querySelectorAll('[data-status]'));
  const emptyId = 'jobsEmptyState';

  let currentFilter = 'all';

  function setActive(btn) {
    filterBtns.forEach((b) => b.classList.toggle('is-active', b === btn));
  }

  function ensureEmptyState() {
    let el = document.getElementById(emptyId);
    if (el) return el;
    el = document.createElement('div');
    el.id = emptyId;
    el.className = 'col-span-full text-center py-12';
    el.innerHTML = `
      <div class="text-gray-400 text-6xl mb-4">📋</div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No work orders</h3>
      <p class="text-gray-600">Nothing matches this filter.</p>
    `;
    el.style.display = 'none';
    grid.appendChild(el);
    return el;
  }

  function applyFilter() {
    const list = cards();
    const want = currentFilter;
    let shown = 0;

    list.forEach((card) => {
      const status = card.getAttribute('data-status') || '';
      const ok = (want === 'all') ? true : status === want;
      card.style.display = ok ? '' : 'none';
      if (ok) shown += 1;
    });

    const empty = ensureEmptyState();
    empty.style.display = shown === 0 ? '' : 'none';
  }

  filterBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      setActive(btn);
      currentFilter = btn.getAttribute('data-filter') || 'all';
      applyFilter();
    });
  });

  // Initial state
  const initial = filterBtns.find((b) => b.classList.contains('is-active')) || filterBtns[0];
  if (initial) {
    currentFilter = initial.getAttribute('data-filter') || 'all';
    setActive(initial);
  }
  applyFilter();
});

