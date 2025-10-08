<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = Cart::with(['product.seller', 'product.images'])
            ->where('user_id', Auth::id())
            ->get();

        // Group by seller
        $groupedCart = $cartItems->groupBy(function ($item) {
            return $item->product->seller_id;
        })->map(function ($items, $sellerId) {
            $seller = $items->first()->product->seller;
            
            return [
                'seller_id' => $sellerId,
                'seller_name' => $seller->name ?? 'Unknown Seller',
                'seller_slug' => $seller->slug ?? null,
                'items' => $items->map(function ($item) {
                    $primaryImage = $item->product->images->where('is_primary', 1)->first();
                    
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'name' => $item->product->name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'stock' => $item->product->stock, // ✅ Include stock
                        'image' => $primaryImage ? $primaryImage->image_path : ($item->product->images->first()->image_path ?? null),
                    ];
                })
            ];
        })->values();

        return response()->json($groupedCart);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);

        if ($request->quantity > $product->stock) {
            return response()->json([
                'message' => 'Requested quantity exceeds available stock',
                'available_stock' => $product->stock
            ], 400);
        }

        $existingCart = Cart::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingCart) {
            $newQuantity = $existingCart->quantity + $request->quantity;
            
            if ($newQuantity > $product->stock) {
                return response()->json([
                    'message' => 'Total quantity would exceed available stock',
                    'available_stock' => $product->stock,
                    'current_in_cart' => $existingCart->quantity
                ], 400);
            }
            
            $existingCart->quantity = $newQuantity;
            $existingCart->save();
            
            return response()->json([
                'message' => 'Cart updated',
                'cart' => $existingCart
            ]);
        }

        $cart = Cart::create([
            'user_id' => Auth::id(),
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'price' => $product->price,
        ]);

        return response()->json([
            'message' => 'Added to cart',
            'cart' => $cart
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($request->quantity > $cart->product->stock) {
            return response()->json([
                'message' => 'Requested quantity exceeds available stock',
                'available_stock' => $cart->product->stock
            ], 400);
        }

        $cart->quantity = $request->quantity;
        $cart->save();

        return response()->json([
            'message' => 'Cart updated',
            'cart' => $cart
        ]);
    }

    public function destroy($id)
    {
        $cart = Cart::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $cart->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }
}
