<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\Finance\PostingService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayableController extends Controller
{
    public function __construct(protected PostingService $posting)
    {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $kdToko = BranchContext::kdToko();

        $purchases = Purchase::query()
            ->where('kd_toko', $kdToko)
            ->where('byr_hutang', '>', 0)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->where('no_fak', 'like', '%'.$q.'%')
                        ->orWhere('nm_sup', 'like', '%'.$q.'%');
                });
            })
            ->orderByRaw('tgl_jt IS NULL')
            ->orderBy('tgl_jt')
            ->orderByDesc('no_urut')
            ->paginate(20)
            ->withQueryString();

        return view('admin.retail.payables', compact('purchases', 'q'));
    }

    public function pay(Request $request, Purchase $purchase)
    {
        abort_unless($purchase->kd_toko === BranchContext::kdToko(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'tgl_bayar' => 'required|date',
            'post_to_gl' => 'nullable|boolean',
        ]);

        $remaining = (float) $purchase->byr_hutang;
        abort_if($data['amount'] > $remaining + 0.001, 422, 'Nominal melebihi sisa hutang.');

        DB::beginTransaction();
        try {
        $paymentId = DB::table('hutang_payments')->insertGetId([
            'purchase_id' => $purchase->no_urut,
            'tgl_bayar' => $data['tgl_bayar'],
            'amount' => $data['amount'],
            'kd_toko' => $purchase->kd_toko,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchase->byr_tunai = (float) $purchase->byr_tunai + (float) $data['amount'];
        $purchase->byr_hutang = max(0, $remaining - (float) $data['amount']);
        $purchase->save();

        if ($request->boolean('post_to_gl')) {
            $entry = $this->posting->postPayablePayment(
                (float) $data['amount'],
                $data['tgl_bayar'],
                (string) $purchase->no_fak,
                (string) $paymentId
            );
            if ($entry) {
                DB::table('hutang_payments')->where('id', $paymentId)->update(['journal_entry_id' => $entry->id]);
            }
        }
        DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()
            ->route('retail.payables.payment.print', $paymentId)
            ->with('success', 'Pembayaran hutang tercatat.');
    }

    public function printPayment(int $paymentId)
    {
        $kdToko = BranchContext::kdToko();
        $payment = DB::table('hutang_payments')
            ->where('id', $paymentId)
            ->where('kd_toko', $kdToko)
            ->first();

        abort_unless($payment, 404);

        $purchase = Purchase::where('no_urut', $payment->purchase_id)->where('kd_toko', $kdToko)->first();

        return view('admin.retail.payable-payment-print', compact('payment', 'purchase'));
    }
}
