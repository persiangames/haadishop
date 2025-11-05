<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * لیست کاربران
     */
    public function index(Request $request)
    {
        $query = User::query();

        // فیلتر بر اساس وضعیت
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // جستجو
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // مرتب‌سازی
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $users = $query->with(['orders', 'roles'])->paginate($perPage);

        return response()->json([
            'users' => $users,
        ], 200);
    }

    /**
     * جزئیات کاربر
     */
    public function show($id)
    {
        $user = User::with(['orders', 'addresses', 'roles', 'loyaltyPoints'])
            ->findOrFail($id);

        return response()->json([
            'user' => $user,
        ], 200);
    }

    /**
     * به‌روزرسانی وضعیت کاربر
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:active,inactive,banned'],
        ]);

        $user = User::findOrFail($id);
        $user->update(['status' => $request->status]);

        return response()->json([
            'message' => 'User status updated successfully.',
            'user' => $user,
        ], 200);
    }

    /**
     * اختصاص نقش به کاربر
     */
    public function assignRole(Request $request, $id)
    {
        $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user = User::findOrFail($id);
        $user->roles()->syncWithoutDetaching([$request->role_id]);

        return response()->json([
            'message' => 'Role assigned successfully.',
            'user' => $user->load('roles'),
        ], 200);
    }

    /**
     * حذف نقش از کاربر
     */
    public function removeRole(Request $request, $id, $roleId)
    {
        $user = User::findOrFail($id);
        $user->roles()->detach($roleId);

        return response()->json([
            'message' => 'Role removed successfully.',
        ], 200);
    }
}

