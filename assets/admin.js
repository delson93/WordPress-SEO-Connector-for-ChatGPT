/* global codeixeSEO */
(() => {
  'use strict';
  const el = id => document.getElementById(`codeixe-${id}`);
  let report;
  async function api(path, body) {
    const response = await fetch(codeixeSEO.root + path, {
      method: body ? 'POST' : 'GET', credentials: 'same-origin',
      headers: { 'X-WP-Nonce': codeixeSEO.nonce, 'Content-Type': 'application/json' },
      body: body ? JSON.stringify(body) : undefined
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || `HTTP ${response.status}`);
    return data;
  }
  function task(button, output, callback) {
    el(button).addEventListener('click', async () => {
      el(button).disabled = true;
      el(output).textContent = 'Working…';
      try { await callback(); } catch (error) { el(output).textContent = error.message; }
      finally { el(button).disabled = false; }
    });
  }
  task('scan', 'summary', async () => {
    report = await api(`audit?page=${encodeURIComponent(el('page').value)}&type=${encodeURIComponent(el('type').value)}`);
    el('results').replaceChildren();
    let count = 0;
    for (const item of report.items) {
      const details = document.createElement('details');
      const summary = document.createElement('summary');
      count += item.issues.length;
      summary.textContent = `#${item.id}: ${item.issues.length} findings · ${item.url}`;
      const pre = document.createElement('pre');
      pre.textContent = JSON.stringify(item, null, 2);
      details.append(summary, pre);
      el('results').append(details);
    }
    el('summary').textContent = `${report.items.length} objects audited, ${count} findings. ${report.has_more ? 'More objects available on the next page.' : 'End of this post type.'}`;
    el('export').disabled = false;
    el('csv').disabled = false;
  });
  function download(content, type, filename) {
    const url = URL.createObjectURL(new Blob([content], { type }));
    const link = document.createElement('a');
    link.href = url; link.download = filename; link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }
  el('export').addEventListener('click', () => download(JSON.stringify(report, null, 2), 'application/json', 'codeixe-audit.json'));
  el('csv').addEventListener('click', () => {
    const cell = value => {
      let text = String(value ?? '');
      if (/^[\s]*[=+@-]/.test(text)) text = "'" + text;
      return '"' + text.replace(/"/g, '""') + '"';
    };
    const rows = [['ID', 'URL', 'Code', 'Severity', 'Evidence', 'Recommendation']];
    report.items.forEach(item => item.issues.forEach(issue => rows.push([item.id, item.url, issue.code, issue.severity, JSON.stringify(issue.evidence), issue.solution])));
    download(rows.map(row => row.map(cell).join(',')).join('\r\n'), 'text/csv;charset=utf-8', 'codeixe-audit.csv');
  });
  task('read', 'object', async () => { el('object').textContent = JSON.stringify(await api(`posts/${Number(el('id').value)}`), null, 2); });
  task('rendered', 'object', async () => { el('object').textContent = JSON.stringify(await api(`posts/${Number(el('id').value)}/audit?rendered=true`), null, 2); });
  task('submit', 'proposal-status', async () => {
    const result = await api('proposals', { id: Number(el('id').value), changes: JSON.parse(el('changes').value) });
    el('proposal-status').textContent = `Proposal #${result.id} saved. Reload this page to review it in Change History. Nothing has been applied.`;
  });
})();
