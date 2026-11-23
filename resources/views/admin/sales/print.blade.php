<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota {{ $sale->no_fak }}</title>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 12px; max-width: 80mm; margin: 1rem auto; }
        body.format-a4 { font-family: Arial, sans-serif; font-size: 14px; max-width: 210mm; padding: 16px; }
        .center { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 2px 0; vertical-align: top; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .actions { text-align: center; margin-top: 12px; }
        .actions a, .actions button { margin: 0 4px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body class="{{ ($format ?? 'default') === 'a4' ? 'format-a4' : '' }}">
    @php
        $itemDisc = $sale->items->sum('disc');
        $cara = $sale->kd_bayar ?: 'TUNAI';
        $kembali = in_array($cara, ['TUNAI', 'COD', 'QRIS', 'BNI'], true)
            ? max(0, (float) $sale->diterima - (float) $sale->tot_bayar)
            : 0;
    @endphp
    <div class="center">
        <strong>{{ session('nm_toko', 'FAFA Store') }}</strong><br>
        @if(($format ?? 'default') === 'member') Nota Member
        @elseif(($format ?? 'default') === 'crew') Nota Crew
        @elseif(($format ?? 'default') === 'a4') Faktur Penjualan (A4)
        @else Nota Penjualan @endif
    </div>
    <p>No: {{ $sale->no_fak }}<br>Tgl: {{ $sale->tgl_jual }}<br>Pel: {{ $sale->nm_pel ?: '-' }}
    @if($sale->kd_member)<br>Member: {{ $sale->nm_member ?: $sale->kd_member }}@if($sale->poin_earned) (+{{ $sale->poin_earned }} poin)@endif
    @endif
    @if($sale->kd_crew)<br>Crew: {{ $sale->nm_crew ?: $sale->kd_crew }}@endif
    <br>Bayar: {{ $cara }}
    </p>
    <div class="line"></div>
    <table>
        @foreach($sale->items as $item)
        <tr>
            <td colspan="2">{{ $item->nm_brg }}</td>
        </tr>
        <tr>
            <td>{{ $item->jml_brg }} {{ $item->nm_sat ?: 'pcs' }} x {{ number_format($item->hrg_jual, 0, ',', '.') }}</td>
            <td style="text-align:right">{{ number_format($item->tot_jual, 0, ',', '.') }}</td>
        </tr>
        @if((float) $item->disc > 0)
        <tr>
            <td>Diskon item</td>
            <td style="text-align:right">-{{ number_format($item->disc, 0, ',', '.') }}</td>
        </tr>
        @endif
        @endforeach
    </table>
    <div class="line"></div>
    <table>
        <tr><td>Jumlah</td><td style="text-align:right">{{ number_format($sale->tot_jual, 0, ',', '.') }}</td></tr>
        @if($itemDisc > 0)
        <tr><td>Diskon item</td><td style="text-align:right">-{{ number_format($itemDisc, 0, ',', '.') }}</td></tr>
        @endif
        @if((float) $sale->disc_nota > 0)
        <tr><td>Diskon nota</td><td style="text-align:right">-{{ number_format($sale->disc_nota, 0, ',', '.') }}</td></tr>
        @endif
        @if((float) $sale->voucher > 0)
        <tr><td>Voucher umum</td><td style="text-align:right">-{{ number_format($sale->voucher, 0, ',', '.') }}</td></tr>
        @endif
        @if((float) $sale->voucher_member > 0)
        <tr><td>Voucher member</td><td style="text-align:right">-{{ number_format($sale->voucher_member, 0, ',', '.') }}</td></tr>
        @endif
        @if((float) $sale->poin_redeem > 0)
        <tr><td>Tukar poin</td><td style="text-align:right">-{{ number_format($sale->poin_redeem, 0, ',', '.') }}</td></tr>
        @endif
        <tr><td><strong>TOTAL</strong></td><td style="text-align:right"><strong>{{ number_format($sale->tot_bayar, 0, ',', '.') }}</strong></td></tr>
        <tr><td>Diterima</td><td style="text-align:right">{{ number_format($sale->diterima, 0, ',', '.') }}</td></tr>
        @if($cara === 'TEMPO')
        <tr><td>Tunai</td><td style="text-align:right">{{ number_format($sale->byr_tunai, 0, ',', '.') }}</td></tr>
        <tr><td>Sisa tempo</td><td style="text-align:right">{{ number_format($sale->byr_hutang, 0, ',', '.') }}</td></tr>
        @else
        <tr><td>Kembali</td><td style="text-align:right">{{ number_format($kembali, 0, ',', '.') }}</td></tr>
        @endif
    </table>
    <p class="center" style="margin-top:12px">Terima kasih</p>
    @if(($format ?? 'default') !== 'a4')
    <p class="center" id="printStatus">Mengirim ke printer thermal...</p>
    @endif
    <p class="actions">
        <button type="button" onclick="window.print()">Cetak lewat dialog</button>
        <a href="{{ route('sales.print', ['id' => $sale->no_urut, 'format' => 'a4']) }}">A4</a>
        @if($sale->kd_member)<a href="{{ route('sales.print', ['id' => $sale->no_urut, 'format' => 'member']) }}">Member</a>@endif
        @if($sale->kd_crew)<a href="{{ route('sales.print', ['id' => $sale->no_urut, 'format' => 'crew']) }}">Crew</a>@endif
        <a href="{{ route('sales.create') }}">Nota baru</a>
    </p>
    <script>
    window.addEventListener('load', function () {
        @if(($format ?? 'default') === 'a4')
        window.print();
        return;
        @endif
        var status = document.getElementById('printStatus');
        fetch(@json(route('sales.thermal', $sale->no_urut)), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) throw new Error('gagal');
            return response.json();
        }).then(function (data) {
            if (data.ok) {
                status.textContent = 'Nota sudah dikirim ke printer thermal.';
                return;
            }
            throw new Error('printer');
        }).catch(function () {
            status.textContent = 'Printer thermal tidak tersambung. Dialog cetak dibuka.';
            window.print();
        });
    });
    </script>
</body>
</html>
