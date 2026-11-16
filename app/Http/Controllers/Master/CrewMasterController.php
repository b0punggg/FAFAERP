<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\Crew;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrewMasterController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $query = $this->crewQuery($request, $kdToko);
        $this->applySort($query, $request->get('sort', 'abjad'));

        $editItem = $request->filled('edit') ? Crew::find($request->edit) : null;
        if ($editItem) {
            $this->authorizeCrew($editItem);
        }

        return view('admin.master.crew', [
            'items' => $query->paginate(10)->withQueryString(),
            'editItem' => $editItem,
            'nextKd' => $this->nextKdCrew($kdToko),
            'sort' => $request->get('sort', 'abjad'),
            'storeName' => session('nm_toko') ?: '',
        ]);
    }

    public function store(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);
        $data = $this->validatedCrew($request);

        $crew = null;
        DB::transaction(function () use ($data, $kdToko, $request, &$crew) {
            $kd = strtoupper(trim((string) ($data['kd_crew'] ?? '')));
            if ($kd === '') {
                $kd = $this->nextKdCrew($kdToko, true);
            }

            $existing = Crew::query()
                ->where('kd_toko', $kdToko)
                ->where('kd_crew', $kd)
                ->lockForUpdate()
                ->first();

            $payload = [
                'nm_crew' => strtoupper($data['nm_crew']),
                'nm_toko' => strtoupper(session('nm_toko') ?: ($data['nm_toko'] ?? '')),
                'al_crew' => strtoupper($data['al_crew'] ?? ''),
                'no_telp' => $data['no_telp'] ?? '',
                'aktif' => $request->boolean('aktif'),
            ];

            if ($existing) {
                $existing->update($payload);
                $crew = $existing;

                return;
            }

            $crew = Crew::create($payload + [
                'kd_crew' => $kd,
                'kd_toko' => $kdToko,
            ]);
        });

        return redirect()->route('master.crew.index')->with('success', 'Crew disimpan ('.$crew->kd_crew.').');
    }

    public function update(Request $request, Crew $crew)
    {
        $this->authorizeCrew($crew);
        $data = $this->validatedCrew($request);

        $crew->update([
            'nm_crew' => strtoupper($data['nm_crew']),
            'nm_toko' => strtoupper(session('nm_toko') ?: ($crew->nm_toko ?? '')),
            'al_crew' => strtoupper($data['al_crew'] ?? ''),
            'no_telp' => $data['no_telp'] ?? '',
            'aktif' => $request->boolean('aktif'),
        ]);

        return redirect()->route('master.crew.index', $request->only('q', 'sort'))->with('success', 'Crew diperbarui.');
    }

    public function destroy(Crew $crew)
    {
        $this->authorizeCrew($crew);
        $crew->delete();

        return back()->with('success', 'Crew dihapus.');
    }

    protected function crewQuery(Request $request, ?string $kdToko)
    {
        $query = Crew::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where(function ($q) {
                $q->whereNull('nm_crew')->orWhere('nm_crew', '!=', '-NONE-');
            });

        return $this->applyMasterSearch($query, $request, ['kd_crew', 'nm_crew', 'nm_toko', 'al_crew', 'no_telp']);
    }

    protected function applySort($query, string $sort): void
    {
        if ($sort === 'id_asc') {
            $query->orderBy('no_urut');
        } else {
            $query->orderBy('nm_crew');
        }
    }

    protected function nextKdCrew(?string $kdToko, bool $lock = false): string
    {
        $query = Crew::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('kd_crew', 'like', 'IDCREW-%');

        if ($lock) {
            $query->lockForUpdate();
        }

        $last = 0;
        foreach ($query->pluck('kd_crew') as $code) {
            $n = $this->crewSeqFromCode((string) $code);
            if ($n !== null) {
                $last = max($last, $n);
            }
        }

        $next = $last + 1;
        while (Crew::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('kd_crew', 'IDCREW-'.$next)
            ->exists()) {
            $next++;
        }

        return 'IDCREW-'.$next;
    }

    protected function crewSeqFromCode(string $code): ?int
    {
        $code = strtoupper(trim($code));
        if (! preg_match('/^IDCREW-(\d{1,6})$/', $code, $match)) {
            return null;
        }

        return (int) $match[1];
    }

    protected function validatedCrew(Request $request): array
    {
        return $request->validate([
            'kd_crew' => 'nullable|string|max:50',
            'nm_crew' => 'required|string|max:255',
            'nm_toko' => 'nullable|string|max:255',
            'al_crew' => 'nullable|string|max:255',
            'no_telp' => 'nullable|string|max:50',
            'aktif' => 'nullable',
        ]);
    }

    protected function authorizeCrew(Crew $crew): void
    {
        $kdToko = BranchContext::kdToko();
        if ($kdToko && $crew->kd_toko !== $kdToko) {
            abort(403);
        }
    }
}
