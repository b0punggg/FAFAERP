<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\InterCompanyInvoice;
use App\Services\Finance\PostingService;
use App\Support\Reports\ReportOutput;
use App\Support\Reports\ReportSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InterCompanyController extends Controller
{
    public function __construct(protected PostingService $posting)
    {
    }

    public function index(Request $request)
    {
        $companies = Company::orderBy('code')->get();
        $names = $companies->mapWithKeys(fn ($company) => [$company->id => $company->code.' — '.$company->legal_name]);
        $ordered = InterCompanyInvoice::orderByDesc('id');
        $map = function ($invoice) use ($names) {
            return [
                'invoice_no' => $invoice->invoice_no,
                'invoice_date' => $invoice->invoice_date,
                'seller' => $names[$invoice->seller_company_id] ?? $invoice->seller_company_id,
                'buyer' => $names[$invoice->buyer_company_id] ?? $invoice->buyer_company_id,
                'amount' => $invoice->amount,
                'status' => $this->statusLabel($invoice->status),
            ];
        };
        $columns = [
            ['key' => 'invoice_no', 'label' => 'Nomor'],
            ['key' => 'invoice_date', 'label' => 'Tanggal', 'date' => true],
            ['key' => 'seller', 'label' => 'Penjual'],
            ['key' => 'buyer', 'label' => 'Pembeli'],
            ['key' => 'amount', 'label' => 'Nilai', 'money' => true],
            ['key' => 'status', 'label' => 'Status'],
        ];

        if (ReportOutput::wants($request)) {
            $rows = [];
            foreach ($ordered->get() as $invoice) {
                $rows[] = $map($invoice);
            }

            return ReportOutput::send($request, new ReportSheet('Jurnal antar-PT', 'Draf dan invoice antar perusahaan', $columns, $rows));
        }

        $invoices = $ordered->paginate(20)->withQueryString();
        $sheetRows = [];
        foreach ($invoices as $invoice) {
            $sheetRows[] = $map($invoice);
        }
        $sheet = new ReportSheet('Jurnal antar-PT', 'Draf dan invoice antar perusahaan', $columns, $sheetRows);

        return view('admin.finance.inter-company', compact('invoices', 'companies', 'sheet'));
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'draft' => 'Draf',
            'posted' => 'Diposting',
            default => (string) $status,
        };
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'seller_company_id' => 'required|exists:companies,id',
            'buyer_company_id' => 'required|different:seller_company_id|exists:companies,id',
            'amount' => 'required|numeric|min:0.01',
            'invoice_date' => 'required|date',
        ]);

        InterCompanyInvoice::create(array_merge($data, [
            'invoice_no' => 'IC-'.Str::upper(Str::random(8)),
            'status' => 'draft',
        ]));

        return back()->with('success', 'Draf invoice antar-PT dibuat. Posting untuk menulis jurnal kedua PT.');
    }

    public function post(InterCompanyInvoice $invoice)
    {
        if ($invoice->status === 'posted') {
            return back()->with('success', 'Invoice ini sudah diposting.');
        }
        $this->posting->postIntercompanyInvoice($invoice);

        return back()->with('success', 'Invoice '.$invoice->invoice_no.' diposting ke jurnal penjual dan pembeli.');
    }
}
