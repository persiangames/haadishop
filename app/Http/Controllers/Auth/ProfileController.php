<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load(['addresses', 'roles']);

        return response()->json([
            'user' => $user,
        ], 200);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['sometimes', 'string', 'max:32', 'unique:users,phone,' . $user->id],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ], 200);
    }

    public function addresses(Request $request)
    {
        $user = $request->user();
        $addresses = $user->addresses;

        return response()->json([
            'addresses' => $addresses,
        ], 200);
    }

    public function addAddress(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'country' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64'],
            'city' => ['required', 'string', 'max:64'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:32'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        // اگر اولین آدرس است یا is_default باشد، بقیه را غیرفعال می‌کنیم
        if ($validated['is_default'] ?? false) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($validated);

        return response()->json([
            'message' => 'Address added successfully.',
            'address' => $address,
        ], 201);
    }

    public function updateAddress(Request $request, $id)
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:100'],
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:32'],
            'country' => ['sometimes', 'string', 'max:64'],
            'province' => ['sometimes', 'string', 'max:64'],
            'city' => ['sometimes', 'string', 'max:64'],
            'address_line' => ['sometimes', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'string', 'max:32'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        // اگر is_default باشد، بقیه را غیرفعال می‌کنیم
        if (isset($validated['is_default']) && $validated['is_default']) {
            $user->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json([
            'message' => 'Address updated successfully.',
            'address' => $address->fresh(),
        ], 200);
    }

    public function deleteAddress(Request $request, $id)
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);
        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully.',
        ], 200);
    }
}

