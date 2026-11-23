<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Finance\FinancialStatementService;
use App\Services\Finance\PostingService;
use App\Support\BranchContext;
use App\Support\Reports\ReportOutput;
use App\Support\Reports\ReportSheet;
use Illuminate\Http\Request;

class FinanceReportController extends Controller
{
    public function __construct(
        protected PostingService $posting,
        protected FinancialStatementService $statements
    ) {
    }

    public function trialBalance(Request $request)
    {
        $companyId = $request->integer('company_id') ?: BranchContext::companyId();
        $from = $request->get('from', date('Y-m-01'));
        $to = $request->get('to', date('Y-m-d'));
        $rows = $companyId ? $this->posting->trialBalance($companyId, $from, $to) : [];
        $companies = Company::orderBy('code')->get();
        $company = $companies->firstWhere('id', $companyId);
        $debet = collect($rows)->sum(fn ($row) => (float) ($row['total_debit'] ?? 0));
        $kredit = collect($rows)->sum(fn ($row) => (float) ($row['total_credit'] ?? 0));
        $sheet = new ReportSheet('Trial balance', $this->companyLine($company, $from, $to), [
            ['key' => 'code', 'label' => 'Kode'],
            ['key' => 'name', 'label' => 'Nama akun'],
            ['key' => 'total_debit', 'label' => 'Debit', 'money' => true],
            ['key' => 'total_credit', 'label' => 'Kredit', 'money' => true],
        ], array_map(fn ($row) => [
            'code' => $row['code'] ?? '',
            'name' => $row['name'] ?? '',
            'total_debit' => $row['total_debit'] ?? 0,
            'total_credit' => $row['total_credit'] ?? 0,
        ], $rows), [
            'Jumlah debit' => $this->rupiah($debet),
            'Jumlah kredit' => $this->rupiah($kredit),
        ], [], $companyId ? 'Belum ada jurnal pada periode ini.' : 'Pilih perusahaan.');

        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, $sheet);
        }

        return view('admin.finance.trial-balance', compact('rows', 'companies', 'companyId', 'from', 'to', 'sheet'));
    }

    public function trialBalanceLedger(Request $request)
    {
        $companyId = $request->integer('company_id') ?: BranchContext::companyId();
        $from = $request->get('from', date('Y-m-01'));
        $to = $request->get('to', date('Y-m-d'));
        $code = (string) $request->get('account', '');
        abort_unless($companyId && $code !== '', 404);

        $lines = $this->posting->ledger($companyId, $code, $from, $to);
        $companies = Company::orderBy('code')->get();
        $company = $companies->firstWhere('id', $companyId);
        $accountName = $lines[0]['name'] ?? $code;

        return view('admin.finance.trial-balance-ledger', [
            'lines' => $lines,
            'companies' => $companies,
            'companyId' => $companyId,
            'from' => $from,
            'to' => $to,
            'code' => $code,
            'accountName' => $accountName,
            'posting' => $this->posting,
        ]);
    }

    public function incomeStatement(Request $request)
    {
        [$companyId, $companies, $from, $to] = $this->period($request);
        $statement = $companyId
            ? $this->statements->incomeStatement($companyId, $from, $to)
            : null;
        $company = $companies->firstWhere('id', $companyId);
        $sheet = $this->incomeSheet($statement, $company, $from, $to);

        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, $sheet);
        }

        return view('admin.finance.income-statement', compact('statement', 'companies', 'companyId', 'from', 'to', 'sheet'));
    }

    public function balanceSheet(Request $request)
    {
        $companyId = $request->integer('company_id') ?: BranchContext::companyId();
        $asOf = $request->get('as_of', date('Y-m-d'));
        $companies = Company::orderBy('code')->get();
        $statement = $companyId
            ? $this->statements->balanceSheet($companyId, $asOf)
            : null;
        $company = $companies->firstWhere('id', $companyId);
        $sheet = $this->balanceSheetDocument($statement, $company, $asOf);

        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, $sheet);
        }

        return view('admin.finance.balance-sheet', compact('statement', 'companies', 'companyId', 'asOf', 'sheet'));
    }

    private function period(Request $request): array
    {
        return [
            $request->integer('company_id') ?: BranchContext::companyId(),
            Company::orderBy('code')->get(),
            $request->get('from', date('Y-m-01')),
            $request->get('to', date('Y-m-d')),
        ];
    }

    private function companyLine(?Company $company, ?string $from, ?string $to): string
    {
        $name = $company ? trim($company->code.' — '.$company->legal_name) : 'Perusahaan';
        if ($from && $to) {
            return $name.' · '.date('d/m/Y', strtotime($from)).' s/d '.date('d/m/Y', strtotime($to));
        }

        return $name;
    }

    private function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    private function accountRows(array $rows): array
    {
        return array_map(fn ($row) => [
            'code' => $row['code'] ?? '',
            'name' => $row['name'] ?? '',
            'amount' => $row['amount'] ?? 0,
        ], $rows);
    }

    private function incomeSheet(?array $statement, ?Company $company, string $from, string $to): ReportSheet
    {
        $columns = [
            ['key' => 'code', 'label' => 'Kode'],
            ['key' => 'name', 'label' => 'Akun'],
            ['key' => 'amount', 'label' => 'Jumlah', 'money' => true],
        ];

        return new ReportSheet('Laba rugi', $this->companyLine($company, $from, $to), [], [], $statement ? [
            'Pendapatan' => $this->rupiah((float) $statement['total_revenue']),
            'Beban' => $this->rupiah((float) $statement['total_expense']),
            'Laba (rugi) bersih' => $this->rupiah((float) $statement['net']),
        ] : [], [
            ['title' => 'Pendapatan', 'columns' => $columns, 'rows' => $this->accountRows($statement['revenue'] ?? [])],
            ['title' => 'Beban', 'columns' => $columns, 'rows' => $this->accountRows($statement['expense'] ?? [])],
        ], 'Belum ada jurnal pada periode ini.');
    }

    private function balanceSheetDocument(?array $statement, ?Company $company, string $asOf): ReportSheet
    {
        $columns = [
            ['key' => 'code', 'label' => 'Kode'],
            ['key' => 'name', 'label' => 'Akun'],
            ['key' => 'amount', 'label' => 'Saldo', 'money' => true],
        ];

        return new ReportSheet('Neraca', $this->companyLine($company, null, null).' · per '.date('d/m/Y', strtotime($asOf)), [], [], $statement ? [
            'Jumlah aset' => $this->rupiah((float) $statement['total_assets']),
            'Kewajiban + ekuitas' => $this->rupiah((float) $statement['total_claims']),
        ] : [], [
            ['title' => 'Aset', 'columns' => $columns, 'rows' => $this->accountRows($statement['assets'] ?? [])],
            ['title' => 'Kewajiban', 'columns' => $columns, 'rows' => $this->accountRows($statement['liabilities'] ?? [])],
            ['title' => 'Ekuitas', 'columns' => $columns, 'rows' => $this->accountRows($statement['equity'] ?? [])],
        ], 'Belum ada saldo pada tanggal ini.');
    }
}
