<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthenticateWarehouseToken
{
    public function handle(Request $request, Closure $next)
    {
        $plain = (string) $request->bearerToken();
        if ($plain === '') {
            return response()->json(['message' => 'Token gudang tidak ada.'], 401);
        }

        $row = DB::table('warehouse_api_tokens')->where('token_hash', hash('sha256', $plain))->first();
        if (! $row) {
            return response()->json(['message' => 'Token gudang tidak berlaku.'], 401);
        }

        DB::table('warehouse_api_tokens')->where('id', $row->id)->update(['last_used_at' => now()]);
        $request->attributes->set('warehouse_token_id', $row->id);

        return $next($request);
    }
}
