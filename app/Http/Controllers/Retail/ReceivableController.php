<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\Finance\PostingService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends Controller
{
    public function __construct(protected PostingService $posting)
    {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $sales = Sale::where('kd_toko', BranchContext::kdToko())
            ->where('byr_hutang', '>', 0)
            ->when($q !== '', fn ($query) => $query->where('no_fak', 'like', '%'.$q.'%'))
            ->orderByDesc('tgl_jual')
            ->paginate(20)
            ->withQueryString();

        return view('admin.retail.receivables', compact('sales', 'q'));
    }

    public function pay(Request $request, Sale $sale)
    {
        abort_unless($sale->kd_toko === BranchContext::kdToko(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'tgl_bayar' => 'required|date',
            'post_to_gl' => 'nullable|boolean',
        ]);

        $remaining = (float) $sale->byr_hutang;
        abort_if($data['amount'] > $remaining + 0.001, 422, 'Nominal melebihi sisa piutang.');

        $paymentId = DB::table('piutang_payments')->insertGetId([
            'sale_id' => $sale->no_urut,
            'tgl_bayar' => $data['tgl_bayar'],
            'amount' => $data['amount'],
            'kd_toko' => $sale->kd_toko,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $entry = $this->posting->postReceivablePayment(
            (float) $data['amount'],
            $data['tgl_bayar'],
            (string) $sale->no_fak,
            (string) $paymentId
        );
        if ($entry) {
            DB::table('piutang_payments')->where('id', $paymentId)->update(['journal_entry_id' => $entry->id]);
        }

        $sale->byr_hutang = max(0, $remaining - (float) $data['amount']);
        $sale->byr_tunai = (float) $sale->byr_tunai + (float) $data['amount'];
        $sale->save();

        return redirect()
            ->route('retail.receivables.payment.print', $paymentId)
            ->with('success', 'Pembayaran piutang tercatat.');
    }

    public function printPayment(int $paymentId)
    {
        $kdToko = BranchContext::kdToko();
        $payment = DB::table('piutang_payments')
            ->where('id', $paymentId)
            ->where('kd_toko', $kdToko)
            ->first();

        abort_unless($payment, 404);

        $sale = Sale::where('no_urut', $payment->sale_id)->where('kd_toko', $kdToko)->first();

        return view('admin.retail.receivable-payment-print', compact('payment', 'sale'));
    }
}
