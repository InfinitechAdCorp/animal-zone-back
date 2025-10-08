<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserController extends Controller
{
    /**
     * ✅ Get current authenticated user profile
     */
    public function show()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * ✅ Update user profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'contact_number' => 'nullable|string|max:20',
            'region' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user
        ]);
    }
}
