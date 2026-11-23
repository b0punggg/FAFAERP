@extends('layouts.app')
@section('title', 'Buku besar '.$code)
@section('content')
@include('components.page-header', [
    'title' => $code.' — '.$accountName,
    'description' => 'Jejak jurnal ke nota pada periode yang sama.',
])
<p class="mb-3"><a href="{{ route('finance.trial-balance', ['company_id' => $companyId, 'from' => $from, 'to' => $to]) }}" class="btn-fafa btn-fafa-secondary btn-fafa-sm">Kembali ke trial balance</a></p>
<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Memo / nota</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($lines as $line)
                @php $url = $posting->documentUrl($line['reference_type'] ?? null, $line['reference_id'] ?? null); @endphp
                <tr>
                    <td>{{ $line['entry_date'] }}</td>
                    <td>{{ $line['memo'] ?: ($line['line_memo'] ?? '') }}</td>
                    <td class="text-right">Rp {{ number_format((float) $line['debit'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((float) $line['credit'], 0, ',', '.') }}</td>
                    <td>
                        @if($url)
                            <a href="{{ $url }}">Buka</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada jurnal akun ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
