<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Jobs\ComputeRopAlertsJob;
use App\Models\RopAlert;

class RopAlertController extends Controller
{
    public function index()
    {
        $alerts = RopAlert::query()
            ->leftJoin('branches', 'branches.id', '=', 'rop_alerts.branch_id')
            ->leftJoin('products', 'products.id', '=', 'rop_alerts.product_id')
            ->orderByDesc('rop_alerts.alerted_at')
            ->select('rop_alerts.*', 'branches.name as branch_name', 'branches.kd_toko', 'products.name as product_name', 'products.sku')
            ->paginate(30);

        return view('admin.procurement.rop-alerts', compact('alerts'));
    }

    public function refresh()
    {
        ComputeRopAlertsJob::dispatchSync();

        return back()->with('success', 'ROP dihitung ulang.');
    }
}
