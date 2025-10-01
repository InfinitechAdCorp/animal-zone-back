<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SellerVerification;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function register(Request $request)
    {
        // ✅ Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'gov_id_type' => 'required|string',
            'business_permit_types' => 'required|json',
            
            // Government ID documents
            'gov_id' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'selfie_with_id' => 'required|file|mimes:jpg,jpeg,png|max:2048',
            'proof_of_address' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            
            // Business permits (conditionally required based on selection)
            'dti_sec' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'mayors_permit' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'bir_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            
            // Product documents
            'fda_certificate' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'product_labels' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // ✅ Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'seller',
        ]);

        // ✅ File upload helper
        $uploadFile = function ($key, $folder) use ($request) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $file->move(public_path("uploads/{$folder}"), $filename);
                return "uploads/{$folder}/{$filename}";
            }
            return null;
        };

        // ✅ Save seller verification
        $verification = new SellerVerification();
        $verification->seller_id = $user->id;
        $verification->company_name = $request->company_name;
        $verification->gov_id_type = $request->gov_id_type;
        $verification->business_permit_types = $request->business_permit_types; // Store as JSON
        
        // Upload files
        $verification->gov_id = $uploadFile('gov_id', 'ids');
        $verification->selfie_with_id = $uploadFile('selfie_with_id', 'selfies');
        $verification->proof_of_address = $uploadFile('proof_of_address', 'address');
        $verification->dti_sec = $uploadFile('dti_sec', 'permits');
        $verification->mayors_permit = $uploadFile('mayors_permit', 'permits');
        $verification->bir_certificate = $uploadFile('bir_certificate', 'permits');
        $verification->fda_certificate = $uploadFile('fda_certificate', 'fda');
        $verification->product_labels = $uploadFile('product_labels', 'labels');
        $verification->status = 'pending';
        $verification->save();

        return response()->json([
            'message' => 'Seller registered successfully. Verification pending.',
            'user' => $user,
            'verification' => $verification,
        ], 201);
    }
}