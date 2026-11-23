<?php

namespace App\Services\Retail;

use App\Models\DailySettlement;
use App\Models\Sale;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;

class DailySettlementService
{
    public function posTotals(?string $kdToko, string $date): array
    {
        $sales = Sale::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereDate('tgl_jual', $date)
            ->get();

        $cash = 0.0;
        $qris = 0.0;
        $edc = 0.0;
        $piutang = 0.0;
        foreach ($sales as $sale) {
            $pay = strtoupper((string) ($sale->kd_bayar ?: 'TUNAI'));
            $tunai = (float) $sale->byr_tunai;
            if ($tunai <= 0 && $pay !== 'TEMPO') {
                $tunai = (float) $sale->tot_bayar;
            }
            if ($pay === 'QRIS') {
                $qris += $tunai;
            } elseif (in_array($pay, ['BNI', 'EDC', 'DEBIT'], true)) {
                $edc += $tunai;
            } else {
                $cash += $tunai;
            }
            $piutang += (float) $sale->byr_hutang;
        }

        $kasIn = 0.0;
        $kasOut = 0.0;
        if ($kdToko) {
            $kasIn = (float) DB::table('kas_entries')
                ->where('kd_toko', $kdToko)
                ->whereDate('tgl', $date)
                ->where('direction', 'in')
                ->sum('amount');
            $kasOut = (float) DB::table('kas_entries')
                ->where('kd_toko', $kdToko)
                ->whereDate('tgl', $date)
                ->where('direction', 'out')
                ->sum('amount');
        }

        return [
            'nota' => $sales->count(),
            'pos_cash_total' => round($cash + $kasIn - $kasOut, 2),
            'qris_total' => round($qris, 2),
            'edc_total' => round($edc, 2),
            'piutang' => round($piutang, 2),
            'kas_in' => round($kasIn, 2),
            'kas_out' => round($kasOut, 2),
        ];
    }

    public function today(?int $branchId = null, ?string $date = null): ?DailySettlement
    {
        $branchId = $branchId ?? BranchContext::branchId();
        $date = $date ?? date('Y-m-d');
        if (! $branchId) {
            return null;
        }

        return DailySettlement::query()
            ->where('branch_id', $branchId)
            ->whereDate('settlement_date', $date)
            ->first();
    }
}
