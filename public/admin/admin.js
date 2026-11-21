'use strict';
/* TNTJ admin panel — vanilla JS SPA. Every request carries `Authorization: Bearer <token>`. */
const API = '/api/v1';
const $ = (s, el = document) => el.querySelector(s);
const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const state = { token: null, user: null, meta: null, branches: [], pending: {} };

// ------------------------------------------------------------------ token storage
function loadToken() { try { return sessionStorage.getItem('tntj_token') || localStorage.getItem('tntj_token'); } catch { return null; } }
function saveToken(t, remember) {
  try { (remember ? localStorage : sessionStorage).setItem('tntj_token', t); } catch { /* storage blocked: token lives in memory only */ }
}
function clearToken() { try { sessionStorage.removeItem('tntj_token'); localStorage.removeItem('tntj_token'); } catch {} }

// ------------------------------------------------------------------ API client
async function api(path, opts = {}) {
  const headers = { Accept: 'application/json', ...(opts.headers || {}) };
  if (state.token) headers.Authorization = 'Bearer ' + state.token;
  let body = opts.body;
  if (body && !(body instanceof FormData)) { headers['Content-Type'] = 'application/json'; body = JSON.stringify(body); }
  const res = await fetch(API + path, { method: opts.method || 'GET', headers, body });
  let data = null;
  try { data = await res.json(); } catch { data = { error: 'சர்வர் பதில் தவறு (' + res.status + ')' }; }
  if (res.status === 401 && path !== '/auth/login') { logout(true); throw new Error(data.error || 'மீண்டும் உள்நுழையவும்'); }
  if (!res.ok) { const e = new Error(data.error || 'பிழை'); e.errors = data.errors || {}; e.status = res.status; throw e; }
  return data;
}

function toast(msg, bad = false) {
  const t = $('#toast'); t.textContent = msg; t.className = 'toast' + (bad ? ' bad' : ''); t.hidden = false;
  clearTimeout(toast._t); toast._t = setTimeout(() => (t.hidden = true), 3200);
}

// ------------------------------------------------------------------ auth
$('#loginForm').addEventListener('submit', async e => {
  e.preventDefault();
  const f = e.target; $('#loginErr').textContent = '';
  f.querySelector('button').disabled = true;
  try {
    const r = await api('/auth/login', { method: 'POST', body: { email: f.email.value, password: f.password.value } });
    state.token = r.access_token; saveToken(r.access_token, f.remember.checked);
    f.reset(); await boot();
  } catch (err) { $('#loginErr').textContent = err.message; }
  finally { f.querySelector('button').disabled = false; }
});

async function logout(silent) {
  if (!silent && state.token) { try { await api('/auth/logout', { method: 'POST' }); } catch {} }
  state.token = null; clearToken();
  $('#app').hidden = true; $('#login').hidden = false;
}
$('#logoutBtn').addEventListener('click', () => logout(false));
$('#menuBtn').addEventListener('click', () => $('#sidebar').classList.toggle('open'));
$('#sidebar').addEventListener('click', e => { if (e.target.closest('a')) $('#sidebar').classList.remove('open'); });

async function boot() {
  state.token = state.token || loadToken();
  if (!state.token) { $('#login').hidden = false; return; }
  try {
    const [me, meta] = await Promise.all([api('/auth/me'), api('/meta')]);
    if (!me.abilities.includes('write')) { toast('இந்த token-க்கு நிர்வாக அனுமதி இல்லை', true); return logout(true); }
    state.user = me.user; state.meta = meta.resources; state.branches = meta.branches;
  } catch { return; }
  $('#login').hidden = true; $('#app').hidden = false;
  $('#who').textContent = state.user.name + ' (' + state.user.role + ')';
  await refreshPending();
  route();
}

async function refreshPending() {
  try { const d = await api('/dashboard'); state.pending = d.pending; state.dash = d; } catch { state.pending = {}; }
  renderNav();
}

function renderNav() {
  const groups = {};
  for (const [k, r] of Object.entries(state.meta)) (groups[r.group] ||= []).push([k, r]);
  const cur = location.hash.slice(2).split('?')[0];
  let h = `<a href="#/" class="nav ${cur === '' ? 'on' : ''}">📈 டாஷ்போர்டு</a>`;
  for (const [g, items] of Object.entries(groups)) {
    h += `<h4>${esc(g)}</h4>`;
    for (const [k, r] of items) {
      const p = state.pending[k];
      h += `<a href="#/${k}" class="nav ${cur === k ? 'on' : ''}"><span>${r.icon}</span>${esc(r.label)}${p ? `<span class="badge">${p}</span>` : ''}</a>`;
    }
  }
  h += `<h4>அமைப்புகள்</h4><a href="#/settings" class="nav ${cur === 'settings' ? 'on' : ''}">⚙️ தள அமைப்புகள்</a>`;
  if (state.user.role === 'superadmin') {
    h += `<a href="#/users" class="nav ${cur === 'users' ? 'on' : ''}">🔐 நிர்வாகிகள் (Users)</a>`;
    h += `<a href="#/tokens" class="nav ${cur === 'tokens' ? 'on' : ''}">🔑 API Tokens</a>`;
  }
  $('#sideNav').innerHTML = h;
}

// ------------------------------------------------------------------ router
window.addEventListener('hashchange', route);
function route() {
  if (!state.meta) return;
  const [path, qs] = location.hash.slice(2).split('?');
  const params = new URLSearchParams(qs || '');
  renderNav();
  if (!path) return viewDashboard();
  if (path === 'settings') return viewSettings();
  if (path === 'users') return viewUsers();
  if (path === 'tokens') return viewTokens();
  if (state.meta[path]) return viewList(path, params);
  $('#view').innerHTML = '<p class="empty">பக்கம் இல்லை</p>';
}
const setTitle = t => { $('#pageTitle').textContent = t; document.title = t + ' — TNTJ நிர்வாகம்'; };

// ------------------------------------------------------------------ dashboard
async function viewDashboard() {
  setTitle('டாஷ்போர்டு');
  await refreshPending();
  const d = state.dash || { counts: {}, pending: {}, upcoming_events: [], latest_news: [] };
  let cards = '';
  for (const [k, r] of Object.entries(state.meta)) {
    const p = d.pending[k];
    cards += `<a class="stat" href="#/${k}${p ? '?pending=1' : ''}"><span>${r.icon} ${esc(r.label)}</span><b>${d.counts[k] ?? 0}</b>${p ? `<span class="pend">${p} அங்கீகாரம் நிலுவையில்</span>` : ''}</a>`;
  }
  $('#view').innerHTML = `
    <div class="cards">${cards}</div>
    <div class="two">
      <div class="panel"><h3>🗓️ வரவிருக்கும் நிகழ்வுகள்</h3><ul class="list-plain">${d.upcoming_events.map(e => `<li><span>${esc(e.title)}</span><span class="muted">${esc(fmtDate(e.starts_at, true))}</span></li>`).join('') || '<li class="muted">இல்லை — <a href="#/events">புதிய நிகழ்வைச் சேர்</a></li>'}</ul></div>
      <div class="panel"><h3>📰 அண்மைப் பதிவுகள்</h3><ul class="list-plain">${d.latest_news.map(n => `<li><span class="trunc">${esc(n.title)}</span><span class="muted">${esc(fmtDate(n.published_at))}</span></li>`).join('')}</ul></div>
    </div>`;
}

// ------------------------------------------------------------------ generic list
const fieldOf = (res, name) => res.fields.find(f => f.name === name) || { name, type: 'text', label: name };
const branchName = id => (state.branches.find(b => b.id == id) || {}).name || '';
function fmtDate(v, time) {
  if (!v) return '';
  const d = new Date(v.replace(' ', 'T'));
  if (isNaN(d)) return v;
  return d.toLocaleDateString('ta-IN', { day: 'numeric', month: 'short', year: 'numeric' }) + (time ? ' ' + d.toLocaleTimeString('en-IN', { hour: 'numeric', minute: '2-digit' }) : '');
}
function cell(res, f, row) {
  const v = row[f.name];
  switch (f.type) {
    case 'bool': return v ? '<span class="yes">✔</span>' : '<span class="no">✖</span>';
    case 'branch': return esc(branchName(v));
    case 'image': return v ? `<img class="thumb" src="${esc(v)}" alt="">` : '';
    case 'date': return esc(fmtDate(v));
    case 'datetime': return esc(fmtDate(v, true));
    case 'url': case 'file': return v ? `<a href="${esc(v)}" target="_blank" rel="noopener">திற ↗</a>` : '';
    default: return `<div class="trunc">${esc(v)}</div>`;
  }
}

async function viewList(key, params) {
  const res = state.meta[key];
  setTitle(res.icon + ' ' + res.label);
  const page = +params.get('page') || 1;
  const q = params.get('q') || '';
  const query = new URLSearchParams({ page, per_page: 25, q });
  if (params.get('pending')) query.set('pending', '1');
  if (params.get('branch_id')) query.set('branch_id', params.get('branch_id'));

  const branchFilter = res.filter === 'branch_id'
    ? `<select id="fBranch"><option value="">அனைத்து கிளைகள்</option>${state.branches.map(b => `<option value="${b.id}" ${params.get('branch_id') == b.id ? 'selected' : ''}>${esc(b.name)}</option>`).join('')}</select>` : '';
  $('#view').innerHTML = `
    <div class="toolbar">
      <input type="search" id="fQ" placeholder="🔍 தேடுக" value="${esc(q)}">
      ${branchFilter}
      ${res.has_pending ? `<label class="check"><input type="checkbox" id="fPending" ${params.get('pending') ? 'checked' : ''}> நிலுவையில் மட்டும்</label>` : ''}
      <span class="spacer"></span>
      ${res.no_create ? '' : `<button class="btn" id="addBtn">＋ புதியது சேர்</button>`}
    </div>
    <div class="tbl-wrap" id="tbl"><p class="empty">ஏற்றுகிறது…</p></div>
    <div class="pager" id="pager"></div>`;

  const go = changes => {
    const p = new URLSearchParams(params);
    for (const [k, v] of Object.entries(changes)) v ? p.set(k, v) : p.delete(k);
    location.hash = '#/' + key + (p.toString() ? '?' + p : '');
  };
  let t; $('#fQ').addEventListener('input', e => { clearTimeout(t); t = setTimeout(() => go({ q: e.target.value, page: '' }), 350); });
  $('#fQ').focus(); $('#fQ').setSelectionRange(q.length, q.length);
  $('#fBranch')?.addEventListener('change', e => go({ branch_id: e.target.value, page: '' }));
  $('#fPending')?.addEventListener('change', e => go({ pending: e.target.checked ? '1' : '', page: '' }));
  $('#addBtn')?.addEventListener('click', () => openForm(key, null, () => route()));

  let r;
  try { r = await api(`/${key}?${query}`); } catch (e) { $('#tbl').innerHTML = `<p class="empty">${esc(e.message)}</p>`; return; }
  if (!r.data.length) { $('#tbl').innerHTML = '<p class="empty">பதிவுகள் இல்லை</p>'; return; }
  const cols = res.list.map(n => fieldOf(res, n));
  const approveCol = res.fields.some(f => f.name === 'approved');
  $('#tbl').innerHTML = `<table><thead><tr>${cols.map(c => `<th>${esc(c.label)}</th>`).join('')}<th></th></tr></thead><tbody>
    ${r.data.map(row => `<tr data-id="${row.id}">${cols.map(c => `<td>${cell(res, c, row)}</td>`).join('')}
      <td class="actions">
        ${approveCol && !row.approved ? `<button class="btn btn-sm" data-act="approve">✔ அங்கீகரி</button>` : ''}
        ${key === 'contact-messages' && !row.is_read ? `<button class="btn btn-sm" data-act="read">✔ படித்தது</button>` : ''}
        <button class="btn btn-ghost btn-sm" data-act="edit">✎ திருத்து</button>
        <button class="btn btn-ghost btn-sm" data-act="del" aria-label="நீக்கு">🗑</button>
      </td></tr>`).join('')}</tbody></table>`;
  $('#pager').innerHTML = `<span class="muted">மொத்தம் ${r.meta.total}</span>
    ${page > 1 ? `<button class="btn btn-ghost btn-sm" data-page="${page - 1}">← முந்தைய</button>` : ''}
    <span>${page} / ${r.meta.pages}</span>
    ${page < r.meta.pages ? `<button class="btn btn-ghost btn-sm" data-page="${page + 1}">அடுத்த →</button>` : ''}`;
  $('#pager').onclick = e => { const b = e.target.closest('[data-page]'); if (b) go({ page: b.dataset.page }); };
  $('#tbl').onclick = async e => {
    const b = e.target.closest('[data-act]'); if (!b) return;
    const id = b.closest('tr').dataset.id;
    try {
      if (b.dataset.act === 'edit') {
        const full = await api(`/${key}/${id}`);
        openForm(key, full.data, () => route());
      } else if (b.dataset.act === 'approve') {
        await api(`/${key}/${id}`, { method: 'PATCH', body: { approved: true } }); toast('அங்கீகரிக்கப்பட்டது'); await refreshPending(); route();
      } else if (b.dataset.act === 'read') {
        await api(`/${key}/${id}`, { method: 'PATCH', body: { is_read: true } }); await refreshPending(); route();
      } else if (b.dataset.act === 'del') {
        if (!confirm('இந்தப் பதிவை நிரந்தரமாக நீக்கவா? இதைத் திரும்பப் பெற முடியாது.')) return;
        await api(`/${key}/${id}`, { method: 'DELETE' }); toast('நீக்கப்பட்டது'); await refreshPending(); route();
      }
    } catch (err) { toast(err.message, true); }
  };
}

// ------------------------------------------------------------------ form builder
function inputFor(f, v) {
  const id = 'f_' + f.name, req = f.required ? 'required' : '';
  const val = v ?? f.default ?? '';
  const wide = ['textarea', 'html', 'image', 'file'].includes(f.type) ? 'full' : '';
  const lab = `${esc(f.label)}${f.required ? ' *' : ''}${f.help ? ` <span class="help">${esc(f.help)}</span>` : ''}`;
  let control;
  switch (f.type) {
    case 'textarea': control = `<textarea id="${id}" name="${f.name}" rows="4" ${req}>${esc(val)}</textarea>`; break;
    case 'html':
      return `<div class="full"><label for="${id}">${lab}</label>
        <div class="rte-bar" data-for="${id}">
          <button type="button" data-cmd="bold"><b>B</b></button><button type="button" data-cmd="italic"><i>I</i></button>
          <button type="button" data-cmd="formatBlock" data-arg="h3">H</button><button type="button" data-cmd="insertUnorderedList">• பட்டியல்</button>
          <button type="button" data-cmd="createLink">🔗</button><button type="button" data-cmd="image">🖼 படம்</button>
          <button type="button" data-cmd="source">&lt;/&gt; HTML</button>
        </div>
        <div class="rte" id="${id}" contenteditable="true" data-name="${f.name}">${val}</div>
        <div class="field-err" data-err="${f.name}"></div></div>`;
    case 'select': control = `<select id="${id}" name="${f.name}" ${req}><option value="">—</option>${f.options.map(o => `<option ${o === val ? 'selected' : ''}>${esc(o)}</option>`).join('')}</select>`; break;
    case 'branch': control = `<select id="${id}" name="${f.name}" ${req}><option value="">—</option>${state.branches.map(b => `<option value="${b.id}" ${b.id == val ? 'selected' : ''}>${esc(b.name)}</option>`).join('')}</select>`; break;
    case 'bool': return `<label class="check"><input type="checkbox" name="${f.name}" ${val && val !== '0' ? 'checked' : ''}> ${esc(f.label)}</label>`;
    case 'number': control = `<input id="${id}" type="number" name="${f.name}" value="${esc(val)}" ${req}>`; break;
    case 'date': control = `<input id="${id}" type="date" name="${f.name}" value="${esc(String(val).slice(0, 10))}" ${req}>`; break;
    case 'datetime': control = `<input id="${id}" type="datetime-local" name="${f.name}" value="${esc(String(val).replace(' ', 'T').slice(0, 16))}" ${req}>`; break;
    case 'phone': control = `<input id="${id}" type="tel" name="${f.name}" value="${esc(val)}" ${req}>`; break;
    case 'url': control = `<input id="${id}" type="url" name="${f.name}" value="${esc(val)}" placeholder="https://" ${req}>`; break;
    case 'image': case 'file':
      control = `<div class="upload-row"><input id="${id}" type="text" name="${f.name}" value="${esc(val)}" placeholder="https://… அல்லது பதிவேற்றவும்" ${req}>
        <label class="btn btn-ghost btn-sm" style="flex-direction:row">⬆ பதிவேற்று<input type="file" data-upload="${id}" hidden ${f.type === 'image' ? 'accept="image/*"' : 'accept=".pdf,.doc,.docx,.xls,.xlsx,image/*"'}></label></div>
        ${f.type === 'image' ? `<img class="preview" data-preview="${id}" src="${esc(val)}" ${val ? '' : 'hidden'} alt="">` : ''}`; break;
    default:
      control = `<input id="${id}" type="text" name="${f.name}" value="${esc(val)}" ${req} ${f.suggest ? `list="dl_${f.name}"` : ''}>`
        + (f.suggest ? `<datalist id="dl_${f.name}">${f.suggest.map(s => `<option value="${esc(s)}">`).join('')}</datalist>` : '');
  }
  return `<div class="${wide}"><label for="${id}">${lab}</label>${control}<div class="field-err" data-err="${f.name}"></div></div>`;
}

async function uploadFile(file) {
  const fd = new FormData(); fd.append('file', file);
  return (await api('/uploads', { method: 'POST', body: fd })).url;
}

function wireForm(body) {
  body.querySelectorAll('[data-upload]').forEach(inp => inp.addEventListener('change', async () => {
    const file = inp.files[0]; if (!file) return;
    const target = body.querySelector('#' + inp.dataset.upload);
    target.value = 'பதிவேற்றுகிறது…';
    try {
      target.value = await uploadFile(file);
      const pv = body.querySelector(`[data-preview="${inp.dataset.upload}"]`); if (pv) { pv.src = target.value; pv.hidden = false; }
      toast('பதிவேற்றப்பட்டது');
    } catch (e) { target.value = ''; toast(e.message, true); }
  }));
  body.querySelectorAll('[data-preview]').forEach(pv => {
    const inp = body.querySelector('#' + pv.dataset.preview);
    inp.addEventListener('change', () => { pv.src = inp.value; pv.hidden = !inp.value; });
  });
  body.querySelectorAll('.rte-bar').forEach(bar => bar.addEventListener('click', async e => {
    const b = e.target.closest('button'); if (!b) return;
    const ed = body.querySelector('#' + bar.dataset.for);
    const cmd = b.dataset.cmd;
    if (cmd === 'source') {
      const src = ed.nextElementSibling?.classList.contains('rte-src') ? ed.nextElementSibling : null;
      if (src) { ed.innerHTML = src.value; src.remove(); ed.hidden = false; }
      else { const ta = document.createElement('textarea'); ta.className = 'rte-src'; ta.value = ed.innerHTML; ed.after(ta); ed.hidden = true; }
      return;
    }
    ed.focus();
    if (cmd === 'createLink') { const u = prompt('இணைப்பு (https://…)'); if (u && /^https?:\/\//.test(u)) document.execCommand('createLink', false, u); return; }
    if (cmd === 'image') {
      const inp = document.createElement('input'); inp.type = 'file'; inp.accept = 'image/*';
      inp.onchange = async () => { try { const u = await uploadFile(inp.files[0]); ed.focus(); document.execCommand('insertImage', false, u); } catch (err) { toast(err.message, true); } };
      inp.click(); return;
    }
    document.execCommand(cmd, false, b.dataset.arg || null);
  }));
}

function collect(body, fields) {
  const out = {};
  for (const f of fields) {
    if (f.type === 'html') {
      const ed = body.querySelector(`[data-name="${f.name}"]`);
      const src = ed.nextElementSibling?.classList.contains('rte-src') ? ed.nextElementSibling : null;
      out[f.name] = src ? src.value : ed.innerHTML;
    } else if (f.type === 'bool') out[f.name] = body.querySelector(`[name="${f.name}"]`).checked;
    else out[f.name] = body.querySelector(`[name="${f.name}"]`).value;
  }
  return out;
}

/** Generic modal: fields[], initial values, onSubmit(values) -> Promise */
function modalForm(title, fields, values, onSubmit) {
  const dlg = $('#modal'), body = $('#modalBody');
  $('#modalTitle').textContent = title; $('#modalErr').textContent = '';
  body.innerHTML = fields.map(f => inputFor(f, values ? values[f.name] : undefined)).join('');
  wireForm(body);
  const form = $('#modalForm');
  form.onsubmit = async e => {
    if (e.submitter?.value !== 'save') return;
    e.preventDefault();
    body.querySelectorAll('[data-err]').forEach(x => (x.textContent = ''));
    $('#modalSave').disabled = true;
    try { await onSubmit(collect(body, fields)); dlg.close(); }
    catch (err) {
      $('#modalErr').textContent = err.message;
      for (const [k, m] of Object.entries(err.errors || {})) { const x = body.querySelector(`[data-err="${k}"]`); if (x) x.textContent = m; }
    } finally { $('#modalSave').disabled = false; }
  };
  dlg.showModal();
}

function openForm(key, row, done) {
  const res = state.meta[key];
  modalForm((row ? '✎ திருத்து — ' : '＋ புதியது — ') + res.label, res.fields, row, async vals => {
    if (row) await api(`/${key}/${row.id}`, { method: 'PUT', body: vals });
    else await api(`/${key}`, { method: 'POST', body: vals });
    toast('சேமிக்கப்பட்டது'); await refreshPending(); done();
  });
}

// ------------------------------------------------------------------ settings
async function viewSettings() {
  setTitle('⚙️ தள அமைப்புகள்');
  const r = await api('/settings');
  const fields = r.data.map(s => ({ name: s.key, type: s.key === 'ticker' || s.key === 'address' ? 'textarea' : 'text', label: s.label || s.key }));
  $('#view').innerHTML = `<form class="panel" id="setForm"><div class="modal-body" style="padding:0">${fields.map(f => inputFor(f, r.data.find(s => s.key === f.name).value)).join('')}</div>
    <p style="margin-top:1rem"><button class="btn">சேமி</button></p></form>`;
  $('#setForm').onsubmit = async e => {
    e.preventDefault();
    try { await api('/settings', { method: 'PUT', body: collect($('#setForm'), fields) }); toast('அமைப்புகள் சேமிக்கப்பட்டன'); }
    catch (err) { toast(err.message, true); }
  };
}

// ------------------------------------------------------------------ users
async function viewUsers() {
  setTitle('🔐 நிர்வாகிகள் (Users)');
  const r = await api('/users');
  const fields = [
    { name: 'name', type: 'text', label: 'பெயர்', required: true },
    { name: 'email', type: 'text', label: 'மின்னஞ்சல்', required: true },
    { name: 'role', type: 'select', label: 'பங்கு', options: ['editor', 'superadmin'], default: 'editor' },
    { name: 'password', type: 'text', label: 'கடவுச்சொல்', help: 'குறைந்தது 8 எழுத்துகள்; திருத்தும் போது காலியாக விட்டால் மாறாது' },
    { name: 'active', type: 'bool', label: 'செயலில்', default: 1 },
  ];
  $('#view').innerHTML = `<div class="toolbar"><span class="spacer"></span><button class="btn" id="addU">＋ புதிய நிர்வாகி</button></div>
    <div class="tbl-wrap"><table><thead><tr><th>பெயர்</th><th>மின்னஞ்சல்</th><th>பங்கு</th><th>செயலில்</th><th>கடைசி உள்நுழைவு</th><th></th></tr></thead><tbody>
    ${r.data.map(u => `<tr data-id="${u.id}"><td>${esc(u.name)}</td><td>${esc(u.email)}</td><td>${esc(u.role)}</td><td>${+u.active ? '<span class="yes">✔</span>' : '<span class="no">✖</span>'}</td><td>${esc(fmtDate(u.last_login_at, true))}</td>
      <td class="actions"><button class="btn btn-ghost btn-sm" data-act="edit">✎</button><button class="btn btn-ghost btn-sm" data-act="del">🗑</button></td></tr>`).join('')}</tbody></table></div>`;
  $('#addU').onclick = () => modalForm('புதிய நிர்வாகி', fields, null, async v => { await api('/users', { method: 'POST', body: v }); toast('சேர்க்கப்பட்டது'); viewUsers(); });
  $('#view').querySelector('tbody').onclick = async e => {
    const b = e.target.closest('[data-act]'); if (!b) return;
    const id = b.closest('tr').dataset.id, u = r.data.find(x => x.id == id);
    if (b.dataset.act === 'edit') modalForm('நிர்வாகி — ' + u.name, fields, { ...u, password: '', active: +u.active }, async v => { if (!v.password) delete v.password; await api('/users/' + id, { method: 'PUT', body: v }); toast('சேமிக்கப்பட்டது'); viewUsers(); });
    else if (confirm(u.email + ' கணக்கை நீக்கவா?')) { try { await api('/users/' + id, { method: 'DELETE' }); viewUsers(); } catch (err) { toast(err.message, true); } }
  };
}

// ------------------------------------------------------------------ API tokens
async function viewTokens() {
  setTitle('🔑 API Tokens');
  const r = await api('/tokens');
  $('#view').innerHTML = `
    <div class="panel"><h3>புதிய API token உருவாக்கு</h3>
      <p class="muted small">மொபைல் ஆப் அல்லது பிற சேவைகள் API-ஐ அழைக்க: <code>Authorization: Bearer &lt;token&gt;</code>. <b>read</b> = வெளியிடப்பட்ட தரவை மட்டும் படிக்கலாம்; <b>read,write</b> = முழு அணுகல்.</p>
      <form id="tokForm" class="toolbar"><input name="name" placeholder="பெயர் (எ.கா. Android App)" required style="max-width:280px">
        <select name="abilities"><option value="read">read</option><option value="read,write">read,write</option></select>
        <input name="expires_days" type="number" min="0" placeholder="காலாவதி (நாட்கள், 0 = இல்லை)" style="max-width:240px">
        <button class="btn">உருவாக்கு</button></form>
      <div id="newTok"></div></div>
    <div class="tbl-wrap" style="margin-top:1rem"><table><thead><tr><th>பெயர்</th><th>அனுமதி</th><th>பயனர்</th><th>கடைசி பயன்பாடு</th><th>காலாவதி</th><th></th></tr></thead><tbody>
    ${r.data.map(t => `<tr data-id="${t.id}"><td>${esc(t.name)}</td><td><code>${esc(t.abilities)}</code></td><td>${esc(t.user_email || '—')}</td><td>${esc(fmtDate(t.last_used_at, true))}</td><td>${esc(t.expires_at ? fmtDate(t.expires_at, true) : 'இல்லை')}</td>
      <td class="actions"><button class="btn btn-ghost btn-sm" data-act="del">ரத்து செய்</button></td></tr>`).join('')}</tbody></table></div>`;
  $('#tokForm').onsubmit = async e => {
    e.preventDefault(); const f = e.target;
    try {
      const t = await api('/tokens', { method: 'POST', body: { name: f.name.value, abilities: f.abilities.value, expires_days: f.expires_days.value } });
      await viewTokens();
      $('#newTok').innerHTML = `<p><b>${esc(t.note)}</b></p><div class="token-box">${esc(t.access_token)}</div>`;
    } catch (err) { toast(err.message, true); }
  };
  $('#view').querySelector('tbody').onclick = async e => {
    const b = e.target.closest('[data-act]'); if (!b) return;
    if (!confirm('இந்த token-ஐ ரத்து செய்யவா? இதைப் பயன்படுத்தும் சேவைகள் உடனே நிறுத்தப்படும்.')) return;
    await api('/tokens/' + b.closest('tr').dataset.id, { method: 'DELETE' }); viewTokens();
  };
}

boot();
