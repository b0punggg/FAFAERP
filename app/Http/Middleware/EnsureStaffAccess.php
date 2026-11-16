<?php

namespace App\Http\Middleware;

use App\Support\StaffAccess;
use Closure;
use Illuminate\Http\Request;

class EnsureStaffAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $name = $request->route()?->getName();
        if (! StaffAccess::allowsRoute($name, $user)) {
            if (StaffAccess::isMovedToInventory($name)) {
                return response()->view('errors.moved-inventory', [
                    'inventoryUrl' => config('inventory.url'),
                ], 403);
            }
            abort(403, 'Akun ini tidak berhak membuka halaman tersebut.');
        }

        return $next($request);
    }
}
