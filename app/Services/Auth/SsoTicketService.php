<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SsoTicketService
{
    public function issue(string $username, string $direction, ?string $nextPath = null): string
    {
        $this->ensureTable();
        $token = bin2hex(random_bytes(24));
        DB::table('sso_tickets')->insert([
            'token' => $token,
            'username' => $username,
            'direction' => $direction,
            'next_path' => $this->safePath($nextPath),
            'expires_at' => now()->addMinutes(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $token;
    }

    public function consume(string $token, string $direction): ?object
    {
        $this->ensureTable();
        $row = DB::table('sso_tickets')
            ->where('token', $token)
            ->where('direction', $direction)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
        if (! $row) {
            return null;
        }
        $ok = DB::table('sso_tickets')
            ->where('id', $row->id)
            ->whereNull('used_at')
            ->update(['used_at' => now(), 'updated_at' => now()]);

        return $ok ? $row : null;
    }

    public function inventoryUrl(?string $path = '/pantauan.html'): string
    {
        $base = rtrim((string) config('inventory.url'), '/');
        $path = $this->safePath($path) ?: '/pantauan.html';

        return $base.$path;
    }

    public function ssoGudangUrl(?string $next = '/pantauan.html'): string
    {
        return rtrim((string) config('inventory.url'), '/').'/sso.html?ticket='.$this->issue(
            (string) (auth()->user()?->nm_user ?? session('nm_user')),
            'gudang',
            $next
        );
    }

    protected function safePath(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '..')) {
            return null;
        }

        return substr($path, 0, 255);
    }

    protected function ensureTable(): void
    {
        if (Schema::hasTable('sso_tickets')) {
            return;
        }
        Schema::create('sso_tickets', function ($table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('username', 100);
            $table->string('direction', 16);
            $table->string('next_path', 255)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }
}
