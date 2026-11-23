<?php

namespace App\Services\FieldSales;

use App\Models\ArSettlement;
use App\Models\SalesCommission;
use App\Models\SfaOrder;
use App\Models\SfaOutlet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SfaArService
{
    public function billed(int $outletId): float
    {
        return (float) SfaOrder::where('sfa_outlet_id', $outletId)
            ->whereIn('status', ['approved', 'delivered'])
            ->sum('total');
    }

    public function paid(int $outletId): float
    {
        return (float) ArSettlement::where('sfa_outlet_id', $outletId)->sum('amount');
    }

    public function openAmount(int $outletId): float
    {
        return max(0, $this->billed($outletId) - $this->paid($outletId));
    }

    public function agingAsOf(?string $asOf = null): array
    {
        $asOfDate = Carbon::parse($asOf ?: now()->toDateString())->startOfDay();
        $totals = ['bucket_0_30' => 0.0, 'bucket_31_60' => 0.0, 'bucket_61_plus' => 0.0];
        $outlets = [];

        foreach (SfaOutlet::orderBy('name')->get() as $outlet) {
            $open = $this->openDeliveredRemaining($outlet->id);
            $buckets = $this->bucketsForOutlet($outlet->id, $asOfDate);
            $row = [
                'outlet' => $outlet,
                'credit_limit' => (float) $outlet->credit_limit,
                'open' => $open,
                'over_limit' => $open > (float) $outlet->credit_limit && (float) $outlet->credit_limit > 0,
                'bucket_0_30' => $buckets[0],
                'bucket_31_60' => $buckets[1],
                'bucket_61_plus' => $buckets[2],
            ];
            $outlets[] = $row;
            $totals['bucket_0_30'] += $buckets[0];
            $totals['bucket_31_60'] += $buckets[1];
            $totals['bucket_61_plus'] += $buckets[2];
        }

        return ['as_of' => $asOfDate->toDateString(), 'outlets' => $outlets, 'totals' => $totals];
    }

    public function recordPayment(int $outletId, float $amount, string $paymentDate, ?int $salesUserId): ArSettlement
    {
        $open = $this->openAmount($outletId);
        if ($amount - $open > 0.01) {
            throw new \InvalidArgumentException('Nominal melebihi sisa piutang outlet (Rp '.number_format($open, 0, ',', '.').').');
        }

        $settlement = ArSettlement::create([
            'sfa_outlet_id' => $outletId,
            'amount' => $amount,
            'payment_date' => $paymentDate,
        ]);
        SalesCommission::create([
            'ar_settlement_id' => $settlement->id,
            'sales_user_id' => $salesUserId ?: 0,
            'amount' => round($amount * 0.02, 2),
            'status' => 'released',
        ]);

        return $settlement;
    }

    public function recentCommissions(int $limit = 15): Collection
    {
        return SalesCommission::query()->orderByDesc('id')->limit($limit)->get();
    }

    private function openDeliveredRemaining(int $outletId): float
    {
        $billed = (float) SfaOrder::where('sfa_outlet_id', $outletId)->where('status', 'delivered')->sum('total');
        $paid = $this->paid($outletId);

        return max(0, $billed - $paid);
    }

    /** @return array{0:float,1:float,2:float} */
    private function bucketsForOutlet(int $outletId, Carbon $asOf): array
    {
        $paidLeft = $this->paid($outletId);
        $buckets = [0.0, 0.0, 0.0];
        $orders = SfaOrder::where('sfa_outlet_id', $outletId)
            ->where('status', 'delivered')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($orders as $order) {
            $remain = (float) $order->total;
            $apply = min($remain, $paidLeft);
            $remain -= $apply;
            $paidLeft -= $apply;
            if ($remain <= 0.009) {
                continue;
            }
            $days = Carbon::parse($order->created_at)->startOfDay()->diffInDays($asOf);
            if ($days <= 30) {
                $buckets[0] += $remain;
            } elseif ($days <= 60) {
                $buckets[1] += $remain;
            } else {
                $buckets[2] += $remain;
            }
        }

        return $buckets;
    }
}
