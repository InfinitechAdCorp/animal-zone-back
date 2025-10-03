<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SellerVerification;
use App\Models\Product;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function statistics()
    {
        return response()->json([
            'total_users'       => User::count(),
            'total_sellers'     => SellerVerification::count(),
            'pending_sellers'   => SellerVerification::where('status', 'pending')->count(),
            'approved_today'    => SellerVerification::where('status', 'approved')
                                    ->whereDate('reviewed_at', now()->toDateString())
                                    ->count(),
            'approved_sellers'  => SellerVerification::where('status', 'approved')->count(),
        ]);
    }

    // ✅ List all products (with seller + category info)
    public function products()
    {
        $products = Product::with(['seller', 'productCategory', 'petTypes'])->get();

        return response()->json($products);
    }

public function updateProductStatus(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $request->validate([
        'status' => 'required|in:approved,rejected',
    ]);

    $product->status = $request->status;
    $product->save();

    return response()->json([
        'message' => "Product {$request->status} successfully!",
        'product' => $product,
    ]);
}

public function getPendingProducts()
{
    $products = Product::where('status', 'pending')->with('documents')->get();
    return response()->json($products);
}

public function getProducts(Request $request)
{
    $status = $request->query('status');
    $query = Product::with('documents');

    if ($status) {
        $query->where('status', $status);
    }

    return response()->json($query->get());
}


}
