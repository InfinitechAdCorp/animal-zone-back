<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SellerVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\SellerPaymentMethod;

class SellerController extends Controller
{



public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $seller = Auth::user();

        if (!$seller || $seller->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Ensure seller owns at least one item in this order
        $hasItem = OrderItem::where('order_id', $id)
            ->where('seller_id', $seller->id)
            ->exists();

        if (!$hasItem) {
            return response()->json(['message' => 'You do not have permission to update this order.'], 403);
        }

        $order = Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => $order,
        ]);
    }


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

        // ✅ New location fields
        'region' => 'required|string|max:100',
        'province' => 'required|string|max:100',
        'city' => 'required|string|max:100',
        'barangay' => 'required|string|max:100',
        'street_address' => 'required|string|max:255',

        // ✅ Government ID documents
        'gov_id' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'selfie_with_id' => 'required|file|mimes:jpg,jpeg,png|max:2048',
        'proof_of_address' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',

        // ✅ Business permits (conditionally required)
        'dti_sec' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'mayors_permit' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'bir_certificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',

        // ✅ Product documents
        'fda_certificate' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'product_labels' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    // ✅ Create User with location info
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'contact_number' => $request->contact_number,
        'password' => bcrypt($request->password),
        'role' => 'seller',
        'slug' => \Str::slug($request->name . '-' . uniqid()),

        // New address fields
        'region' => $request->region,
        'province' => $request->province,
        'city' => $request->city,
        'barangay' => $request->barangay,
        'street_address' => $request->street_address,
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
    $verification->business_permit_types = $request->business_permit_types; // JSON

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

public function getSellerInfoBySlug($slug)
{
    $seller = User::with('sellerVerification')
        ->where('slug', $slug)
        ->where('role', 'seller')
        ->firstOrFail();

    // ✅ Only return approved sellers
    if (!$seller->sellerVerification || $seller->sellerVerification->status !== 'approved') {
        return response()->json(['message' => 'Seller not found or not approved'], 404);
    }

    return response()->json([
        'id' => $seller->id,
        'name' => $seller->name,
        'slug' => $seller->slug,
        'email' => $seller->email,
        'contact_number' => $seller->contact_number,
        'company_name' => $seller->sellerVerification->company_name,

        // ✅ Add these location fields
        'region' => $seller->region,
        'province' => $seller->province,
        'city' => $seller->city,
        'barangay' => $seller->barangay,
        'street_address' => $seller->street_address,

        'created_at' => $seller->created_at,
        'total_products' => $seller->products()->where('status', 'approved')->count(),
        'average_rating' => 4.5,
    ]);
}

public function getSellerProducts(Request $request)
{
    $seller = $request->user();
    $status = $request->query('status');

    $query = Product::where('seller_id', $seller->id)
        ->with(['productCategory', 'petTypes', 'images']);

    if ($status && in_array($status, ['pending', 'approved', 'rejected', 'out_of_stock'])) {
        if ($status === 'out_of_stock') {
            $query->where('stock', '<=', 0);
        } else {
            $query->where('status', $status);
        }
    }

    $products = $query->orderBy('created_at', 'desc')->get();

    // ✅ Count totals by status
    $counts = [
        'pending' => Product::where('seller_id', $seller->id)->where('status', 'pending')->count(),
        'approved' => Product::where('seller_id', $seller->id)->where('status', 'approved')->count(),
        'rejected' => Product::where('seller_id', $seller->id)->where('status', 'rejected')->count(),
        'outOfStock' => Product::where('seller_id', $seller->id)->where('stock', '<=', 0)->count(),
    ];

    return response()->json([
        'data' => $products,
        'counts' => $counts,
    ]);
}


// ✅ Get products by seller slug
public function getSellerProductsBySlug($slug)
{
    $seller = User::where('slug', $slug)
        ->where('role', 'seller')
        ->firstOrFail();

    $products = Product::with(['productCategory', 'petTypes', 'images'])
        ->where('seller_id', $seller->id)
        ->where('status', 'approved')
        ->get();

    return response()->json(
        $products->map(function ($product) {
            return [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'price' => $product->price,
                'original_price' => null,
                'stock' => $product->stock,
                'category' => $product->productCategory?->name ?? 'Uncategorized',
                'seller_name' => $product->seller?->name ?? 'Unknown Seller',
                'images' => $product->images->pluck('image_path'),
                'rating' => 4.5,
                'review_count' => 0,
            ];
        })
    );
}

public function getPaymentQRCodes($id)
{
    $seller = User::find($id);

    if (!$seller) {
        return response()->json(['message' => 'Seller not found'], 404);
    }

    // ✅ Get from seller_payment_methods table
    $methods = SellerPaymentMethod::where('seller_id', $seller->id)->get();

    // ✅ Build array of enabled methods with QR paths
    $qrs = [];
    foreach ($methods as $method) {
        if ($method->enabled && $method->qr_path) {
            $qrs[$method->method] = asset($method->qr_path); // ✅ full URL
        }
    }

    // ✅ Return only existing and enabled payment methods
    return response()->json([
        'seller_id' => $seller->id,
        'payment_qrs' => $qrs,
    ]);
}

public function updateProduct(Request $request, $id)
{
    $seller = $request->user();

    $product = Product::where('id', $id)
        ->where('seller_id', $seller->id)
        ->firstOrFail();

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'stock' => 'required|integer|min:0',
    ]);

    $product->update($validated);

    return response()->json([
        'message' => 'Product updated successfully.',
        'product' => $product,
    ]);
}


public function updatePaymentMethods(Request $request)
{
    $seller = $request->user();
    $methods = ['gcash', 'paymaya', 'bpi', 'bdo'];

    foreach ($methods as $method) {
        $enabled = $request->boolean("{$method}_enabled");
        $fileField = "{$method}_qr";

        $record = \App\Models\SellerPaymentMethod::firstOrNew([
            'seller_id' => $seller->id,
            'method' => $method,
        ]);

        $record->enabled = $enabled;

        // ✅ Handle file upload if provided
        if ($request->hasFile($fileField)) {
            $file = $request->file($fileField);

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Ensure directory exists
            $uploadPath = public_path('uploads/payment_qr');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Move file to uploads/payment_qr/
            $file->move($uploadPath, $filename);

            // ✅ Delete old QR file if exists
            if ($record->qr_path && file_exists(public_path($record->qr_path))) {
                @unlink(public_path($record->qr_path));
            }

            // Save relative path to DB
            $record->qr_path = 'uploads/payment_qr/' . $filename;
        }

        $record->save();
    }

    // ✅ Reload seller with payment methods and verification
    $seller->load(['paymentMethods', 'sellerVerification']);

    $verificationStatus = optional($seller->sellerVerification)->status ?? 'pending';
    $documents = null;

    if ($seller->sellerVerification) {
        $documents = [
            'gov_id'           => $seller->sellerVerification->gov_id,
            'selfie_with_id'   => $seller->sellerVerification->selfie_with_id,
            'proof_of_address' => $seller->sellerVerification->proof_of_address,
            'dti_sec'          => $seller->sellerVerification->dti_sec,
            'mayors_permit'    => $seller->sellerVerification->mayors_permit,
            'bir_certificate'  => $seller->sellerVerification->bir_certificate,
            'fda_certificate'  => $seller->sellerVerification->fda_certificate,
            'product_labels'   => $seller->sellerVerification->product_labels,
        ];
    }

    return response()->json([
        'message' => 'Payment methods updated successfully.',
        'seller' => [
            'id'                  => $seller->id,
            'name'                => $seller->name,
            'email'               => $seller->email,
            'contact_number'      => $seller->contact_number,
            'role'                => $seller->role,
            'verification_status' => $verificationStatus,
            'documents'           => $documents,
            'payment_methods'     => $seller->paymentMethods,
        ],
    ]);
}


public function getPaymentMethods(Request $request)
{
    $seller = $request->user();
    return response()->json($seller->paymentMethods);
}

}