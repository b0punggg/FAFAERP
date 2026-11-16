<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Support\BranchContext;
use App\Support\StaffAccess;
use Closure;
use Illuminate\Http\Request;

class SetCompanyContext
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if (StaffAccess::isStoreLocked($user)) {
            StaffAccess::lockStore($user);

            return $next($request);
        }

        if (! session('company_id') && $user->company_id) {
            $branch = $user->branch_id
                ? Branch::with('warehouses')->find($user->branch_id)
                : null;

            BranchContext::set(
                $user->company_id,
                $user->branch_id,
                $branch?->kd_toko ?? $user->kd_toko,
                $branch?->warehouses->first()?->id
            );
        }

        if ($request->user()?->canSwitchContext() && $request->filled('switch_branch_id')) {
            $branch = Branch::with('warehouses')
                ->where('is_active', true)
                ->whereNotNull('kd_toko')
                ->find($request->integer('switch_branch_id'));
            if ($branch && $user->canAccessBranch($branch->id)) {
                BranchContext::applyBranch($branch);
            }
        }

        return $next($request);
    }
}
