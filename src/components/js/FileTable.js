(() => {
  const root = document.getElementById('file-table');
  if (!root) return;

  const dir = root.dataset.dir;
  const canWrite = root.dataset.canWrite === '1';
  const csrf = root.dataset.csrf;
  const API = root.dataset.api;

  const $ = id => document.getElementById(id);
  const I18N = JSON.parse(root.dataset.i18n || '{}');
  const t = key => I18N[key] || key;

  // Simple POST (create, delete, rename). Returns { status, ok | error }
  async function send(data) {
    const body = new FormData();
    for (const [k, v] of Object.entries(data)) body.append(k, v);
    const res = await fetch(API, { method: 'POST', body, headers: { 'X-CSRF-Token': csrf } });
    return { status: res.status, ...(await res.json().catch(() => ({}))) };
  }

  // Every "Cancel" button closes its own dialog
  document.querySelectorAll('dialog [data-close]').forEach(btn =>
    btn.addEventListener('click', () => btn.closest('dialog').close())
  );

  /* ---------- Create folder (Vue: toggleAddFilePopup + createNewFile) ---------- */
  $('btn-new').addEventListener('click', () => {
    $('create-name').value = '';
    $('dlg-create').showModal();
  });

  $('create-ok').addEventListener('click', async () => {
    const name = $('create-name').value.trim();
    if (!name) return;
    const r = await send({ action: 'create', dir, name });
    if (r.ok) location.reload();
    else alert(r.status === 409 ? t('cant.create.folder') + name + t('already.exists') : r.error);
  });

  /* ---------- Rename + delete (Vue: editElem / updateFileName / deleteElem) ---------- */
  let renameTarget = null;

  root.addEventListener('click', async e => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const { action, path, name } = btn.dataset;

    if (action === 'delete') {
      if (!confirm(`${t('delete')} : ${name} ?`)) return;
      const r = await send({ action: 'delete', path });
      if (r.ok) location.reload(); else alert(r.error || t('error'));
    }

    if (action === 'rename') {
      renameTarget = { path, name };
      $('rename-name').value = name;
      $('dlg-rename').showModal();
    }
  });

  $('rename-ok').addEventListener('click', async () => {
    const newName = $('rename-name').value.trim();
    if (!newName || newName === renameTarget.name) return $('dlg-rename').close();
    const r = await send({ action: 'rename', path: renameTarget.path, newName });
    if (r.ok) location.reload();
    else alert(r.status === 409 ? t('cant.rename') + newName + t('already.exists') : r.error);
  });

  /* ---------- Progress bar (Vue: transferProgress / transferStatus) ---------- */
  const btnNew = $('btn-new');
  const transfer = $('transfer');
  const bar = transfer.querySelector('[data-progress-bar]');

  function setProgress(pct, failed = false) {
    bar.style.width = Math.max(0, Math.min(pct, 100)) + '%';
    bar.classList.toggle('bg-red-500', failed);
    bar.classList.toggle('bg-blue-500', !failed);
  }

  /* ---------- Upload (Vue: moveFileToTarget) ---------- */
  // XMLHttpRequest instead of fetch: it gives real upload progress
  function uploadOne(file, name, overwrite, onProgress) {
    return new Promise(resolve => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', API);
      xhr.setRequestHeader('X-CSRF-Token', csrf);
      xhr.upload.onprogress = e => e.lengthComputable && onProgress(e.loaded / e.total);
      xhr.onload = () => {
        let json = {};
        try { json = JSON.parse(xhr.responseText); } catch {}
        resolve({ status: xhr.status, ...json });
      };
      xhr.onerror = () => resolve({ status: 0, error: 'Network error' });

      const body = new FormData();
      body.append('action', 'upload');
      body.append('dir', dir);
      body.append('name', name);
      body.append('overwrite', overwrite ? '1' : '0');
      body.append('file', file);
      xhr.send(body);
    });
  }

  // "Name already exists" dialog (Vue: FileExistsDialog). Returns a promise.
  function askConflict(name) {
    return new Promise(resolve => {
      const dlg = $('dlg-exists');
      $('exists-text').textContent = name;
      $('exists-name').value = name;
      $('exists-all').checked = false;

      const done = choice => {
        dlg.close();
        resolve({ choice, newName: $('exists-name').value.trim(), all: $('exists-all').checked });
      };
      $('exists-overwrite').onclick = () => done('overwrite');
      $('exists-rename').onclick = () => done('rename');
      $('exists-cancel').onclick = () => done('cancel');
      dlg.oncancel = e => { e.preventDefault(); done('cancel'); };   // Escape key
      dlg.showModal();
    });
  }

  async function uploadFiles(files) {
    btnNew.classList.add('hidden');
    transfer.classList.remove('hidden');
    setProgress(0);

    let overwriteAll = false;

    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const report = fraction => setProgress(((i + fraction) / files.length) * 100);

      let r = await uploadOne(file, file.name, overwriteAll, report);

      if (r.status === 409) {                       // name already exists
        const c = await askConflict(file.name);
        if (c.choice === 'cancel') break;
        if (c.choice === 'overwrite') {
          overwriteAll = c.all;
          r = await uploadOne(file, file.name, true, report);
        } else {
          r = await uploadOne(file, c.newName, false, report);
        }
      }

      if (!r.ok) {
        setProgress(100, true);
        alert(r.error || t('error'));
        break;
      }
    }
    location.reload();   // show the new files
  }

  /* ---------- Drag and drop (Vue: onDragOver / onDrop / ...) ---------- */
  const zone = $('drop-zone');
  let depth = 0;   // dragenter/dragleave also fire on children: count them

  function setZone(state) {   // 'ok' | 'no' | null
    zone.classList.toggle('border-transparent', !state);
    zone.classList.toggle('border-dashed', !!state);
    zone.classList.toggle('border-green-500', state === 'ok');
    zone.classList.toggle('border-red-500', state === 'no');
    zone.classList.toggle('cursor-no-drop', state === 'no');
  }

  const hasFiles = e => e.dataTransfer && [...e.dataTransfer.types].includes('Files');

  zone.addEventListener('dragenter', e => {
    if (!hasFiles(e)) return;
    e.preventDefault();
    depth++;
    setZone(canWrite ? 'ok' : 'no');
  });

  zone.addEventListener('dragover', e => {
    if (!hasFiles(e)) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = canWrite ? 'copy' : 'none';
  });

  zone.addEventListener('dragleave', () => {
    if (--depth <= 0) { depth = 0; setZone(null); }
  });

  zone.addEventListener('drop', e => {
    e.preventDefault();
    depth = 0;
    setZone(null);
    if (!canWrite || !hasFiles(e)) return;

    // Files only: dropped folders are skipped (see notes)
    const files = [...e.dataTransfer.items]
      .filter(item => item.kind === 'file' && !item.webkitGetAsEntry()?.isDirectory)
      .map(item => item.getAsFile());

    if (files.length) uploadFiles(files);
  });
})();
