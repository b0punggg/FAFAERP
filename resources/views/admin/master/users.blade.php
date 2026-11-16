@extends('layouts.app')
@section('title', 'Pengguna')
@section('content')
@include('components.page-header', ['title' => 'Pengguna gerai', 'description' => 'Kasir dan kepala toko login di FAFA. Admin dan kepala gudang login di inventory.'])
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="fafa-card mb-3">
    <div class="fafa-card-body">
        <form method="GET" class="form-inline">
            <input name="q" class="form-control mr-2" value="{{ request('q') }}" placeholder="Cari user…">
            <button class="btn-fafa btn-fafa-secondary">Cari</button>
        </form>
    </div>
</div>

@if($editUser ?? null)
<div class="fafa-card mb-3 border-primary">
    <div class="fafa-card-header">Edit: {{ $editUser->nm_user }}</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('master.users.update', $editUser) }}" class="form-fafa row">
            @csrf
            @method('PUT')
            <div class="col-md-3 form-group"><label>Peran</label>
                <select name="otoritas" class="form-control">
                    @foreach(\App\Support\StaffAccess::geraiRoleOptions() as $code => $label)
                        <option value="{{ $code }}" @selected(old('otoritas', \App\Support\StaffAccess::code($editUser))===$code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group"><label>Gerai</label>
                <select name="branch_id" class="form-control">
                    <option value="">—</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" @selected((int)old('branch_id', $editUser->branch_id)===$b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group"><label>No. HP</label><input name="no_hp" class="form-control" maxlength="20" value="{{ old('no_hp', $editUser->no_hp) }}"></div>
            <div class="col-md-3 form-group"><label>Alamat</label><input name="alamat" class="form-control" maxlength="100" value="{{ old('alamat', $editUser->alamat) }}"></div>
            <div class="col-md-2 form-group"><label>Password baru</label><input type="password" name="pass" class="form-control" placeholder="Opsional"></div>
            <div class="col-md-3 align-self-end">
                <button class="btn-fafa btn-fafa-primary">Simpan</button>
                <a href="{{ route('master.users.index', request()->only('q')) }}" class="btn-fafa btn-fafa-link">Batal</a>
            </div>
        </form>
    </div>
</div>
@endif

<div class="fafa-card mb-3">
    <div class="fafa-card-header">Tambah pengguna</div>
    <div class="fafa-card-body">
        <form method="POST" action="{{ route('master.users.store') }}" class="form-fafa row">@csrf
            <div class="col-md-3 form-group"><label>Username</label><input name="nm_user" class="form-control" maxlength="30" required value="{{ old('nm_user') }}"></div>
            <div class="col-md-2 form-group"><label>Password</label><input type="password" name="pass" class="form-control" required></div>
            <div class="col-md-2 form-group"><label>No. HP</label><input name="no_hp" class="form-control" maxlength="20" value="{{ old('no_hp') }}"></div>
            <div class="col-md-3 form-group"><label>Alamat</label><input name="alamat" class="form-control" maxlength="100" value="{{ old('alamat') }}"></div>
            <div class="col-md-3 form-group"><label>Peran</label><select name="otoritas" class="form-control">@foreach(\App\Support\StaffAccess::geraiRoleOptions() as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3 form-group"><label>Gerai</label><select name="branch_id" class="form-control"><option value="">—</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div class="col-md-2 align-self-end"><button class="btn-fafa btn-fafa-primary w-100">Tambah</button></div>
        </form>
    </div>
</div>

<div class="fafa-card">
    <div class="fafa-card-body" style="padding:0">
        <table class="fafa-table">
            <thead><tr><th>User</th><th>Role</th><th>Gerai</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td>{{ $u->nm_user }}</td>
                    <td>{{ \App\Support\StaffAccess::label($u) }}</td>
                    <td>{{ $u->branch->name ?? $u->kd_toko ?? '—' }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('master.users.index', array_merge(request()->only('q'), ['edit' => $u->id_user])) }}" class="btn-fafa btn-fafa-sm btn-fafa-secondary">Edit</a>
                        <form method="POST" action="{{ route('master.users.destroy', $u) }}" class="d-inline" onsubmit="return confirm('Hapus user?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn-fafa btn-fafa-sm btn-fafa-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <x-fafa-pagination :paginator="$users" />
</div>
@endsection
