<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;

class StaffAccess
{
    public static function options(): array
    {
        return [
            '1' => 'Operator / kasir',
            '3' => 'Kepala toko',
            '2' => 'Administrator',
            '4' => 'Digital',
            '5' => 'Sales TO',
            '6' => 'Finance',
            '7' => 'Direksi',
        ];
    }

    public static function role(?User $user): string
    {
        $code = strtolower(trim((string) ($user?->otoritas ?? '')));

        return match (true) {
            in_array($code, ['1', 'operator', 'kasir', 'user'], true) => 'operator',
            in_array($code, ['3', 'kepala', 'kepala toko', 'kepala_toko'], true) => 'kepala',
            in_array($code, ['2', 'admin', 'administrator'], true) => 'admin',
            in_array($code, ['4', 'digital'], true) => 'digital',
            in_array($code, ['5', 'to', 'sales to', 'sales_to'], true) => 'to',
            in_array($code, ['6', 'finance'], true) => 'finance',
            in_array($code, ['7', 'direksi', 'holding'], true) => 'direksi',
            default => 'operator',
        };
    }

    public static function code(?User $user): string
    {
        return match (self::role($user)) {
            'kepala' => '3',
            'admin' => '2',
            'digital' => '4',
            'to' => '5',
            'finance' => '6',
            'direksi' => '7',
            default => '1',
        };
    }

    public static function label(?User $user): string
    {
        return self::options()[self::code($user)];
    }

    public static function can(?User $user, string $ability): bool
    {
        if (! $user) {
            return false;
        }
        $role = self::role($user);

        $map = [
            'home' => ['operator', 'kepala', 'admin', 'digital', 'to', 'finance', 'direksi'],
            'switch' => ['admin', 'direksi'],
            'sales' => ['operator', 'kepala', 'admin'],
            'store' => ['kepala', 'admin'],
            'master' => ['admin'],
            'master_pusat' => [],
            'transfer' => [],
            'rop' => [],
            'po_pusat' => [],
            'utility' => ['admin'],
            'utility_berat' => [],
            'stock' => ['operator', 'kepala', 'admin'],
            'store_reports' => ['kepala', 'admin'],
            'finance' => ['finance', 'direksi'],
            'pivot' => ['finance', 'direksi'],
            'holding' => ['direksi'],
            'digital' => ['digital'],
            'to' => ['to'],
            'budget' => ['finance', 'direksi'],
            'budget_write' => ['finance'],
            'budget_approve' => ['direksi'],
        ];

        return in_array($role, $map[$ability] ?? [], true);
    }

    public static function isMovedToInventory(?string $name): bool
    {
        $ability = $name ? self::abilityFor($name) : null;

        return in_array($ability, ['master_pusat', 'transfer', 'rop', 'po_pusat', 'utility_berat', 'pivot'], true);
    }

    public static function geraiRoleOptions(): array
    {
        $all = self::options();
        unset($all['2']);

        return $all;
    }

    public static function canSeeCost(?User $user): bool
    {
        return in_array(self::role($user), ['kepala', 'admin'], true);
    }

    public static function isStoreLocked(?User $user): bool
    {
        return in_array(self::role($user), ['operator', 'kepala'], true);
    }

    public static function lockStore(User $user): void
    {
        $branch = null;
        if ($user->branch_id) {
            $branch = Branch::with('warehouses')->find($user->branch_id);
        }
        if (! $branch && $user->kd_toko) {
            $branch = Branch::with('warehouses')->where('kd_toko', $user->kd_toko)->first();
        }
        if ($branch) {
            BranchContext::applyBranch($branch);
        }
    }

    public static function allowsRoute(?string $name, ?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($name === null || in_array($name, ['logout', 'login', 'login.post'], true)) {
            return true;
        }

        $ability = self::abilityFor($name);
        if ($ability === null) {
            return self::role($user) === 'admin';
        }

        return self::can($user, $ability);
    }

    public static function abilityFor(string $name): ?string
    {
        if ($name === 'dashboard') {
            return 'home';
        }
        if ($name === 'context.branch') {
            return 'switch';
        }
        if ($name === 'executive.budgets.approve') {
            return 'budget_approve';
        }
        if (in_array($name, ['executive.budgets.index', 'executive.budgets.show', 'executive.budgets.print'], true)) {
            return 'budget';
        }
        if (str_starts_with($name, 'executive.budgets')) {
            return 'budget_write';
        }
        if ($name === 'executive.dashboard') {
            return 'holding';
        }
        if (str_starts_with($name, 'digital.')) {
            return 'digital';
        }
        if (str_starts_with($name, 'fieldsales.')) {
            return 'to';
        }
        if (str_starts_with($name, 'finance.')) {
            return 'finance';
        }
        if (str_starts_with($name, 'reports.pivot')) {
            return 'pivot';
        }
        if (in_array($name, ['reports.stock', 'retail.inventory.index', 'retail.legacy-stock.index'], true)) {
            return 'stock';
        }
        if (str_starts_with($name, 'reports.')) {
            return 'store_reports';
        }
        if (str_starts_with($name, 'sales.')) {
            return 'sales';
        }
        if (str_starts_with($name, 'purchases.')) {
            return 'store';
        }
        if (in_array($name, [
            'master.customers.index', 'master.customers.store', 'master.customers.update', 'master.customers.destroy',
            'master.members.index', 'master.members.store', 'master.members.update', 'master.members.destroy',
            'master.crew.index', 'master.crew.store', 'master.crew.update', 'master.crew.destroy',
            'master.discount-fixed.index', 'master.discount-fixed.store', 'master.discount-fixed.update', 'master.discount-fixed.destroy',
            'master.discount-promo.index', 'master.discount-promo.store', 'master.discount-promo.update', 'master.discount-promo.destroy',
            'master.users.index', 'master.users.store', 'master.users.update', 'master.users.destroy',
            'master.suppliers.index', 'master.suppliers.store', 'master.suppliers.update', 'master.suppliers.destroy',
        ], true) || str_starts_with($name, 'master.customers')
            || str_starts_with($name, 'master.members')
            || str_starts_with($name, 'master.crew')
            || str_starts_with($name, 'master.discount')
            || str_starts_with($name, 'master.users')
            || str_starts_with($name, 'master.suppliers')
            || str_starts_with($name, 'master.brand-report')
            || str_starts_with($name, 'master.paket')) {
            return 'master';
        }
        if (str_starts_with($name, 'products.')
            || str_starts_with($name, 'master.kemas')
            || str_starts_with($name, 'master.bagian')
            || str_starts_with($name, 'master.stores')) {
            return 'master_pusat';
        }
        if (str_starts_with($name, 'inventory.')) {
            return 'transfer';
        }
        if (str_starts_with($name, 'procurement.rop')) {
            return 'rop';
        }
        if (str_starts_with($name, 'procurement.')) {
            return 'po_pusat';
        }
        if (in_array($name, [
            'utility.expired', 'utility.expired.update', 'utility.dead-stock', 'utility.dead-stock.print',
            'utility.data-check', 'utility.set-bagian', 'utility.set-bagian.apply',
            'utility.price-compare',
        ], true)) {
            return 'utility_berat';
        }
        if (str_starts_with($name, 'utility.')) {
            return 'utility';
        }
        if (str_starts_with($name, 'retail.')) {
            return 'store';
        }

        return null;
    }
}
