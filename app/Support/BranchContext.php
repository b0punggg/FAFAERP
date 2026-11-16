<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class BranchContext
{
    public static function kdToko(): ?string
    {
        return session('branch_kd_toko') ?? session('id_toko');
    }

    public static function branchId(): ?int
    {
        return session('branch_id');
    }

    public static function companyId(): ?int
    {
        return session('company_id');
    }

    public static function warehouseId(): ?int
    {
        return session('warehouse_id');
    }

    public static function applyBranch(Branch $branch): void
    {
        $branch->loadMissing('warehouses');
        self::set(
            (int) $branch->company_id,
            (int) $branch->id,
            $branch->kd_toko,
            $branch->warehouses->first()?->id
        );
        $name = DB::table('toko')->where('kd_toko', $branch->kd_toko)->value('nm_toko');
        session(['nm_toko' => $name ?: $branch->name]);
    }

    public static function set(int $companyId, ?int $branchId, ?string $kdToko, ?int $warehouseId = null): void
    {
        session([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'branch_kd_toko' => $kdToko,
            'id_toko' => $kdToko,
            'warehouse_id' => $warehouseId,
        ]);
    }

    /** Tanggal kerja (legacy `tgl_set`) — default hari ini. */
    public static function businessDate(): string
    {
        $date = session('business_date') ?? session('tgl_set');

        return $date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
    }

    public static function setBusinessDate(string $date): void
    {
        session([
            'business_date' => $date,
            'tgl_set' => $date,
        ]);
    }
}
