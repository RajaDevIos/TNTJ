'use strict';
const $$ = (s, el = document) => [...el.querySelectorAll(s)];

// ---- Header dropdowns
const dds = $$('.dd');
const closeAll = except => dds.forEach(d => {
  if (d === except) return;
  d.classList.remove('open');
  d.querySelector('button').setAttribute('aria-expanded', 'false');
});
dds.forEach(dd => dd.querySelector('button').addEventListener('click', e => {
  const open = !dd.classList.contains('open');
  closeAll(dd);
  dd.classList.toggle('open', open);
  e.currentTarget.setAttribute('aria-expanded', String(open));
}));
document.addEventListener('click', e => { if (!e.target.closest('.dd')) closeAll(); });

// ---- Search bar
const sbtn = document.querySelector('[data-search-toggle]');
const sbar = document.getElementById('searchbar');
sbtn?.addEventListener('click', () => {
  sbar.hidden = !sbar.hidden;
  sbtn.setAttribute('aria-expanded', String(!sbar.hidden));
  if (!sbar.hidden) sbar.querySelector('input').focus();
});

// ---- Phone menu sheet
const sheet = document.getElementById('sheet');
const openBtn = document.querySelector('[data-sheet-open]');
const setSheet = open => {
  sheet.hidden = !open;
  document.body.classList.toggle('sheet-open', open);
  openBtn?.setAttribute('aria-expanded', String(open));
  (open ? sheet.querySelector('[data-sheet-close]') : openBtn)?.focus();
};
openBtn?.addEventListener('click', () => setSheet(true));
sheet?.querySelector('[data-sheet-close]').addEventListener('click', () => setSheet(false));
document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  closeAll();
  if (sheet && !sheet.hidden) setSheet(false);
  if (sbar && !sbar.hidden) { sbar.hidden = true; sbtn.setAttribute('aria-expanded', 'false'); sbtn.focus(); }
});

// ---- Instant filtering: <input data-filter=".selector"> matches each item's data-text
const norm = s => (s || '').toLowerCase().replace(/[\s.\-]+/g, '');
$$('[data-filter]').forEach(input => {
  const items = $$(input.dataset.filter);
  const none = document.querySelector('.no-results');
  input.addEventListener('input', () => {
    const q = norm(input.value);
    let shown = 0;
    items.forEach(el => {
      const hit = !q || norm(el.dataset.text || el.textContent).includes(q);
      el.hidden = !hit;
      if (hit) { shown++; if (q && el.tagName === 'DETAILS') el.open = true; }
    });
    if (none) none.hidden = shown > 0;
  });
});

// ---- Branches: on wide screens the directory reads as one open document with an index;
//      on phones each branch stays collapsed so the list is scannable.
const wide = window.matchMedia('(min-width: 901px)');
const syncBranches = () => $$('details.b-sec').forEach(d => (d.open = wide.matches));
if (document.querySelector('details.b-sec')) { syncBranches(); wide.addEventListener('change', syncBranches); }
// Jumping from the index or a #hash opens that branch
const openHash = () => { const d = location.hash && document.querySelector(location.hash); if (d && d.tagName === 'DETAILS') d.open = true; };
window.addEventListener('hashchange', openHash); openHash();

// ---- Home: "who do I call?"
const fq = document.getElementById('finderQ');
if (fq) {
  const data = JSON.parse(document.getElementById('finderData').textContent);
  const list = document.getElementById('finderList');
  const hint = document.getElementById('finderHint');
  const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const digits = p => String(p || '').replace(/\D/g, '');
  const wa = p => { const d = digits(p); return d.length === 10 ? '91' + d : d; };
  const fmt = p => { const d = digits(p); return d.length === 10 ? d.slice(0, 5) + ' ' + d.slice(5) : p; };
  const use = id => `<svg class="i" aria-hidden="true"><use href="#i-${id}"/></svg>`;
  fq.addEventListener('input', () => {
    const q = norm(fq.value);
    hint.hidden = !!q;
    if (!q) { list.innerHTML = ''; return; }
    const hits = data.filter(r => norm(r.n + r.b + r.r + r.p + (r.s || '')).includes(q)).slice(0, 20);
    list.innerHTML = hits.length
      ? hits.map(r => `<li><span><b>${esc(r.n)}</b><small>${esc(r.r)}, ${esc(r.b)}</small></span>
          <span class="num"><a href="tel:${digits(r.p)}">${esc(fmt(r.p))}</a></span>
          <span class="cbtns"><a class="cbtn cbtn-call" href="tel:${digits(r.p)}" aria-label="${esc((window.T?.call || '%s').replace('%s', fmt(r.p)))}">${use('phone')}</a><a class="cbtn cbtn-wa" href="https://wa.me/${wa(r.p)}" target="_blank" rel="noopener" aria-label="${esc(fmt(r.p))} WhatsApp">${use('whatsapp')}</a></span></li>`).join('')
      : `<li class="finder-empty">${esc((window.T?.none || '%s').replace('%s', fq.value))}</li>`;
  });
}
