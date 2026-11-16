<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/** Kolom & scope master legacy (pelanggan global, nama telp bervariasi). */
final class LegacyMasterSchema
{
    public static function hasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    public static function phoneColumn(string $table, string $modern, string $legacy): string
    {
        return self::hasColumn($table, $modern) ? $modern : $legacy;
    }

    /** Filter kd_toko hanya jika kolom ada (pelanggan legacy: biasanya tanpa kolom = data global). */
    public static function scopeByTokoIfSupported(Builder $query, string $table, ?string $kdToko): Builder
    {
        if ($kdToko && self::hasColumn($table, 'kd_toko')) {
            $query->where(function (Builder $q) use ($kdToko) {
                $q->where('kd_toko', $kdToko)->orWhereNull('kd_toko');
            });
        }

        return $query;
    }
}
