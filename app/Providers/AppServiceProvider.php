<?php

namespace App\Providers;

use App\Models\Branch;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Paginator::defaultView('vendor.pagination.fafa');
        Paginator::defaultSimpleView('vendor.pagination.fafa-simple');

        View::composer('layouts.header', function ($view) {
            $branches = collect();
            $user = auth()->user();
            if ($user && $user->canSwitchContext()) {
                $rows = Branch::query()
                    ->where('is_active', true)
                    ->whereNotNull('kd_toko')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
                $names = $rows->isEmpty()
                    ? collect()
                    : DB::table('toko')->whereIn('kd_toko', $rows->pluck('kd_toko'))->pluck('nm_toko', 'kd_toko');
                $branches = $rows->map(fn (Branch $branch) => (object) [
                    'id' => $branch->id,
                    'label' => $names[$branch->kd_toko] ?? $branch->name,
                ]);
            }
            $view->with('switchBranches', $branches);
        });
    }
}

