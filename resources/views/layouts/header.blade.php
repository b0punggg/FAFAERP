<header class="top-header">
    <div class="header-content">
        <div class="header-logo">
            <button type="button" class="btn-icon-menu" id="menuToggle" aria-label="Menu" aria-expanded="false" aria-controls="sidebar">
                <i class="fa fa-bars"></i>
            </button>
            <div class="logo-mark">F</div>
            <div>
                <span class="logo-text">FAFA ERP</span>
                <span class="logo-sub">Retail &amp; Multi-Gerai</span>
            </div>
        </div>
        <div class="header-actions">
            @if(($switchBranches ?? collect())->isNotEmpty())
                <form method="POST" action="{{ route('context.branch') }}" class="header-switch">
                    @csrf
                    <label class="sr-only" for="branch_id">Gerai</label>
                    <select id="branch_id" name="branch_id" class="header-select" onchange="this.form.submit()">
                        @foreach($switchBranches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) session('branch_id') === (int) $branch->id)>{{ $branch->label }}</option>
                        @endforeach
                    </select>
                </form>
            @elseif(session('nm_toko'))
                <span class="header-chip hide-mobile"><i class="fa fa-store"></i> {{ session('nm_toko') }}</span>
            @endif
            @if(session('nm_user'))
                <span class="header-chip" title="{{ session('nm_user') }}"><i class="fa fa-user-circle"></i> <span class="chip-label">{{ session('nm_user') }}</span></span>
            @endif
            <a class="header-chip header-sso" href="{{ route('sso.gudang', ['next' => '/pantauan.html']) }}" title="Buka gudang tanpa login ulang (username harus sama)"><i class="fa fa-cubes"></i> <span class="chip-label">Gudang</span></a>
        </div>
    </div>
</header>
