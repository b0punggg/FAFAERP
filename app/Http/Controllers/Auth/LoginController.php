<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nm_user' => 'required|string',
            'pass' => 'required|string',
        ]);

        $user = User::where('nm_user', $request->nm_user)->first();

        if ($user && Hash::check($request->pass, $user->pass)) {
            $this->establishSession($user);

            return redirect()->route('dashboard');
        }

        return redirect()->route('login')->with('error', 'Nama User atau Password tidak valid!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }

    public function establishSession(User $user): void
    {
        Auth::login($user);

        session([
            'nm_user' => $user->nm_user,
            'id_user' => $user->id_user,
            'masuk' => 'ok',
            'kodepemakai' => $user->otoritas,
            'foto' => $user->foto,
            'tgl_set' => date('Y-m-d'),
        ]);

        $companyId = $user->company_id;
        $branchId = $user->branch_id;
        $kdToko = $user->kd_toko;
        $branch = null;

        if ($branchId) {
            $branch = Branch::with('warehouses')->find($branchId);
            $kdToko = $branch?->kd_toko ?? $kdToko;
            $companyId = $branch?->company_id ?? $companyId;
        } elseif ($kdToko && ! $companyId) {
            $branch = Branch::with('warehouses')->where('kd_toko', $kdToko)->first();
            if ($branch) {
                $companyId = $branch->company_id;
                $branchId = $branch->id;
            }
        }

        if (! $companyId) {
            $companyId = Company::where('type', 'retail')->value('id');
        }

        BranchContext::set(
            (int) $companyId,
            $branchId ? (int) $branchId : null,
            $kdToko,
            $branch?->warehouses->first()?->id ?? null
        );

        if ($user->store) {
            session(['nm_toko' => $user->store->nm_toko]);
        } elseif ($branch ?? null) {
            session(['nm_toko' => $branch->name]);
        } else {
            session(['nm_toko' => 'NONE']);
        }
    }
}

