<?php

namespace App\Services\Finance;

use App\Models\ChartOfAccount;
use App\Models\CoaGroupMap;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Purchase;
use App\Models\Sale;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;

class PostingService
{
    public function postSale(Sale $sale): ?JournalEntry
    {
        $companyId = BranchContext::companyId();
        if (! $companyId) {
            return null;
        }

        $cash = (float) $sale->byr_tunai;
        $ar = (float) $sale->byr_hutang;
        $revenue = (float) $sale->tot_jual - (float) $sale->disc;
        $ppn = (float) $sale->ppn;

        return $this->postBalancedEntry($companyId, BranchContext::branchId(), $sale->tgl_jual, Sale::class, (string) $sale->no_urut, 'Penjualan '.$sale->no_fak, [
            ['group' => 'cash', 'debit' => $cash],
            ['group' => 'ar', 'debit' => $ar],
            ['group' => 'revenue', 'credit' => $revenue],
            ['group' => 'ppn_out', 'credit' => $ppn],
        ]);
    }

    public function postSalesReturn(float $total, string $noRetur, $date): ?JournalEntry
    {
        $companyId = BranchContext::companyId();
        if (! $companyId || $total <= 0) {
            return null;
        }

        return $this->postBalancedEntry($companyId, BranchContext::branchId(), $date, 'sales_return', $noRetur, 'Retur penjualan '.$noRetur, [
            ['group' => 'revenue', 'debit' => $total],
            ['group' => 'cash', 'credit' => $total],
        ]);
    }

    public function postPurchaseReturn(float $total, string $noRetur, $date): ?JournalEntry
    {
        $companyId = BranchContext::companyId();
        if (! $companyId || $total <= 0) {
            return null;
        }

        return $this->postBalancedEntry($companyId, BranchContext::branchId(), $date, 'purchase_return', $noRetur, 'Retur pembelian '.$noRetur, [
            ['group' => 'ap', 'debit' => $total],
            ['group' => 'inventory', 'credit' => $total],
        ]);
    }

    public function postKasEntry(string $direction, float $amount, $date, string $entryId, bool $postToGl = true): ?JournalEntry
    {
        $companyId = BranchContext::companyId();
        if (! $companyId || ! $postToGl || $amount <= 0) {
            return null;
        }

        if ($direction === 'in') {
            return $this->postBalancedEntry($companyId, BranchContext::branchId(), $date, 'kas_entry', $entryId, 'Kas masuk #'.$entryId, [
                ['group' => 'cash', 'debit' => $amount],
                ['group' => 'other_income', 'credit' => $amount],
            ]);
        }

        return $this->postBalancedEntry($companyId, BranchContext::branchId(), $date, 'kas_entry', $entryId, 'Kas keluar #'.$entryId, [
            ['group' => 'expense', 'debit' => $amount],
            ['group' => 'cash', 'credit' => $amount],
        ]);
    }

    public function postOperatingExpense(float $amount, $date, string $expenseId): ?JournalEntry
    {
        $companyId = BranchContext::companyId();
        if (! $companyId || $amount <= 0) {
            return null;
        }

        return $this->postBalancedEntry($companyId, BranchContext::branchId(), $date, 'operating_expense', $expenseId, 'Biaya operasional #'.$expenseId, [
            ['group' => 'expense', 'debit' => $amount],
            ['group' => 'cash', 'credit' => $amount],
        ]);
    }

    public function postReceivablePayment(float $amount, $date, string $saleKey, string $paymentId): ?JournalEntry
    {
        $companyId = BranchContext::companyId();
        if (! $companyId || $amount <= 0) {
            return null;
        }

        return $this->postBalancedEntry($companyId, BranchContext::branchId(), $date, 'piutang_payment', $paymentId, 'Bayar piutang faktur ref '.$saleKey, [
            ['group' => 'cash', 'debit' => $amount],
            ['group' => 'ar', 'credit' => $amount],
        ]);
    }

    public function postPayablePayment(float $amount, $date, string $purchaseKey, string $paymentId): ?JournalEntry
    {
        $companyId = BranchContext::companyId();
        if (! $companyId || $amount <= 0) {
            return null;
        }

        return $this->postBalancedEntry($companyId, BranchContext::branchId(), $date, 'hutang_payment', $paymentId, 'Bayar hutang faktur ref '.$purchaseKey, [
            ['group' => 'ap', 'debit' => $amount],
            ['group' => 'cash', 'credit' => $amount],
        ]);
    }

    public function postPurchase(Purchase $purchase): ?JournalEntry
    {
        $companyId = BranchContext::companyId();
        if (! $companyId) {
            return null;
        }

        $total = (float) $purchase->tot_beli;
        $ppn = (float) ($purchase->ppn ?? 0);

        return $this->postBalancedEntry($companyId, BranchContext::branchId(), $purchase->tgl_fak, Purchase::class, (string) $purchase->no_urut, 'Pembelian '.$purchase->no_fak, [
            ['group' => 'inventory', 'debit' => $total - $ppn],
            ['group' => 'ppn_in', 'debit' => $ppn],
            ['group' => 'ap', 'credit' => $total],
        ]);
    }

    public function postDigitalIntercompany(int $digitalCompanyId, int $retailCompanyId, float $amount, string $orderId, $date): void
    {
        if ($amount <= 0 || $digitalCompanyId === $retailCompanyId) {
            return;
        }

        $this->postBalancedEntry($digitalCompanyId, null, $date, 'digital_order', $orderId, 'Order digital antar PT #'.$orderId, [
            ['group' => 'inventory', 'debit' => $amount],
            ['group' => 'ap', 'credit' => $amount],
        ]);
        $this->postBalancedEntry($retailCompanyId, null, $date, 'digital_order', $orderId, 'Pengeluaran gudang pusat untuk order digital #'.$orderId, [
            ['group' => 'ar', 'debit' => $amount],
            ['group' => 'inventory', 'credit' => $amount],
        ]);

        \App\Models\InterCompanyInvoice::create([
            'seller_company_id' => $retailCompanyId,
            'buyer_company_id' => $digitalCompanyId,
            'invoice_no' => 'IC-DIG-'.$orderId,
            'invoice_date' => $date,
            'amount' => $amount,
            'status' => 'posted',
        ]);
    }

    protected function postBalancedEntry(
        int $companyId,
        ?int $branchId,
        $date,
        string $refType,
        string $refId,
        string $memo,
        array $sides
    ): JournalEntry {
        return DB::transaction(function () use ($companyId, $branchId, $date, $refType, $refId, $memo, $sides) {
            $entry = JournalEntry::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'entry_date' => $date,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'memo' => $memo,
                'status' => 'posted',
            ]);

            foreach ($sides as $side) {
                $accountId = $this->accountIdForGroup($companyId, $side['group']);
                $debit = (float) ($side['debit'] ?? 0);
                $credit = (float) ($side['credit'] ?? 0);
                if ($debit <= 0 && $credit <= 0) {
                    continue;
                }
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $accountId,
                    'debit' => $debit,
                    'credit' => $credit,
                ]);
            }

            return $entry->load('lines');
        });
    }

    protected function accountIdForGroup(int $companyId, string $groupCode): int
    {
        $mapped = CoaGroupMap::where('company_id', $companyId)
            ->where('group_code', $groupCode)
            ->value('chart_of_account_id');

        if ($mapped) {
            return (int) $mapped;
        }

        return (int) ChartOfAccount::where('company_id', $companyId)->value('id');
    }

    public function trialBalance(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $query = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_lines.chart_of_account_id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', 'posted');

        if ($from) {
            $query->where('journal_entries.entry_date', '>=', $from);
        }
        if ($to) {
            $query->where('journal_entries.entry_date', '<=', $to);
        }

        return $query
            ->selectRaw('chart_of_accounts.code, chart_of_accounts.name, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('chart_of_accounts.code', 'chart_of_accounts.name')
            ->orderBy('chart_of_accounts.code')
            ->get()
            ->toArray();
    }
}
