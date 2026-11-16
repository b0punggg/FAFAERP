<?php

namespace App\Services\Sales;

use App\Models\DiscPromo;
use App\Models\DiscPromoDetail;
use App\Models\DiscTetap;
use App\Support\BranchContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DiscountCalculatorService
{
    public function forLine(string $kdBrg, int $kdSat, float $qty, float $unitPrice, ?string $saleDate = null, ?string $kdBayar = null): array
    {
        $saleDate = $saleDate ?: now()->toDateString();
        $kdToko = BranchContext::kdToko();
        $effective = $unitPrice;
        $discount = 0.0;
        $source = null;

        if (Schema::hasTable('disctetap')) {
            $fixed = $this->fixedDiscount($kdBrg, $kdSat, $qty, $unitPrice, $kdToko);
            if ($fixed['discount'] > 0) {
                return $fixed + ['source' => 'disctetap'];
            }
            if ($fixed['unit_price'] < $unitPrice) {
                $effective = $fixed['unit_price'];
                $discount = $fixed['discount'];
                $source = 'disctetap';
            }
        }

        if (Schema::hasTable('disc_promo') && Schema::hasTable('disc_promo_detail')) {
            $promo = $this->promoDiscount($kdBrg, $qty, $unitPrice, $kdToko, $saleDate, $kdBayar);
            if ($promo['discount'] > $discount) {
                return $promo + ['source' => 'disc_promo'];
            }
        }

        return [
            'discount' => round($discount, 2),
            'unit_price' => round($effective, 2),
            'source' => $source,
        ];
    }

    protected function fixedDiscount(string $kdBrg, int $kdSat, float $qty, float $unitPrice, ?string $kdToko): array
    {
        $rule = DiscTetap::query()
            ->where('kd_brg', $kdBrg)
            ->where('kd_sat', $kdSat)
            ->when($kdToko, fn ($q) => $q->where(fn ($q) => $q->where('kd_toko', $kdToko)->orWhereNull('kd_toko')))
            ->orderByRaw('CASE WHEN kd_toko IS NULL THEN 1 ELSE 0 END')
            ->first();

        if (! $rule) {
            return ['discount' => 0, 'unit_price' => $unitPrice];
        }

        if ($rule->lim_jual > 0 && $qty < (float) $rule->lim_jual) {
            return ['discount' => 0, 'unit_price' => $unitPrice];
        }

        $special = (float) $rule->hrg_jual;
        $effective = $special > 0 ? min($unitPrice, $special) : $unitPrice;

        return [
            'discount' => max(0, ($unitPrice - $effective) * $qty),
            'unit_price' => $effective,
        ];
    }

    protected function promoDiscount(string $kdBrg, float $qty, float $unitPrice, ?string $kdToko, string $saleDate, ?string $kdBayar = null): array
    {
        if (! $kdToko) {
            return ['discount' => 0, 'unit_price' => $unitPrice];
        }

        $date = Carbon::parse($saleDate);

        $promos = DiscPromo::query()
            ->where('kd_toko', $kdToko)
            ->whereDate('tgl_awal', '<=', $date)
            ->whereDate('tgl_akhir', '>=', $date)
            ->orderByDesc('tgl_akhir')
            ->get();

        $lineSubtotal = $unitPrice * $qty;
        $bestDiscount = 0.0;

        foreach ($promos as $promo) {
            if (! $this->promoMatchesPayment($promo, $kdBayar)) {
                continue;
            }

            $detailQuery = DiscPromoDetail::query()
                ->where('no_promo', $promo->no_promo)
                ->where('kd_toko', $kdToko)
                ->where('kd_brg', '!=', '');

            $hasSkuList = (clone $detailQuery)->exists();
            $detail = (clone $detailQuery)->where('kd_brg', $kdBrg)->first();

            if ($hasSkuList && ! $detail) {
                continue;
            }

            $discRupiah = $detail ? (float) $detail->disc_rupiah : (float) $promo->disc_rupiah;
            $discPersen = $detail ? (float) $detail->disc_persen : (float) $promo->disc_persen;
            if ($detail && $discRupiah <= 0 && $discPersen <= 0) {
                $discRupiah = (float) $promo->disc_rupiah;
                $discPersen = (float) $promo->disc_persen;
            }

            $fromPromo = $discRupiah * $qty + ($lineSubtotal * $discPersen / 100);
            $bestDiscount = max($bestDiscount, $fromPromo);
        }

        if ($bestDiscount <= 0) {
            return ['discount' => 0, 'unit_price' => $unitPrice];
        }

        $effective = max(0, $unitPrice - ($bestDiscount / max($qty, 1)));

        return [
            'discount' => min($bestDiscount, $lineSubtotal),
            'unit_price' => $effective,
        ];
    }

    protected function promoMatchesPayment(DiscPromo $promo, ?string $kdBayar): bool
    {
        if (! Schema::hasColumn('disc_promo', 'kd_bayar')) {
            return true;
        }

        $channel = strtoupper(trim((string) $promo->kd_bayar));
        if ($channel === '') {
            return true;
        }

        return $channel === strtoupper(trim((string) $kdBayar));
    }
}
