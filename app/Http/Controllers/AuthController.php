<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\SellerVerification;

class AuthController extends Controller
{
    
    public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::with('sellerVerification')->where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid login credentials.',
        ], 401);
    }

    // 🚫 Prevent login if email not verified
    if (is_null($user->email_verified_at)) {
        return response()->json([
            'success' => false,
            'message' => 'Please verify your email address before logging in.',
        ], 403);
    }

    $roleName = $user->role ?? 'user';
    $token = $user->createToken($roleName . '-token')->plainTextToken;

    $verificationStatus = null;
    if ($roleName === 'seller') {
        $verificationStatus = optional($user->sellerVerification)->status ?? 'pending';
    }

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'token'   => $token,
        'user'    => [
            'id'                  => $user->id,
            'name'                => $user->name,
            'email'               => $user->email,
            'contact_number'      => $user->contact_number,
            'role'                => $roleName,
            'verification_status' => $verificationStatus,
        ]
    ], 200);
}

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }

    public function me(Request $request)
{
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $verificationStatus = null;
    $documents = null;

    if ($user->role === 'seller') {
        $verification = $user->sellerVerification;
        $verificationStatus = optional($verification)->status ?? 'pending';
        
        if ($verification) {
            $documents = [
                'gov_id'          => $verification->gov_id,
                'selfie_with_id'  => $verification->selfie_with_id,
                'proof_of_address'=> $verification->proof_of_address,
                'dti_sec'         => $verification->dti_sec,
                'mayors_permit'   => $verification->mayors_permit,
                'bir_certificate' => $verification->bir_certificate,
                'fda_certificate' => $verification->fda_certificate,
                'product_labels'  => $verification->product_labels,
            ];
        }

        // ✅ Load payment methods relationship for sellers
        $user->load('paymentMethods');
    }

    return response()->json([
        'id'                  => $user->id,
        'name'                => $user->name,
        'email'               => $user->email,
        'contact_number'      => $user->contact_number,
        'role'                => $user->role,
        'verification_status' => $verificationStatus,
        'documents'           => $documents,
        
        // 🧩 Payment QR paths from users table (for backward compatibility)
        'gcash_qr'   => $user->gcash_qr,
        'paymaya_qr' => $user->paymaya_qr,
        'bpi_qr'     => $user->bpi_qr,
        'bdo_qr'     => $user->bdo_qr,
        
        // ✅ Payment methods from seller_payment_methods table (new structure)
        'payment_methods' => $user->role === 'seller' ? $user->paymentMethods : null,
    ]);
}

public function register(Request $request)
{
    $request->validate([
        'name'           => 'required|string|max:100',
        'email'          => 'required|email|unique:users,email',
        'password'       => 'required|string|min:6',
        'contact_number' => 'required|string|max:15',
    ]);

    $user = User::create([
        'name'           => $request->name,
        'email'          => $request->email,
        'contact_number' => $request->contact_number,
        'password'       => Hash::make($request->password),
        'role'           => 'buyer',
    ]);

    // ✅ Send verification email
    $user->sendEmailVerificationNotification();

    return response()->json([
        'success' => true,
        'message' => 'Registered successfully. Please check your email for verification link.',
        'user'    => $user,
    ], 201);
}


}
