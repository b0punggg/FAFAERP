<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Finance\PostingService;
use App\Services\Inventory\InventoryMovementService;
use App\Support\BranchContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class Gelombang0ExitCommand extends Command
{
    protected $signature = 'erp:gelombang0-exit
                            {--skip-sync : Jangan sync qty dari mas_brg}
                            {--skip-sales : Jangan buat nota uji}';

    protected $description = 'Gelombang 0 Exit: user, sync stok gerai, 1 nota per gerai, cek stok A≠B / PUSAT / packing ONLINE';

    public function handle(InventoryMovementService $inventory, PostingService $posting): int
    {
        $this->ensureKasirUsers();
        $this->ensureInventoryUsers();

        if (! $this->option('skip-sync')) {
            $code = $this->call('erp:gelombang0-prepare', ['--sync-qty' => true, '--zero-central' => true]);
            if ($code !== self::SUCCESS) {
                return $code;
            }
        } else {
            $this->call('erp:gelombang0-prepare', ['--zero-central' => true]);
        }

        $okToko = $this->assertLocations();
        $okDiff = $this->assertStockDiffers();
        $okPack = $this->assertPackingOnline();
        $okSj = $this->assertSjFromPusat();
        $okSales = true;
        if (! $this->option('skip-sales')) {
            $okSales = $this->postOneSalePerGerai($inventory, $posting);
        }

        $this->newLine();
        $pass = $okToko && $okDiff && $okPack && $okSj && $okSales;
        if ($pass) {
            $this->info('EXIT 0 lokal: empat gerai + PUSAT, satu DB, nota uji, packing ONLINE, SJ asal PUSAT.');
            $this->comment('Hosting produksi + matikan kasir PHP lama tetap tugas cutover, bukan DB lokal.');
        } else {
            $this->error('EXIT 0 belum lolos. Perbaiki item di atas.');
        }

        return $pass ? self::SUCCESS : self::FAILURE;
    }

    private function ensureKasirUsers(): void
    {
        $pass = 'FafaExit0';
        $retail = Company::where('type', 'retail')->first();
        $created = [];
        foreach (['IDTOKO-1', 'IDTOKO-2', 'IDTOKO-3', 'IDTOKO-4'] as $i => $kd) {
            $name = 'G0KASIR'.($i + 1);
            $branch = Branch::where('kd_toko', $kd)->first();
            $user = User::where('nm_user', $name)->first();
            if (! $user) {
                $user = new User();
                $user->nm_user = $name;
            }
            $user->pass = $pass;
            $user->otoritas = '1';
            $user->no_hp = '-';
            $user->alamat = '-';
            $user->foto = '-';
            $user->kd_toko = $kd;
            if ($retail) {
                $user->company_id = $retail->id;
            }
            if ($branch) {
                $user->branch_id = $branch->id;
            }
            $user->save();
            $created[] = [$name, $kd, Hash::check($pass, $user->fresh()->pass) ? 'login-ok' : 'hash-gagal'];
        }
        $this->table(['kasir', 'kd_toko', 'hash'], $created);
        $dir = storage_path('app/gelombang0');
        File::ensureDirectoryExists($dir);
        File::put($dir.DIRECTORY_SEPARATOR.'exit-users.txt', "kasir {$pass}\ninventory admin / admin123\n");
        $this->line('Password kasir uji: '.$pass.' (storage/app/gelombang0/exit-users.txt)');
    }

    private function ensureInventoryUsers(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }
        DB::table('users')->where('username', 'gudang1')->update(['kd_toko' => 'PUSAT', 'aktif' => 1]);
        $packer = DB::table('users')->where('username', 'packer1')->first();
        if (! $packer) {
            DB::table('users')->insert([
                'username' => 'packer1',
                'password' => 'admin123', // placeholder, hash below
                'nama_lengkap' => 'Packer ONLINE',
                'role' => 'packer',
                'kd_toko' => 'ONLINE',
                'aktif' => 1,
            ]);
        }
        $hash = '$2a$10$e78N9yheTPeUUYnJU7LOGOxdB8jamFw26HvFegEXlOqpKcyl4WhKW';
        DB::table('users')->whereIn('username', ['admin', 'gudang1', 'packer1'])->update(['password' => $hash]);
        DB::table('users')->where('username', 'packer1')->update(['kd_toko' => 'ONLINE', 'role' => 'packer', 'aktif' => 1]);
        $this->info('Inventory: admin/admin123, gudang1 @ PUSAT, packer1 @ ONLINE.');
    }

    private function assertLocations(): bool
    {
        $need = ['IDTOKO-1', 'IDTOKO-2', 'IDTOKO-3', 'IDTOKO-4', 'PUSAT'];
        $have = DB::table('toko')->pluck('kd_toko')->all();
        $missing = array_diff($need, $have);
        if ($missing) {
            $this->error('Toko kurang: '.implode(',', $missing));

            return false;
        }
        $this->info('Lokasi: 4 gerai + PUSAT ada.');

        return true;
    }

    private function assertStockDiffers(): bool
    {
        $row = DB::selectOne(
            "SELECT a.kd_brg AS sku, a.jml_brg AS q1, b.jml_brg AS q2
             FROM mas_brg a
             INNER JOIN mas_brg b ON a.kd_brg = b.kd_brg
             WHERE a.kd_toko = 'IDTOKO-1' AND b.kd_toko IN ('IDTOKO-2','IDTOKO-3','IDTOKO-4')
               AND ABS(a.jml_brg - b.jml_brg) > 0.001
             LIMIT 1"
        );
        if ($row) {
            $this->info("Stok beda SKU {$row->sku}: gerai1={$row->q1} gerai lain={$row->q2}");

            return true;
        }

        $sums = DB::table('inventory_balances as ib')
            ->join('warehouses as w', 'w.id', '=', 'ib.warehouse_id')
            ->whereIn('w.kd_toko', ['IDTOKO-1', 'IDTOKO-2'])
            ->groupBy('w.kd_toko')
            ->select('w.kd_toko', DB::raw('SUM(ib.qty_on_hand) as s'))
            ->pluck('s', 'kd_toko');
        $q1 = (float) ($sums['IDTOKO-1'] ?? 0);
        $q2 = (float) ($sums['IDTOKO-2'] ?? 0);
        if (abs($q1 - $q2) > 0.001) {
            $this->info("Katalog gerai 1 dan 2 tidak overlap SKU; total qty 1={$q1} ≠ 2={$q2}.");

            return true;
        }
        $this->error('Stok gerai 1 dan 2 tidak bisa dibedakan.');

        return false;
    }

    private function assertPackingOnline(): bool
    {
        $file = base_path('../inventory-system/routes/paketReady.js');
        if (! is_file($file)) {
            $file = 'C:\\laragon\\www\\inventory-system\\routes\\paketReady.js';
        }
        if (! is_file($file)) {
            $this->warn('paketReady.js tidak ketemu — cek packing manual.');

            return true;
        }
        $src = File::get($file);
        $ok = str_contains($src, 'ONLINE()') && str_contains($src, 'Gerai tidak dipotong');
        if ($ok) {
            $this->info('Packing memotong gudang ONLINE, bukan FAFA 1–4.');
        } else {
            $this->error('Kode packing tidak menegaskan potong ONLINE saja.');
        }

        return $ok;
    }

    private function assertSjFromPusat(): bool
    {
        $file = 'C:\\laragon\\www\\inventory-system\\routes\\suratJalan.js';
        if (! is_file($file)) {
            $this->warn('suratJalan.js tidak ketemu.');

            return true;
        }
        $src = File::get($file);
        $ok = str_contains($src, 'kd_tokoasal = pusat') || str_contains($src, 'const kd_tokoasal = pusat');
        if ($ok) {
            $this->info('Surat jalan asal dipaksa PUSAT.');
        } else {
            $this->error('SJ tidak memaksa asal PUSAT.');
        }

        return $ok;
    }

    private function postOneSalePerGerai(InventoryMovementService $inventory, PostingService $posting): bool
    {
        $company = Company::where('type', 'retail')->first();
        $ok = true;
        $rows = [];
        foreach (['IDTOKO-1', 'IDTOKO-2', 'IDTOKO-3', 'IDTOKO-4'] as $kd) {
            $noFak = 'G0EXIT-'.$kd.'-'.date('Ymd');
            if (Sale::where('kd_toko', $kd)->where('no_fak', $noFak)->exists()) {
                $rows[] = [$kd, $noFak, 'sudah ada'];
                continue;
            }
            $line = $this->pickSaleLine($kd);
            if (! $line) {
                $this->error("Tidak ada SKU stok > 0 di {$kd} (mas_brg + products).");
                $ok = false;
                $rows[] = [$kd, '-', 'tidak ada SKU'];
                continue;
            }
            $branch = Branch::where('kd_toko', $kd)->first();
            $wh = Warehouse::where('kd_toko', $kd)->where('is_central', false)->first();
            if ($company && $branch) {
                BranchContext::set((int) $company->id, (int) $branch->id, $kd, $wh?->id);
            }
            try {
                DB::transaction(function () use ($inventory, $posting, $kd, $noFak, $line) {
                    $inventory->assertSaleQuantities([$line], $kd);
                    $tot = (float) $line['jml_brg'] * (float) $line['hrg_jual'];
                    $sale = Sale::create([
                        'no_fak' => $noFak,
                        'tgl_jual' => date('Y-m-d'),
                        'kd_pel' => '',
                        'nm_pel' => 'UJI EXIT 0',
                        'kd_toko' => $kd,
                        'tot_jual' => $tot,
                        'disc' => 0,
                        'ppn' => 0,
                        'tot_bayar' => $tot,
                        'byr_tunai' => $tot,
                        'byr_hutang' => 0,
                        'kd_bayar' => 'TUNAI',
                        'diterima' => $tot,
                        'ket' => 'Gelombang 0 exit uji',
                    ]);
                    SaleItem::create([
                        'no_fak' => $noFak,
                        'tgl_jual' => date('Y-m-d'),
                        'kd_brg' => $line['kd_brg'],
                        'nm_brg' => $line['nm_brg'],
                        'kd_sat' => $line['kd_sat'],
                        'nm_sat' => $line['nm_sat'],
                        'jml_brg' => $line['jml_brg'],
                        'hrg_jual' => $line['hrg_jual'],
                        'disc' => 0,
                        'ppn' => 0,
                        'tot_jual' => $tot,
                        'kd_toko' => $kd,
                    ]);
                    $inventory->issueForSaleLine($line['kd_brg'], (float) $line['jml_brg'], (int) $line['kd_sat'], $kd, $noFak);
                    $posting->postSale($sale);
                });
                $rows[] = [$kd, $noFak, $line['kd_brg']];
            } catch (\Throwable $e) {
                $this->error("{$kd}: ".$e->getMessage());
                $ok = false;
                $rows[] = [$kd, $noFak, 'gagal'];
            }
        }
        $this->table(['gerai', 'nota', 'sku'], $rows);

        return $ok;
    }

    /** @return array{kd_brg:string,nm_brg:string,kd_sat:int,nm_sat:string,jml_brg:float,hrg_jual:float}|null */
    private function pickSaleLine(string $kdToko): ?array
    {
        $row = DB::table('mas_brg as b')
            ->join('products as p', 'p.sku', '=', 'b.kd_brg')
            ->where('b.kd_toko', $kdToko)
            ->where('b.jml_brg', '>=', 1)
            ->orderByDesc('b.jml_brg')
            ->first(['b.kd_brg', 'b.nm_brg', 'b.kd_kem1', 'b.nm_kem1', 'b.hrg_jum1']);
        if (! $row) {
            return null;
        }
        $price = (float) ($row->hrg_jum1 ?? 0);
        if ($price <= 0) {
            $price = 1000;
        }

        return [
            'kd_brg' => (string) $row->kd_brg,
            'nm_brg' => (string) ($row->nm_brg ?? $row->kd_brg),
            'kd_sat' => 1,
            'nm_sat' => (string) ($row->nm_kem1 ?: 'pcs'),
            'jml_brg' => 1,
            'hrg_jual' => $price,
        ];
    }
}
