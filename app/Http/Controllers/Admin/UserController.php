<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceSighting;
use App\Models\Role;
use App\Models\StoreCreditTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role')->withCount('deviceTokens')->orderBy('created_at', 'desc');

        if ($search = $request->query('q')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->query('shared_device') === '1') {
            $sharedHashes = DeviceSighting::query()
                ->select('token_hash')
                ->groupBy('token_hash')
                ->havingRaw('COUNT(DISTINCT user_id) > 1')
                ->pluck('token_hash');
            $userIds = DeviceSighting::query()
                ->whereIn('token_hash', $sharedHashes)
                ->pluck('user_id')
                ->unique();
            $query->whereIn('id', $userIds);
        }

        $users = $query->paginate(15)->withQueryString();

        $sharedByUser = [];
        foreach ($users as $user) {
            $siblings = $user->siblingAccountsOnDevices();
            $sharedByUser[$user->id] = $siblings;
        }

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('label')->get(),
            'search' => $search,
            'sharedByUser' => $sharedByUser,
            'filterShared' => $request->query('shared_device') === '1',
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'user' => new User(['is_active' => true]),
            'roles' => Role::orderBy('label')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:32',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'required|boolean',
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'تم إنشاء المستخدم.');
    }

    public function edit(User $user)
    {
        $sightings = DeviceSighting::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_seen_at')
            ->get();

        $siblings = $user->siblingAccountsOnDevices();

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('label')->get(),
            'sightings' => $sightings,
            'siblings' => $siblings,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:32',
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'required|boolean',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $wasActive = (bool) $user->is_active;
        $user->update($data);

        // تعطيل مؤقت: إسقاط جلسات API
        if ($wasActive && ! $user->is_active) {
            $user->tokens()->delete();
        }

        return redirect()->route('admin.users.index')->with('success', 'تم تحديث المستخدم.');
    }

    /** الحذف ممنوع — عطّلي الحساب مؤقتاً بدل الحذف. */
    public function destroy(User $user)
    {
        return redirect()
            ->route('admin.users.edit', $user)
            ->with('error', 'حذف الحساب ممنوع. عطّلي الحساب مؤقتاً من حالة التفعيل.');
    }

    public function addCredit(Request $request, User $user)
    {
        $data = $request->validate([
            'amount_syp' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $user->increment('store_credit_syp', $data['amount_syp']);

        $user->storeCreditTransactions()->create([
            'amount_syp' => $data['amount_syp'],
            'type' => StoreCreditTransaction::TYPE_TOP_UP,
            'notes' => $data['notes'] ?: 'شحن يدوي من قِبَل '.auth()->user()->name,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'تم شحن '.number_format($data['amount_syp']).' ل.س لرصيد '.$user->name.'.');
    }
}
