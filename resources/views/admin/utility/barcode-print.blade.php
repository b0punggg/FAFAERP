<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Cetak {{ $mode === 'qr' ? 'QR' : 'Barcode' }}</title>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<style>
  body { font-family: Arial, sans-serif; margin: 0; background: #fff; }
  .toolbar { padding: .75rem 1rem; }
  .sheet { display: grid; gap: 1mm; }
  .cell { overflow: hidden; text-align: center; page-break-inside: avoid; }
  .cell svg, .cell canvas { max-width: 100%; height: auto; }
  .name { font-size: 7pt; font-weight: 700; margin: 0; line-height: 1.15; }
  .price { font-size: 7pt; font-weight: 700; margin: 0; }
  @media print { .toolbar { display: none; } }
  @if($paper === 'A4')
  @page { size: A4; margin: 6mm; }
  .sheet { grid-template-columns: repeat(9, 1fr); }
  .cell { height: 18mm; }
  @elseif($paper === '58')
  @page { size: 58mm auto; margin: 1mm; }
  .sheet { width: 56mm; grid-template-columns: 1fr 1fr; }
  .cell { height: 16mm; }
  @elseif($paper === '80')
  @page { size: 80mm auto; margin: 1mm; }
  .sheet { width: 78mm; grid-template-columns: 1fr 1fr; }
  .cell { height: 16mm; }
  @else
  @page { size: 70mm auto; margin: 1mm; }
  .sheet { width: 68mm; grid-template-columns: 1fr 1fr; }
  .cell { height: 22mm; }
  @endif
</style>
</head>
<body>
<div class="toolbar">
  <button type="button" onclick="window.print()">Cetak</button>
  <span>{{ count($labels) }} label · kertas {{ $paper }}{{ $paper === 'A4' ? '' : ' mm' }}</span>
</div>
<div class="sheet">
@foreach($labels as $label)
  <div class="cell">
    @if($mode === 'qr')
      <p class="name">{{ \Illuminate\Support\Str::limit($label['name'], 42) }}</p>
      <canvas class="qr" data-code="{{ $label['code'] }}"></canvas>
      <p class="price">Rp {{ number_format($label['price'], 0, ',', '.') }}</p>
    @else
      <svg class="barcode" data-code="{{ $label['code'] }}"></svg>
    @endif
  </div>
@endforeach
</div>
<script>
document.querySelectorAll('.barcode').forEach(function (el) {
  try {
    JsBarcode(el, el.dataset.code, { format: 'CODE128', width: 1, height: 36, displayValue: true, fontSize: 10, margin: 0 });
  } catch (e) {}
});
document.querySelectorAll('.qr').forEach(function (el) {
  QRCode.toCanvas(el, el.dataset.code, { width: 72, margin: 0 });
});
setTimeout(function () { window.print(); }, 500);
</script>
</body>
</html>
