@extends('layouts.app')
@section('title', 'Setting Printer')
@section('content')
@include('components.page-header', ['title' => 'Proses data / printer'])
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="fafa-card"><div class="fafa-card-body form-fafa">
<form method="POST" action="{{ route('utility.settings.save') }}">@csrf
<div class="form-group"><label>Nama printer (TSPL / QZ)</label><input name="printer_name" class="form-control" value="{{ $settings->printer_name }}"></div>
<div class="form-group"><label>Salinan barcode</label><input type="number" name="barcode_copies" class="form-control" min="1" max="20" value="{{ $settings->barcode_copies ?? 1 }}"></div>
<div class="form-group"><label>Mode</label><select name="barcode_printer_type" class="form-control"><option value="browser" @selected(($settings->barcode_printer_type ?? '')==='browser')>Browser print</option><option value="tspl" @selected(($settings->barcode_printer_type ?? '')==='tspl')>TSPL (manual/QZ)</option></select></div>
<button class="btn-fafa btn-fafa-primary">Simpan</button>
</form></div></div>
@endsection
