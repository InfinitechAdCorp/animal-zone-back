<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SellerVerification;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductDocument;
use App\Models\Order;

class AdminController extends Controller
{
    public function getProductDocuments($id)
    {
        $documents = ProductDocument::where('product_id', $id)->get();

        if ($documents->isEmpty()) {
            return response()->json([], 200);
        }

        return response()->json($documents, 200);
    }

     public function statistics()
    {
        // 🧮 Basic user and seller stats
        $totalUsers = User::count();
        $totalSellers = SellerVerification::count();
        $pendingSellers = SellerVerification::where('status', 'pending')->count();
        $approvedToday = SellerVerification::where('status', 'approved')
            ->whereDate('reviewed_at', now()->toDateString())
            ->count();
        $approvedSellers = SellerVerification::where('status', 'approved')->count();

        // 🧾 Product, order, and revenue analytics
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total');

        // 📊 Monthly revenue breakdown (Jan–Dec)
        $monthlyRevenue = Order::selectRaw('MONTH(created_at) as month, SUM(total) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'total_users'       => $totalUsers,
            'total_sellers'     => $totalSellers,
            'pending_sellers'   => $pendingSellers,
            'approved_today'    => $approvedToday,
            'approved_sellers'  => $approvedSellers,

            // Added for dashboard
            'total_products'    => $totalProducts,
            'total_orders'      => $totalOrders,
            'total_revenue'     => $totalRevenue,
            'monthly_revenue'   => $monthlyRevenue,
        ]);
    }


    // ✅ List all products (with seller + category + images)
    public function products()
    {
        $products = Product::with([
            'seller:id,name',
            'productCategory:id,name',
            'petTypes:id,name',
            'documents',
            'images', // ✅ added
        ])->get();

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

    // ✅ Pending products only (also with images)
    public function getPendingProducts()
    {
        $products = Product::where('status', 'pending')
            ->with([
                'seller:id,name',
                'productCategory:id,name',
                'petTypes:id,name',
                'documents',
                'images', // ✅ added
            ])
            ->get();

        return response()->json($products);
    }

    // ✅ Filter by status (approved/pending/rejected)
    public function getProducts(Request $request)
    {
        $status = $request->query('status');
        $query = Product::with([
            'seller:id,name',
            'productCategory:id,name',
            'petTypes:id,name',
            'documents',
            'images', // ✅ added
        ]);

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json($query->get());
    }
}
