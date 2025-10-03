<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SellerVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class SellerController extends Controller
{
    public function register(Request $request)
    {
        // ✅ Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contact_number' => 'nullable|string|max:20',
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
            'contact_number' => $request->contact_number,
            'password' => bcrypt($request->password),
            'role' => 'seller',
        ]);

          // ✅ Send Email Verification
        $user->sendEmailVerificationNotification();

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
            'message' => 'Seller registered successfully. Your account is under review. Please check your email to verify your email address before logging in.',
            'user' => $user,
            'verification' => $verification,
        ], 201);

    }

        public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = SellerVerification::with('seller');
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $sellers = $query->orderBy('created_at', 'desc')->get();
        
        // No need for manual json_decode anymore!
        
        return response()->json([
            'success' => true,
            'data'    => $sellers
        ]);
    }

      public function products(Request $request)
    {
       
    }

    public function show($id)
    {
        $seller = SellerVerification::with('seller')->findOrFail($id);
        
        // No need for manual json_decode anymore!
        
        return response()->json([
            'success' => true,
            'data'    => $seller
        ]);
    }

    // ✅ Approve seller
    public function approve(Request $request, $id)
    {
        $seller = SellerVerification::findOrFail($id);

        $seller->status = 'approved';
        $seller->remarks = $request->remarks ?? null;
        $seller->reviewed_by = Auth::id();
        $seller->reviewed_at = now();
        $seller->save();

        return response()->json([
            'success' => true,
            'message' => 'Seller approved successfully',
            'data'    => $seller
        ]);
    }

    // ✅ Reject seller
    public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'required|string'
        ]);

        $seller = SellerVerification::findOrFail($id);

        $seller->status = 'rejected';
        $seller->remarks = $request->remarks;
        $seller->reviewed_by = Auth::id();
        $seller->reviewed_at = now();
        $seller->save();

        return response()->json([
            'success' => true,
            'message' => 'Seller rejected',
            'data'    => $seller
        ]);
    }

    public function getDocument($id, $type)
{
    $seller = SellerVerification::findOrFail($id);

    if (!$seller || !in_array($type, [
        'gov_id',
        'selfie_with_id',
        'proof_of_address',
        'dti_sec',
        'mayors_permit',
        'bir_certificate',
        'fda_certificate',
        'product_labels'
    ])) {
        return response()->json(['message' => 'Invalid document type'], 400);
    }

    $path = $seller->$type;

    if (!$path || !file_exists(public_path($path))) {
        return response()->json(['message' => 'File not found'], 404);
    }

    return response()->file(public_path($path));
}


}