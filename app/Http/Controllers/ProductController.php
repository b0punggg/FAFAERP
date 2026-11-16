<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\Master\CatalogMirror;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $kd_toko = BranchContext::kdToko();
        $search = $request->get('search', '');
        
        $query = Product::where('kd_toko', $kd_toko);
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('kd_brg', 'like', "%{$search}%")
                  ->orWhere('nm_brg', 'like', "%{$search}%")
                  ->orWhere('kd_bar', 'like', "%{$search}%");
            });
        }
        
        $products = $query->orderBy('nm_brg')->paginate(20);
        
        return view('admin.products.index', compact('products', 'search'));
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kd_brg' => 'required|string|max:50',
            'kd_bar' => 'nullable|string|max:50',
            'nm_brg' => 'required|string|max:255',
            'kd_kem1' => 'required|integer',
            'nm_kem1' => 'required|string|max:50',
            'jum_kem1' => 'required|numeric',
            'hrg_jum1' => 'required|numeric',
        ]);

        $data = $request->all();
        $data['kd_toko'] = BranchContext::kdToko();
        $data['jml_brg'] = 0;
        $data['brg_msk'] = 0;
        $data['brg_klr'] = 0;
        $data['kd_bar'] = $data['kd_bar'] ?? '';
        foreach (['cetak' => 0, 'pilih' => 0, 'copy' => 0, 'id_user' => auth()->id() ?: 0] as $column => $value) {
            if (Schema::hasColumn('mas_brg', $column)) {
                $data[$column] = $value;
            }
        }

        $product = Product::create($data);
        app(CatalogMirror::class)->upsert($product);

        return redirect()->route('products.index')->with('success', 'Barang berhasil ditambahkan');
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.products.show', compact('product'));
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nm_brg' => 'required|string|max:255',
            'kd_kem1' => 'required|integer',
            'nm_kem1' => 'required|string|max:50',
            'jum_kem1' => 'required|numeric',
            'hrg_jum1' => 'required|numeric',
        ]);

        $product = Product::findOrFail($id);
        $product->update($request->all());
        app(CatalogMirror::class)->upsert($product->fresh());

        return redirect()->route('products.index')->with('success', 'Barang berhasil diupdate');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $sku = (string) $product->kd_brg;
        $product->delete();
        app(CatalogMirror::class)->forget($sku);

        return redirect()->route('products.index')->with('success', 'Barang berhasil dihapus');
    }

    public function search(Request $request)
    {
        $kd_toko = BranchContext::kdToko();
        $keyword = $request->get('keyword', '');
        
        $products = Product::where('kd_toko', $kd_toko)
            ->where(function($q) use ($keyword) {
                $q->where('kd_brg', 'like', "%{$keyword}%")
                  ->orWhere('nm_brg', 'like', "%{$keyword}%")
                  ->orWhere('kd_bar', 'like', "%{$keyword}%");
            })
            ->limit(20)
            ->get();
        
        return response()->json($products);
    }

    public function export(Request $request)
    {
        $kd_toko = BranchContext::kdToko();
        $search = $request->get('search', '');

        $query = Product::where('kd_toko', $kd_toko);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('kd_brg', 'like', "%{$search}%")
                    ->orWhere('nm_brg', 'like', "%{$search}%")
                    ->orWhere('kd_bar', 'like', "%{$search}%");
            });
        }

        $filename = 'barang-'.($kd_toko ?: 'all').'-'.date('Ymd').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['kd_brg', 'kd_bar', 'nm_brg', 'jml_brg', 'hrg_jum1', 'hrg_jum2', 'hrg_jum3']);
            $query->orderBy('nm_brg')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $p) {
                    fputcsv($out, [
                        $p->kd_brg,
                        $p->kd_bar,
                        $p->nm_brg,
                        $p->jml_brg,
                        $p->hrg_jum1,
                        $p->hrg_jum2,
                        $p->hrg_jum3,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

