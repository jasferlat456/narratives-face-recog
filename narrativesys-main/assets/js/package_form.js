/* Package form: live validation, live preview, drag & drop banner, inclusion editor */
(() => {
  'use strict';
  const form = document.getElementById('pkgForm');
  if (!form) return;

  const isEdit = form.dataset.edit === '1';
  const MAX_MB = 4, MIN_W = 600, MAX_INCL = 20;
  const $  = id => document.getElementById(id);
  const el = n  => form.elements[n];
  const box = k => form.querySelector(`[data-err="${k}"]`);
  const list = $('inclList'), addBtn = $('addIncl'), submitBtn = $('submitBtn');
  const fileInput = el('banner'), drop = $('drop');

  let bannerError = '', hasBanner = isEdit, dirty = false, submitting = false;

  /* ---------- helpers ---------- */
  const formatPeso = v => {
    const n = parseFloat(v);
    if (isNaN(n)) return '₱0';
    return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: Number.isInteger(n) ? 0 : 2, maximumFractionDigits: 2 });
  };
  function setErr(key, msg) {
    const b = box(key); if (b) b.textContent = msg || '';
    const field = el(key);
    if (field && field.classList) { field.classList.toggle('invalid', !!msg); field.setAttribute('aria-invalid', msg ? 'true' : 'false'); }
  }

  /* ---------- field rules ---------- */
  const rules = {
    name: v => {
      v = v.trim();
      if (!v) return 'Package name is required.';
      if (v.length < 2) return 'Package name must be at least 2 characters.';
      if (v.length > 120) return 'Package name must be 120 characters or fewer.';
      return '';
    },
    description: v => {
      v = v.trim();
      if (!v) return 'Description is required.';
      if (v.length < 3) return 'Description must be at least 3 characters.';
      if (v.length > 255) return 'Description must be 255 characters or fewer.';
      return '';
    },
    price: v => {
      v = v.trim();
      if (v === '') return 'Price is required.';
      if (isNaN(v)) return 'Enter a valid number.';
      if (parseFloat(v) <= 0) return 'Price must be greater than 0.';
      if (parseFloat(v) > 999999.99) return 'Price is too high.';
      if (/\.\d{3,}/.test(v)) return 'Use at most 2 decimal places.';
      return '';
    },
    duration: v => (v.length > 60 ? 'Duration must be 60 characters or fewer.' : '')
  };
  const validateField = n => { const m = rules[n](el(n).value); setErr(n, m); return m; };

  /* ---------- inclusions ---------- */
  const rowHTML = (val = '') => `
    <div class="incl-row">
      <input type="text" name="inclusions[]" value="${val.replace(/"/g, '&quot;')}" placeholder="e.g. 2 pcs 4R Prints" maxlength="255" autocomplete="off">
      <button type="button" class="icon-btn" data-up title="Move up">↑</button>
      <button type="button" class="icon-btn" data-down title="Move down">↓</button>
      <button type="button" class="icon-btn danger" data-remove title="Remove">✕</button>
    </div>`;
  const rows   = () => [...list.querySelectorAll('.incl-row')];
  const values = () => rows().map(r => r.querySelector('input').value.trim());

  function validateIncl() {
    const vals = values(), seen = new Map(); let msg = '';
    const inputs = rows().map(r => r.querySelector('input'));
    inputs.forEach(i => i.classList.remove('invalid'));
    const mark = i => inputs[i].classList.add('invalid');

    if (!vals.filter(Boolean).length) msg = 'Add at least one inclusion.';
    vals.forEach((v, i) => {
      if (!v) return;
      if (v.length > 255) { msg = msg || 'Each inclusion must be 255 characters or fewer.'; mark(i); }
      const k = v.toLowerCase();
      if (seen.has(k)) { msg = msg || `Duplicate inclusion: “${v}”.`; mark(i); mark(seen.get(k)); }
      else seen.set(k, i);
    });
    setErr('inclusions', msg);
    return msg;
  }
  function refreshInclUI() {
    const n = values().filter(Boolean).length;
    $('inclCount').textContent = `(${n}/${MAX_INCL})`;
    addBtn.disabled = rows().length >= MAX_INCL;
    rows().forEach((r, i, all) => {
      r.querySelector('[data-up]').disabled = i === 0;
      r.querySelector('[data-down]').disabled = i === all.length - 1;
    });
  }
  function addRow(after, val = '') {
    if (rows().length >= MAX_INCL) return;
    (after || list.lastElementChild || list).insertAdjacentHTML(after || list.lastElementChild ? 'afterend' : 'beforeend', rowHTML(val));
    const r = after ? after.nextElementSibling : list.lastElementChild;
    r.querySelector('input').focus();
    refreshInclUI(); renderPreview();
  }

  addBtn.addEventListener('click', () => addRow(null));
  list.addEventListener('click', e => {
    const row = e.target.closest('.incl-row'); if (!row) return;
    if (e.target.matches('[data-remove]')) {
      if (rows().length > 1) row.remove(); else row.querySelector('input').value = '';
    } else if (e.target.matches('[data-up]') && row.previousElementSibling) {
      row.parentNode.insertBefore(row, row.previousElementSibling);
    } else if (e.target.matches('[data-down]') && row.nextElementSibling) {
      row.parentNode.insertBefore(row.nextElementSibling, row);
    } else return;
    dirty = true; refreshInclUI(); validateIncl(); renderPreview();
  });
  list.addEventListener('input', () => { dirty = true; refreshInclUI(); if (box('inclusions').textContent) validateIncl(); renderPreview(); });
  list.addEventListener('focusout', validateIncl);
  list.addEventListener('keydown', e => {
    if (!e.target.matches('input')) return;
    const row = e.target.closest('.incl-row');
    if (e.key === 'Enter') { e.preventDefault(); if (e.target.value.trim()) addRow(row); }
    if (e.key === 'Backspace' && !e.target.value && rows().length > 1) {
      e.preventDefault();
      const prev = row.previousElementSibling || row.nextElementSibling;
      row.remove(); prev.querySelector('input').focus(); refreshInclUI(); renderPreview();
    }
  });

  /* ---------- banner ---------- */
  const bannerMsg = () => bannerError || (!isEdit && !hasBanner ? 'Banner photo is required.' : '');
  function finishBanner() {
    const m = bannerMsg();
    box('banner').textContent = m;
    drop.classList.toggle('invalid', !!m);
    const f = fileInput.files[0];
    $('dropText').textContent = f ? `${f.name} (${(f.size / 1048576).toFixed(2)} MB)` : 'Drag & drop an image, or click to browse';
  }
  function handleFile(file) {
    bannerError = ''; hasBanner = isEdit;
    if (!file) return finishBanner();
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) bannerError = 'Banner must be JPG, PNG or WEBP.';
    else if (file.size > MAX_MB * 1048576) bannerError = `Banner must be ${MAX_MB} MB or smaller (yours is ${(file.size / 1048576).toFixed(1)} MB).`;
    if (bannerError) { fileInput.value = ''; return finishBanner(); }

    const url = URL.createObjectURL(file), img = new Image();
    img.onload = () => {
      if (img.naturalWidth < MIN_W) {
        bannerError = `Image is too small (${img.naturalWidth}px wide). Minimum is ${MIN_W}px.`;
        fileInput.value = ''; URL.revokeObjectURL(url);
      } else {
        hasBanner = true; dirty = true;
        $('bannerPrev').style.backgroundImage = $('pvMedia').style.backgroundImage = `url('${url}')`;
      }
      finishBanner();
    };
    img.onerror = () => { bannerError = 'That file is not a valid image.'; fileInput.value = ''; finishBanner(); };
    img.src = url;
  }
  fileInput.addEventListener('change', () => handleFile(fileInput.files[0]));
  ['dragenter', 'dragover'].forEach(t => drop.addEventListener(t, e => { e.preventDefault(); drop.classList.add('over'); }));
  ['dragleave', 'drop'].forEach(t => drop.addEventListener(t, e => { e.preventDefault(); drop.classList.remove('over'); }));
  drop.addEventListener('drop', e => {
    const f = e.dataTransfer.files[0]; if (!f) return;
    const dt = new DataTransfer(); dt.items.add(f); fileInput.files = dt.files;
    handleFile(f);
  });

  /* ---------- live preview ---------- */
  function renderPreview() {
    $('pvName').textContent  = el('name').value.trim() || 'Package name';
    $('pvDesc').textContent  = el('description').value.trim() || 'Short description';
    $('pvPrice').textContent = formatPeso(el('price').value);
    const dur = el('duration').value.trim();
    $('pvMeta').textContent = dur; $('pvMeta').hidden = !dur;
    $('pvBadge').hidden = !el('is_popular').checked;
    $('pvCard').classList.toggle('is-popular', el('is_popular').checked);

    const off = el('status').value === 'unavailable';
    $('pvOff').hidden = !off;
    $('pvCard').style.opacity = off ? .6 : 1;
    $('pvNote').textContent = off ? 'Unavailable packages are hidden from the landing page.' : '';

    const vals = values().filter(Boolean);
    $('pvList').innerHTML = '';
    vals.slice(0, 4).forEach(t => { const li = document.createElement('li'); li.textContent = t; $('pvList').appendChild(li); });
    const more = vals.length - 4;
    $('pvMore').hidden = more <= 0;
    if (more > 0) $('pvMore').textContent = `+ ${more} more inclusion${more > 1 ? 's' : ''}`;
  }

  /* ---------- wiring ---------- */
  ['name', 'description', 'price', 'duration'].forEach(n => {
    const input = el(n); let touched = !!box(n).textContent;
    input.addEventListener('blur', () => { touched = true; validateField(n); });
    input.addEventListener('input', () => {
      dirty = true;
      if (touched) validateField(n);
      const c = form.querySelector(`[data-count="${n}"]`);
      if (c) { c.textContent = `${input.value.length}/${input.maxLength}`; c.classList.toggle('warn', input.value.length > input.maxLength * .9); }
      renderPreview();
    });
  });
  el('price').addEventListener('blur', () => { const v = el('price').value; if (v !== '' && !isNaN(v) && +v > 0) el('price').value = (+v).toFixed(2); renderPreview(); });
  el('price').addEventListener('wheel', e => e.target.blur());       // stop accidental scroll changes
  el('status').addEventListener('change', () => { dirty = true; renderPreview(); });
  el('is_popular').addEventListener('change', () => { dirty = true; renderPreview(); });

  form.addEventListener('submit', e => {
    const invalid = [];
    ['name', 'description', 'price', 'duration'].forEach(n => { if (validateField(n)) invalid.push(el(n)); });
    if (validateIncl()) invalid.push(list.querySelector('.invalid') || list.querySelector('input'));
    finishBanner(); if (bannerMsg()) invalid.push(drop);

    if (invalid.length) {
      e.preventDefault();
      // focus the first invalid control in page order
      invalid.sort((a, b) => (a.compareDocumentPosition(b) & 4 ? -1 : 1));
      const first = invalid[0];
      first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      (first.matches('input,select') ? first : first.querySelector('input'))?.focus({ preventScroll: true });
      return;
    }
    submitting = true; submitBtn.disabled = true; submitBtn.textContent = 'Saving…';
  });

  window.addEventListener('beforeunload', e => { if (dirty && !submitting) { e.preventDefault(); e.returnValue = ''; } });

  // initial state
  ['name', 'description'].forEach(n => { const i = el(n); form.querySelector(`[data-count="${n}"]`).textContent = `${i.value.length}/${i.maxLength}`; });
  if (box('banner').textContent) drop.classList.add('invalid');
  refreshInclUI(); renderPreview();
})();
