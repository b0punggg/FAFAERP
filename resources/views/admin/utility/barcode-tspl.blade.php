<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Cetak label 70 mm</title>
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js"></script>
<style>
  body { font-family: Arial, sans-serif; margin: 1.5rem; color: #0f172a; }
  button { margin-right: .5rem; }
  pre { background: #f8fafc; padding: .75rem; white-space: pre-wrap; font-size: 12px; }
</style>
</head>
<body>
<p id="status">Menyiapkan {{ $count }} label untuk printer <strong>{{ $printer }}</strong>.</p>
<button type="button" id="retry">Kirim lagi</button>
<button type="button" onclick="window.close()">Tutup</button>
<pre id="raw">{{ $tspl }}</pre>
<script>
var printer = @json($printer);
var tspl = @json($tspl);

function setStatus(text) {
  document.getElementById('status').textContent = text;
}

function kirim() {
  setStatus('Menghubungkan QZ Tray…');
  qz.websocket.connect().then(function () {
    return qz.printers.find(printer);
  }).then(function (found) {
    var config = qz.configs.create(found);
    var data = [{ type: 'raw', format: 'command', data: tspl }];
    return qz.print(config, data);
  }).then(function () {
    setStatus('Label terkirim ke ' + printer + '.');
  }).catch(function (err) {
    setStatus('Belum terkirim. Pastikan QZ Tray menyala dan printer ' + printer + ' terpasang. ' + (err && err.message ? err.message : ''));
  });
}

document.getElementById('retry').addEventListener('click', kirim);
kirim();
</script>
</body>
</html>
