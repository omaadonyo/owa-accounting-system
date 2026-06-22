import './custom-select';

window.printPreview = function () {
    const card = document.querySelector('.preview-card');
    if (!card) return;
    const clone = card.cloneNode(true);
    clone.querySelectorAll('img').forEach(function (img) { img.style.maxWidth = '120px'; img.style.maxHeight = '60px'; img.style.height = 'auto'; img.style.objectFit = 'contain'; });
    clone.querySelectorAll('svg').forEach(function (s) { s.style.maxWidth = '250px'; s.style.maxHeight = '250px'; });
    const html = clone.outerHTML;
    const bizName = (card.querySelector('h2')?.textContent || '').trim();
    const docTitle = (card.querySelector('h1')?.textContent || '').trim();
    const docNum = (card.querySelector('.font-mono')?.textContent || '').trim();
    const title = [bizName, docTitle, docNum].filter(Boolean).join(' - ');
    var ss = '';
    document.querySelectorAll('link[rel="stylesheet"], style').forEach(function (el) { ss += el.outerHTML + '\n'; });
    var w = window.open('', '', 'width=800,height=600');
    w.document.write('<!DOCTYPE html><html><head><title>' + title + '</title>' + ss + '<style>body{background:#fff;padding:20px;font-family:Instrument Sans,sans-serif}.preview-card{border:none!important;box-shadow:none!important;background:#fff!important;max-width:210mm;margin:0 auto}.preview-card [class*="dark:"],.preview-card .dark\\:*{color:inherit!important;background:inherit!important;border-color:inherit!important}.preview-card thead th,.preview-card thead td{background:#1e293b!important;color:#fff!important;font-weight:700!important;border-color:#1e293b!important}.preview-card thead th *,.preview-card thead td *{color:#fff!important}.preview-card hr{border-color:#1e293b!important;border-top-width:2px!important;opacity:.15}@media print{@page{size:A4;margin:10mm 15mm}body{margin:0;padding:0}}</style></head><body>' + html + '</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function () { w.print(); }, 400);
};
