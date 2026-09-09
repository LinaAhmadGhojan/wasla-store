<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Return a paginated list of users for admin.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $q = $request->query('q');

        $query = User::query()->select(['id', 'name', 'email', 'phone', 'created_at']);

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($paginator);
    }

    /**
     * Create a new user (admin action)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:32',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create($data);

        return response()->json($user, 201);
    }

    /**
     * Update an existing user
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:32',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($data['password'])) {
            $user->password = $data['password'];
            unset($data['password']);
        }

        $user->fill($data);
        $user->save();

        return response()->json($user);
    }
}
