<?php

namespace App\Services\Sales;

use App\Models\Member;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MemberPointService
{
    /** Legacy: floor(total bayar / 50000) */
    public const RUPIAH_PER_POIN = 50000;

    public function pointsEarnedForTotal(float $totBayar): float
    {
        return (float) floor($totBayar / self::RUPIAH_PER_POIN);
    }

    /** 1 poin = Rp 1 diskon (legacy redeem hidden value) */
    public function redeemValueRupiah(float $poinRedeem): float
    {
        return max(0, $poinRedeem);
    }

    public function applySalePoints(
        string $kdMember,
        string $noFak,
        string $tglJual,
        float $poinEarned,
        float $poinRedeem,
        ?string $kdToko = null
    ): void {
        if (! Schema::hasTable('member') || $kdMember === '') {
            return;
        }

        $kdToko = $kdToko ?: BranchContext::kdToko();
        $member = Member::where('kd_member', $kdMember)->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))->first();
        if (! $member) {
            return;
        }

        DB::transaction(function () use ($member, $noFak, $tglJual, $poinEarned, $poinRedeem, $kdToko) {
            $saldo = (float) $member->poin;

            if ($poinRedeem > 0) {
                $saldo = max(0, $saldo - $poinRedeem);
                $this->history($member->kd_member, $noFak, $tglJual, 0, $poinRedeem, $saldo, "Penukaran poin transaksi {$noFak}", $kdToko);
            }

            if ($poinEarned > 0) {
                $saldo += $poinEarned;
                $this->history($member->kd_member, $noFak, $tglJual, $poinEarned, 0, $saldo, "Transaksi penjualan {$noFak}", $kdToko);
            }

            $member->update(['poin' => $saldo]);
        });
    }

    public function exchangePoints(string $kdMember, float $poinTukar, string $keterangan, ?string $kdToko = null): void
    {
        $kdToko = $kdToko ?: BranchContext::kdToko();
        abort_unless($kdToko && $poinTukar > 0, 422);

        $member = Member::where('kd_member', $kdMember)->where('kd_toko', $kdToko)->firstOrFail();
        abort_if((float) $member->poin < $poinTukar, 422, 'Poin tidak cukup.');

        DB::transaction(function () use ($member, $poinTukar, $keterangan, $kdToko) {
            $saldo = (float) $member->poin - $poinTukar;
            $noRef = 'TUKAR-'.now()->format('YmdHis');
            $this->history($member->kd_member, $noRef, now()->toDateString(), 0, $poinTukar, $saldo, 'Penukaran poin — '.$keterangan, $kdToko);
            $member->update(['poin' => $saldo]);
        });
    }

    protected function history(
        string $kdMember,
        string $noFak,
        string $tgl,
        float $masuk,
        float $keluar,
        float $saldo,
        string $ket,
        ?string $kdToko
    ): void {
        if (! Schema::hasTable('member_poin_history')) {
            return;
        }

        DB::table('member_poin_history')->insert([
            'kd_member' => $kdMember,
            'no_fakjual' => $noFak,
            'tgl_transaksi' => $tgl,
            'poin_masuk' => $masuk,
            'poin_keluar' => $keluar,
            'poin_saldo' => $saldo,
            'keterangan' => $ket,
            'kd_toko' => $kdToko,
            'created_at' => now(),
        ]);
    }
}
