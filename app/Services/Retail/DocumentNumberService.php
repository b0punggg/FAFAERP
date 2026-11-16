<?php

namespace App\Services\Retail;

use App\Models\Sale;
use App\Models\SaleDraft;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentNumberService
{
    public function suggestSale(?string $kdToko, int $userId): string
    {
        $kdToko = $kdToko ?: '';
        $userId = max(0, $userId);
        $n = $this->nextCounter($kdToko);
        $guard = 0;
        do {
            $number = $this->formatSale($kdToko, $userId, $n);
            $n++;
            $guard++;
        } while ($guard < 5000 && $this->saleNumberTaken($kdToko, $number));

        return $number;
    }

    public function consumeSale(?string $kdToko, string $noFak, int $userId): void
    {
        if (! $kdToko || ! Schema::hasTable('seting')) {
            return;
        }

        $current = $this->storedCounter($kdToko);
        $parsed = $this->counterFromNumber($noFak, $userId);
        $counter = $parsed > 0 ? $parsed : $current;
        if ($counter >= $current) {
            $this->writeCounter($kdToko, $counter + 1);
        }
    }

    public function unfinishedPurchase(?string $kdToko): ?object
    {
        if (! $kdToko || ! Schema::hasTable('beli_brg') || ! Schema::hasTable('beli_bay') || ! Schema::hasColumn('beli_bay', 'pay')) {
            return null;
        }

        $latest = DB::table('beli_brg')
            ->where('kd_toko', $kdToko)
            ->orderByDesc('no_urut')
            ->first(['no_fak', 'tgl_fak', 'kd_sup']);
        if (! $latest || $latest->no_fak === '') {
            return null;
        }

        $pay = DB::table('beli_bay')
            ->where('kd_toko', $kdToko)
            ->where('no_fak', $latest->no_fak)
            ->orderByDesc('no_urut')
            ->value('pay');
        if ($pay === null || (int) $pay === 1) {
            return null;
        }

        $name = DB::table('beli')
            ->where('kd_toko', $kdToko)
            ->where('no_fak', $latest->no_fak)
            ->value('nm_sup');

        return (object) [
            'no_fak' => $latest->no_fak,
            'tgl_fak' => $latest->tgl_fak,
            'kd_sup' => $latest->kd_sup,
            'nm_sup' => $name ?: '',
        ];
    }

    protected function formatSale(string $kdToko, int $userId, int $counter): string
    {
        $date = BranchContext::businessDate();
        $stamp = date('dmy', strtotime($date) ?: time());

        return 'FAFA'.$this->storeNo($kdToko).'.'.$stamp.'-'.$userId.$counter;
    }

    protected function storeNo(string $kdToko): string
    {
        $parts = explode('-', $kdToko);

        return $parts[1] ?? '0';
    }

    protected function nextCounter(string $kdToko): int
    {
        $kode = $this->storedCounter($kdToko);

        return $kode > 1000000000 ? 1 : max(1, $kode);
    }

    protected function storedCounter(string $kdToko): int
    {
        if ($kdToko === '' || ! Schema::hasTable('seting')) {
            return 1;
        }

        $kode = DB::table('seting')->where('nm_per', $kdToko)->orderBy('no_urut')->value('kode');
        if ($kode !== null) {
            return (int) $kode;
        }

        $next = max(1, $this->maxUsedCounter($kdToko) + 1);
        DB::table('seting')->insert([
            'nm_per' => $kdToko,
            'kode' => $next,
        ]);

        return $next;
    }

    protected function writeCounter(string $kdToko, int $kode): void
    {
        $updated = DB::table('seting')->where('nm_per', $kdToko)->update(['kode' => $kode]);
        if ($updated === 0 && ! DB::table('seting')->where('nm_per', $kdToko)->exists()) {
            DB::table('seting')->insert([
                'nm_per' => $kdToko,
                'kode' => $kode,
            ]);
        }
    }

    protected function counterFromNumber(string $noFak, int $userId): int
    {
        $suffix = (string) strrchr($noFak, '-');
        $suffix = ltrim($suffix, '-');
        $user = (string) $userId;
        if ($user !== '' && str_starts_with($suffix, $user) && strlen($suffix) > strlen($user)) {
            return (int) substr($suffix, strlen($user));
        }

        return 0;
    }

    protected function saleNumberTaken(string $kdToko, string $number): bool
    {
        if (Sale::query()->where('kd_toko', $kdToko)->where('no_fak', $number)->exists()) {
            return true;
        }

        return Schema::hasTable('sale_drafts')
            && SaleDraft::query()->where('kd_toko', $kdToko)->where('no_fak', $number)->exists();
    }

    protected function maxUsedCounter(string $kdToko): int
    {
        $userIds = DB::table('pemakai')->pluck('id_user')
            ->map(fn ($id) => (string) $id)
            ->sortByDesc(fn ($id) => strlen($id))
            ->values();

        $max = 0;
        DB::table('jual')
            ->where('kd_toko', $kdToko)
            ->where('no_fak', 'like', 'FAFA%-%')
            ->select('no_urut', 'no_fak')
            ->chunkById(1000, function ($rows) use ($userIds, &$max) {
                foreach ($rows as $row) {
                    $suffix = ltrim((string) strrchr($row->no_fak, '-'), '-');
                    if ($suffix === '' || ! ctype_digit($suffix)) {
                        continue;
                    }
                    foreach ($userIds as $id) {
                        if ($id !== '' && str_starts_with($suffix, $id) && strlen($suffix) > strlen($id)) {
                            $max = max($max, (int) substr($suffix, strlen($id)));
                            break;
                        }
                    }
                }
            }, 'no_urut');

        return $max;
    }
}
