<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\Member;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberMasterController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $query = $this->memberQuery($request, $kdToko);
        $this->applySort($query, $request->get('sort', 'abjad'));

        $editItem = $request->filled('edit') ? Member::find($request->edit) : null;
        if ($editItem) {
            $this->authorizeMember($editItem);
        }

        return view('admin.master.members', [
            'items' => $query->paginate(10)->withQueryString(),
            'editItem' => $editItem,
            'nextKd' => $this->nextKdMember($kdToko),
            'sort' => $request->get('sort', 'abjad'),
        ]);
    }

    public function store(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);
        $data = $this->validatedMember($request);

        $member = null;
        DB::transaction(function () use ($data, $kdToko, &$member) {
            $kd = strtoupper(trim((string) ($data['kd_member'] ?? '')));
            if ($kd === '') {
                $kd = $this->nextKdMember($kdToko, true);
            }

            $existing = Member::query()
                ->where('kd_toko', $kdToko)
                ->where('kd_member', $kd)
                ->lockForUpdate()
                ->first();

            $payload = [
                'nm_member' => strtoupper($data['nm_member']),
                'nm_toko' => strtoupper($data['nm_toko'] ?? ''),
                'al_member' => strtoupper($data['al_member'] ?? ''),
                'no_telp' => $data['no_telp'] ?? '',
            ];

            if ($existing) {
                $existing->update($payload);
                $member = $existing;

                return;
            }

            $member = Member::create($payload + [
                'kd_member' => $kd,
                'poin' => 0,
                'tgl_daftar' => now()->toDateString(),
                'kd_toko' => $kdToko,
            ]);
        });

        return redirect()->route('master.members.index')->with('success', 'Member disimpan ('.$member->kd_member.').');
    }

    public function update(Request $request, Member $member)
    {
        $this->authorizeMember($member);
        $data = $this->validatedMember($request);

        $member->update([
            'nm_member' => strtoupper($data['nm_member']),
            'nm_toko' => strtoupper($data['nm_toko'] ?? ''),
            'al_member' => strtoupper($data['al_member'] ?? ''),
            'no_telp' => $data['no_telp'] ?? '',
        ]);

        return redirect()->route('master.members.index', $request->only('q', 'sort'))->with('success', 'Member diperbarui.');
    }

    public function destroy(Member $member)
    {
        $this->authorizeMember($member);
        $member->delete();

        return back()->with('success', 'Member dihapus.');
    }

    public function export(Request $request): StreamedResponse
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        $query = $this->memberQuery($request, $kdToko);
        $this->applySort($query, $request->get('sort', 'abjad'));
        $rows = $query->get();
        $filename = 'member-'.$kdToko.'-'.date('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID Member', 'Nama Member', 'Nama Toko', 'Alamat', 'No. Telp/HP', 'Tgl Daftar', 'Poin']);
            foreach ($rows as $m) {
                fputcsv($out, [
                    $m->kd_member,
                    $m->nm_member,
                    $m->nm_toko,
                    $m->al_member,
                    $m->no_telp,
                    optional($m->tgl_daftar)->format('d-m-Y'),
                    $m->poin,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        $query = $this->memberQuery($request, $kdToko);
        $this->applySort($query, $request->get('sort', 'abjad'));

        return view('admin.master.members-print', [
            'items' => $query->get(),
            'storeName' => session('nm_toko') ?: $kdToko,
        ]);
    }

    protected function memberQuery(Request $request, ?string $kdToko)
    {
        $query = Member::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where(function ($q) {
                $q->whereNull('nm_member')->orWhere('nm_member', '!=', '-NONE-');
            });

        return $this->applyMasterSearch($query, $request, ['kd_member', 'nm_member', 'nm_toko', 'al_member', 'no_telp']);
    }

    protected function applySort($query, string $sort): void
    {
        if ($sort === 'id_asc') {
            $query->orderBy('no_urut');
        } elseif ($sort === 'poin_desc') {
            $query->orderByDesc('poin')->orderBy('nm_member');
        } elseif ($sort === 'poin_asc') {
            $query->orderBy('poin')->orderBy('nm_member');
        } else {
            $query->orderBy('nm_member');
        }
    }

    protected function nextKdMember(?string $kdToko, bool $lock = false): string
    {
        $query = Member::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('kd_member', 'like', 'IDMEM-%');

        if ($lock) {
            $query->lockForUpdate();
        }

        $codes = $query->pluck('kd_member');
        $last = 0;
        foreach ($codes as $code) {
            $n = $this->memberSeqFromCode((string) $code);
            if ($n !== null) {
                $last = max($last, $n);
            }
        }

        $next = $last + 1;
        while (Member::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('kd_member', 'IDMEM-'.$next)
            ->exists()) {
            $next++;
        }

        return 'IDMEM-'.$next;
    }

    /** Nomor urut dari IDMEM-n. Abaikan kode acak / nomor telepon yang nyasar ke kode member. */
    protected function memberSeqFromCode(string $code): ?int
    {
        $code = strtoupper(trim($code));
        if (! preg_match('/^IDMEM-(\d{1,6})$/', $code, $match)) {
            return null;
        }

        return (int) $match[1];
    }

    protected function validatedMember(Request $request): array
    {
        return $request->validate([
            'kd_member' => 'nullable|string|max:50',
            'nm_member' => 'required|string|max:255',
            'nm_toko' => 'nullable|string|max:255',
            'al_member' => 'nullable|string',
            'no_telp' => 'nullable|string|max:50',
        ]);
    }

    protected function authorizeMember(Member $member): void
    {
        $kdToko = BranchContext::kdToko();
        if ($kdToko && $member->kd_toko !== $kdToko) {
            abort(403);
        }
    }
}
