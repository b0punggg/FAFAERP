<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\StaffAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserMasterController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        $query = $this->applyMasterSearch(User::query(), $request, ['nm_user', 'otoritas', 'kd_toko']);
        $users = $query->with(['branch', 'company'])->orderBy('nm_user')->paginate(20);
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $retailId = Company::where('type', 'retail')->value('id');
        $editUser = $request->filled('edit') ? User::find($request->edit) : null;

        return view('admin.master.users', compact('users', 'branches', 'retailId', 'editUser'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nm_user' => 'required|string|max:30|unique:pemakai,nm_user',
            'pass' => 'required|string|min:4',
            'otoritas' => ['required', Rule::in(array_keys(StaffAccess::geraiRoleOptions()))],
            'branch_id' => 'required_if:otoritas,1,3|nullable|exists:branches,id',
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:100',
        ]);

        $branch = ! empty($data['branch_id']) ? Branch::find($data['branch_id']) : null;

        User::create([
            'nm_user' => $data['nm_user'],
            'pass' => Hash::make($data['pass']),
            'otoritas' => $data['otoritas'],
            'no_hp' => $data['no_hp'] ?? '',
            'alamat' => $data['alamat'] ?? '',
            'foto' => '',
            'kd_toko' => $branch?->kd_toko ?? '',
            'company_id' => $branch?->company_id,
            'branch_id' => $branch?->id,
        ]);

        return back()->with('success', 'Pengguna ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $allowed = array_keys(StaffAccess::geraiRoleOptions());
        if (StaffAccess::role($user) === 'admin') {
            $allowed[] = '2';
        }
        $data = $request->validate([
            'otoritas' => ['required', Rule::in($allowed)],
            'branch_id' => 'required_if:otoritas,1,3|nullable|exists:branches,id',
            'pass' => 'nullable|string|min:4',
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:100',
        ]);

        $branch = ! empty($data['branch_id']) ? Branch::find($data['branch_id']) : null;

        $user->otoritas = $data['otoritas'];
        $user->branch_id = $branch?->id;
        $user->kd_toko = $branch?->kd_toko ?? '';
        $user->company_id = $branch?->company_id;
        $user->no_hp = $data['no_hp'] ?? '';
        $user->alamat = $data['alamat'] ?? '';

        if (! empty($data['pass'])) {
            $user->pass = Hash::make($data['pass']);
        }

        $user->save();

        return redirect()->route('master.users.index', $request->only('q'))->with('success', 'Pengguna diperbarui.');
    }

    public function destroy(User $user)
    {
        if ((int) $user->id_user === (int) auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna dihapus.');
    }
}
