<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\DB;

class FinancialStatementService
{
    public function incomeStatement(int $companyId, ?string $from, ?string $to): array
    {
        $rows = $this->balances($companyId, $from, $to);
        $revenue = [];
        $expense = [];
        foreach ($rows as $row) {
            if ($row->type === 'revenue') {
                $amount = (float) $row->credit - (float) $row->debit;
                if (abs($amount) >= 0.005) {
                    $revenue[] = ['code' => $row->code, 'name' => $row->name, 'amount' => $amount];
                }
            }
            if ($row->type === 'expense') {
                $amount = (float) $row->debit - (float) $row->credit;
                if (abs($amount) >= 0.005) {
                    $expense[] = ['code' => $row->code, 'name' => $row->name, 'amount' => $amount];
                }
            }
        }

        $totalRevenue = array_sum(array_column($revenue, 'amount'));
        $totalExpense = array_sum(array_column($expense, 'amount'));

        return [
            'revenue' => $revenue,
            'expense' => $expense,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net' => $totalRevenue - $totalExpense,
        ];
    }

    public function balanceSheet(int $companyId, ?string $asOf): array
    {
        $rows = $this->balances($companyId, null, $asOf);
        $assets = [];
        $liabilities = [];
        $equity = [];
        $earnings = 0.0;

        foreach ($rows as $row) {
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            if ($row->type === 'asset') {
                $amount = $debit - $credit;
                if (abs($amount) >= 0.005) {
                    $assets[] = ['code' => $row->code, 'name' => $row->name, 'amount' => $amount];
                }
            } elseif ($row->type === 'liability') {
                $amount = $credit - $debit;
                if (abs($amount) >= 0.005) {
                    $liabilities[] = ['code' => $row->code, 'name' => $row->name, 'amount' => $amount];
                }
            } elseif ($row->type === 'equity') {
                $amount = $credit - $debit;
                if (abs($amount) >= 0.005) {
                    $equity[] = ['code' => $row->code, 'name' => $row->name, 'amount' => $amount];
                }
            } elseif ($row->type === 'revenue') {
                $earnings += $credit - $debit;
            } elseif ($row->type === 'expense') {
                $earnings -= $debit - $credit;
            }
        }

        if (abs($earnings) >= 0.005) {
            $equity[] = ['code' => '', 'name' => 'Laba berjalan', 'amount' => $earnings];
        }

        $totalAssets = array_sum(array_column($assets, 'amount'));
        $totalLiabilities = array_sum(array_column($liabilities, 'amount'));
        $totalEquity = array_sum(array_column($equity, 'amount'));

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_claims' => $totalLiabilities + $totalEquity,
        ];
    }

    private function balances(int $companyId, ?string $from, ?string $to)
    {
        $query = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('chart_of_accounts as a', 'a.id', '=', 'jl.chart_of_account_id')
            ->where('je.company_id', $companyId)
            ->where('je.status', 'posted');

        if ($from) {
            $query->where('je.entry_date', '>=', $from);
        }
        if ($to) {
            $query->where('je.entry_date', '<=', $to);
        }

        return $query
            ->selectRaw('a.code, a.name, a.type, SUM(jl.debit) as debit, SUM(jl.credit) as credit')
            ->groupBy('a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get();
    }
}
