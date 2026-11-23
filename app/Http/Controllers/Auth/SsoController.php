<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\SsoTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SsoController extends Controller
{
    public function __construct(protected SsoTicketService $tickets)
    {
    }

    public function toGudang(Request $request)
    {
        $next = $request->get('next', '/pantauan.html');
        $user = $request->user();
        $username = (string) ($user?->nm_user ?? '');
        if ($username !== '' && Schema::hasTable('users')) {
            $exists = \Illuminate\Support\Facades\DB::table('users')
                ->where('username', $username)
                ->where('aktif', 1)
                ->exists();
            if (! $exists) {
                return redirect()
                    ->back()
                    ->with('error', 'Belum ada akun gudang dengan username '.$username.'. Buat user inventory dengan username yang sama.');
            }
        }

        return redirect()->away($this->tickets->ssoGudangUrl($next));
    }

    public function fromGudang(Request $request, LoginController $login)
    {
        $ticket = (string) $request->query('ticket', '');
        $row = $this->tickets->consume($ticket, 'kasir');
        if (! $row) {
            return redirect()->route('login')->with('error', 'Tautan SSO kedaluwarsa. Login kasir biasa.');
        }
        $user = User::where('nm_user', $row->username)->first();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Tidak ada pemakai kasir '.$row->username.'.');
        }
        $login->establishSession($user);

        return redirect()->route('dashboard');
    }
}
