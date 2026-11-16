<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaleDeletionHistoryController extends Controller
{
    public function index()
    {
        abort_unless(Schema::hasTable('sale_deletion_history'), 501);

        $kdToko = BranchContext::kdToko();
        $rows = DB::table('sale_deletion_history')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.sales.deletion-history', compact('rows'));
    }
}
