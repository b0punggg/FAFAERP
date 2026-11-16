@php
    $item = $editItem ?? null;
    $v = fn ($key, $default = '') => old($key, $item ? data_get($item, $key) : $default);
@endphp

@if($routePrefix === 'master.kemas')
    <div class="row">
        <div class="col-md-4 form-group"><label>Satuan 1</label><input name="nm_sat1" class="form-control" value="{{ $v('nm_sat1') }}" required></div>
        <div class="col-md-4 form-group"><label>Satuan 2</label><input name="nm_sat2" class="form-control" value="{{ $v('nm_sat2') }}"></div>
    </div>
@elseif($routePrefix === 'master.suppliers')
    <div class="row">
        @if($mode === 'create')
            <div class="col-md-2 form-group"><label>Kode</label><input name="kd_sup" class="form-control" value="{{ $v('kd_sup') }}" required></div>
        @endif
        <div class="col-md-4 form-group"><label>Nama</label><input name="nm_sup" class="form-control" value="{{ $v('nm_sup') }}" required></div>
        <div class="col-md-3 form-group"><label>Alamat</label><input name="al_sup" class="form-control" value="{{ $v('al_sup') }}"></div>
        <div class="col-md-3 form-group"><label>Telepon</label><input name="telp_sup" class="form-control" value="{{ $v('telp_sup') }}"></div>
    </div>
@elseif($routePrefix === 'master.customers')
    <div class="row">
        @if($mode === 'create')
            <div class="col-md-2 form-group"><label>Kode</label><input name="kd_pel" class="form-control" value="{{ $v('kd_pel') }}" required></div>
        @endif
        <div class="col-md-4 form-group"><label>Nama</label><input name="nm_pel" class="form-control" value="{{ $v('nm_pel') }}" required></div>
        <div class="col-md-3 form-group"><label>Alamat</label><input name="al_pel" class="form-control" value="{{ $v('al_pel') }}"></div>
        <div class="col-md-3 form-group"><label>Telepon</label><input name="telp_pel" class="form-control" value="{{ $v('telp_pel') }}"></div>
    </div>
@elseif($routePrefix === 'master.bagian')
    <div class="row">
        <div class="col-md-6 form-group"><label>Nama bagian</label><input name="nm_bag" class="form-control" value="{{ $v('nm_bag') }}" required></div>
    </div>
@elseif($routePrefix === 'master.paket')
    <div class="row">
        <div class="col-md-6 form-group"><label>Nama paket</label><input name="nm_paket" class="form-control" value="{{ $v('nm_paket') }}" required></div>
    </div>
@elseif($routePrefix === 'master.stores')
    <div class="row">
        <div class="col-md-2 form-group"><label>Kode</label><input class="form-control" value="{{ $v('kd_toko') }}" disabled></div>
        <div class="col-md-4 form-group"><label>Nama gerai</label><input name="name" class="form-control" value="{{ $v('name') }}" required></div>
        <div class="col-md-4 form-group"><label>Alamat</label><input name="address" class="form-control" value="{{ $v('address') }}"></div>
        <div class="col-md-2 form-group"><label>Aktif</label>
            @php $active = old('is_active', $item?->is_active ?? true); @endphp
            <select name="is_active" class="form-control">
                <option value="1" @selected((string)$active === '1' || $active === true)>Ya</option>
                <option value="0" @selected((string)$active === '0' || $active === false)>Tidak</option>
            </select>
        </div>
    </div>
@endif
