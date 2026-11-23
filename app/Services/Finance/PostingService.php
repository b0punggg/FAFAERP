<?php

namespace App\Services\Finance;

use App\Models\ChartOfAccount;
use App\Models\CoaGroupMap;
use App\Models\InterCompanyInvoice;
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

        $pay = strtoupper((string) ($sale->kd_bayar ?: 'TUNAI'));
        $qris = in_array($pay, ['QRIS'], true) ? $cash : 0;
        $edc = in_array($pay, ['BNI', 'EDC', 'DEBIT'], true) ? $cash : 0;
        $kas = $qris > 0 || $edc > 0 ? 0 : $cash;

        return $this->postBalancedEntry($companyId, BranchContext::branchId(), $sale->tgl_jual, Sale::class, (string) $sale->no_urut, 'Penjualan '.$sale->no_fak, [
            ['group' => 'cash', 'debit' => $kas],
            ['group' => 'cash', 'debit' => $qris, 'memo' => 'QRIS'],
            ['group' => 'cash', 'debit' => $edc, 'memo' => 'EDC'],
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

        $total = (float) ($purchase->tot_bayar ?: $purchase->tot_beli);
        $ppn = (float) ($purchase->ppn ?? 0);
        $cash = (float) ($purchase->byr_tunai ?? 0);
        $ap = (float) ($purchase->byr_hutang ?? 0);
        if ($cash <= 0 && $ap <= 0) {
            $pay = strtoupper((string) ($purchase->kd_bayar ?: 'TUNAI'));
            if ($pay === 'TEMPO') {
                $ap = $total;
            } else {
                $cash = $total;
            }
        }

        return $this->postBalancedEntry($companyId, BranchContext::branchId(), $purchase->tgl_fak, Purchase::class, (string) $purchase->no_urut, 'Pembelian '.$purchase->no_fak, [
            ['group' => 'inventory', 'debit' => $total - $ppn],
            ['group' => 'ppn_in', 'debit' => $ppn],
            ['group' => 'cash', 'credit' => $cash],
            ['group' => 'ap', 'credit' => $ap],
        ]);
    }

    public function postDigitalIntercompany(int $digitalCompanyId, int $retailCompanyId, float $amount, string $orderId, $date): void
    {
        $this->postIntercompany(
            $retailCompanyId,
            $digitalCompanyId,
            $amount,
            'IC-DIG-'.$orderId,
            $date,
            'digital_order',
            $orderId,
            'Gudang pusat untuk order digital #'.$orderId
        );
    }

    public function postFieldSalesIntercompany(int $toCompanyId, int $retailCompanyId, float $amount, string $orderId, $date): void
    {
        $this->postIntercompany(
            $retailCompanyId,
            $toCompanyId,
            $amount,
            'IC-TO-'.$orderId,
            $date,
            'sfa_order',
            $orderId,
            'Kanvas TO order #'.$orderId
        );
    }

    public function postIntercompanyInvoice(InterCompanyInvoice $invoice): void
    {
        if ($invoice->status === 'posted') {
            return;
        }

        $this->postIntercompany(
            (int) $invoice->seller_company_id,
            (int) $invoice->buyer_company_id,
            (float) $invoice->amount,
            (string) $invoice->invoice_no,
            $invoice->invoice_date,
            'inter_company_invoice',
            (string) $invoice->id,
            'Invoice antar-PT '.$invoice->invoice_no,
            $invoice
        );
    }

    public function postIntercompany(
        int $sellerCompanyId,
        int $buyerCompanyId,
        float $amount,
        string $invoiceNo,
        $date,
        string $refType,
        string $refId,
        string $memo,
        ?InterCompanyInvoice $existing = null
    ): void {
        if ($amount <= 0 || $sellerCompanyId === $buyerCompanyId) {
            return;
        }

        $posted = InterCompanyInvoice::query()
            ->where('invoice_no', $invoiceNo)
            ->where('status', 'posted')
            ->first();
        if ($posted && (! $existing || (int) $posted->id !== (int) $existing->id)) {
            return;
        }

        $this->postBalancedEntry($buyerCompanyId, null, $date, $refType, $refId, $memo.' (pembeli)', [
            ['group' => 'inventory', 'debit' => $amount],
            ['group' => 'ap', 'credit' => $amount],
        ]);
        $this->postBalancedEntry($sellerCompanyId, null, $date, $refType, $refId, $memo.' (penjual)', [
            ['group' => 'ar', 'debit' => $amount],
            ['group' => 'inventory', 'credit' => $amount],
        ]);

        $row = $existing ?: InterCompanyInvoice::firstOrNew(['invoice_no' => $invoiceNo]);
        $row->fill([
            'seller_company_id' => $sellerCompanyId,
            'buyer_company_id' => $buyerCompanyId,
            'invoice_date' => $date,
            'amount' => $amount,
            'status' => 'posted',
        ]);
        $row->save();
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
            $this->voidReference($refType, $refId, $companyId);

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
                    'memo' => $side['memo'] ?? $memo,
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

    public function voidReference(string $refType, string $refId, ?int $companyId = null): void
    {
        $query = JournalEntry::query()
            ->where('reference_type', $refType)
            ->where('reference_id', $refId)
            ->where('status', 'posted');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $query->update(['status' => 'void']);
    }

    public function ledger(int $companyId, string $accountCode, ?string $from = null, ?string $to = null): array
    {
        $query = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_lines.chart_of_account_id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', 'posted')
            ->where('chart_of_accounts.code', $accountCode);

        if ($from) {
            $query->where('journal_entries.entry_date', '>=', $from);
        }
        if ($to) {
            $query->where('journal_entries.entry_date', '<=', $to);
        }

        return $query
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->get([
                'journal_entries.id as entry_id',
                'journal_entries.entry_date',
                'journal_entries.memo',
                'journal_entries.reference_type',
                'journal_entries.reference_id',
                'journal_entries.branch_id',
                'journal_lines.debit',
                'journal_lines.credit',
                'journal_lines.memo as line_memo',
                'chart_of_accounts.code',
                'chart_of_accounts.name',
            ])
            ->toArray();
    }

    public function documentUrl(?string $refType, ?string $refId): ?string
    {
        if (! $refType || $refId === null || $refId === '') {
            return null;
        }
        if ($refType === Sale::class || $refType === 'App\\Models\\Sale') {
            return route('sales.show', $refId);
        }
        if ($refType === Purchase::class || $refType === 'App\\Models\\Purchase') {
            return route('purchases.show', $refId);
        }
        if ($refType === 'sales_return') {
            return route('retail.returns.sales');
        }
        if ($refType === 'purchase_return') {
            return route('retail.returns.purchases');
        }
        if ($refType === 'kas_entry') {
            return route('retail.kas.index');
        }
        if ($refType === 'piutang_payment') {
            return route('retail.receivables.index');
        }
        if ($refType === 'hutang_payment') {
            return route('retail.payables.index');
        }

        return null;
    }
}
