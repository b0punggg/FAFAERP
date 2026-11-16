@extends('layouts.app')
@section('title', 'Tukar Poin')
@section('content')
@include('components.page-header', ['title' => 'Tukar poin member'])

<div class="fafa-card"><div class="fafa-card-body form-fafa">
<form method="POST" action="{{ route('retail.points.store') }}">@csrf
<div class="form-group"><label>Cari member</label><input type="text" id="memberKw" class="form-control" autocomplete="off"><div id="memberPick" class="list-group mt-1"></div></div>
<div class="form-group"><label>Kode member</label><input name="kd_member" id="kd_member" class="form-control" required readonly></div>
<div class="form-group"><label>Poin saat ini</label><input id="poin_now" class="form-control" readonly></div>
<div class="form-group"><label>Poin ditukar</label><input type="number" step="0.01" min="0.01" name="poin_tukar" class="form-control" required></div>
<div class="form-group"><label>Keterangan</label><input name="keterangan" class="form-control"></div>
<button class="btn-fafa btn-fafa-primary">Proses tukar</button>
<a href="{{ route('retail.points.history') }}" class="btn-fafa btn-fafa-link">Riwayat semua</a>
</form></div></div>
@push('scripts')
<script>
$('#memberKw').on('keyup', function() {
    const kw = $(this).val();
    if (kw.length < 2) return;
    $.post('{{ route('retail.points.search-member') }}', {_token:'{{ csrf_token() }}', keyword: kw}, function(rows) {
        let h = '';
        rows.forEach(m => { h += `<button type="button" class="list-group-item list-group-item-action py-1" data-kd="${m.kd_member}" data-poin="${m.poin}">${m.kd_member} — ${m.nm_member} (${m.poin} poin)</button>`; });
        $('#memberPick').html(h);
    });
});
$('#memberPick').on('click', 'button', function() {
    $('#kd_member').val($(this).data('kd'));
    $('#poin_now').val($(this).data('poin'));
    $('#memberPick').empty();
});
</script>
@endpush
@endsection
