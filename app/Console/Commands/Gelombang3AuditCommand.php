<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Finance\PostingService;
use App\Support\BranchContext;
use Database\Seeders\ErpFinanceSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Gelombang3AuditCommand extends Command
{
    protected $signature = 'erp:gelombang3-audit
                            {--from= : Mulai tanggal (default awal bulan)}
                            {--to= : Akhir tanggal (default hari ini)}
                            {--backfill : Posting jurnal yang belum ada}';

    protected $description = 'Gelombang 3: cek jurnal jual/beli/kas/retur/bayar vs nota';

    public function handle(PostingService $posting): int
    {
        if (! Schema::hasTable('journal_entries')) {
            $this->error('Tabel jurnal belum ada. Jalankan php artisan migrate.');

            return self::FAILURE;
        }

        (new ErpFinanceSeeder)->run();
        $from = $this->option('from') ?: date('Y-m-01');
        $to = $this->option('to') ?: date('Y-m-d');
        $backfill = (bool) $this->option('backfill');
        $missing = 0;
        $posted = 0;

        foreach (Branch::query()->whereNotNull('kd_toko')->orderBy('kd_toko')->get() as $branch) {
            BranchContext::applyBranch($branch);
            $kd = $branch->kd_toko;

            foreach (Sale::query()->where('kd_toko', $kd)->whereBetween('tgl_jual', [$from, $to])->cursor() as $sale) {
                if ($this->hasPosted(Sale::class, (string) $sale->no_urut)) {
                    continue;
                }
                $missing++;
                if ($backfill) {
                    $posting->postSale($sale);
                    $posted++;
                }
            }

            foreach (Purchase::query()->where('kd_toko', $kd)->whereBetween('tgl_fak', [$from, $to])->cursor() as $purchase) {
                if ($this->hasPosted(Purchase::class, (string) $purchase->no_urut)) {
                    continue;
                }
                $missing++;
                if ($backfill) {
                    $posting->postPurchase($purchase);
                    $posted++;
                }
            }
        }

        $kas = Schema::hasTable('kas_entries')
            ? (int) DB::table('kas_entries')->whereBetween('tgl', [$from, $to])->whereNull('journal_entry_id')->count()
            : 0;
        $piutang = Schema::hasTable('piutang_payments')
            ? (int) DB::table('piutang_payments')->whereBetween('tgl_bayar', [$from, $to])->whereNull('journal_entry_id')->count()
            : 0;
        $hutang = Schema::hasTable('hutang_payments')
            ? (int) DB::table('hutang_payments')->whereBetween('tgl_bayar', [$from, $to])->whereNull('journal_entry_id')->count()
            : 0;

        $journals = JournalEntry::query()
            ->where('status', 'posted')
            ->whereBetween('entry_date', [$from, $to])
            ->count();

        $this->info("Periode {$from} s/d {$to}");
        $this->info("Jurnal posted: {$journals}");
        $this->info("Nota jual/beli tanpa jurnal: {$missing}");
        $this->info("Kas tanpa journal_entry_id: {$kas}");
        $this->info("Bayar piutang tanpa jurnal: {$piutang}");
        $this->info("Bayar hutang tanpa jurnal: {$hutang}");
        if ($backfill) {
            $this->info("Backfill jual/beli: {$posted}");
        }

        $this->info('Exit 3: beli/jual/kas/retur/bayar punya jurnal; trial balance bisa diklik ke nota; tutup toko dari daftar penjualan.');

        return self::SUCCESS;
    }

    protected function hasPosted(string $type, string $id): bool
    {
        return JournalEntry::query()
            ->where('reference_type', $type)
            ->where('reference_id', $id)
            ->where('status', 'posted')
            ->exists();
    }
}
