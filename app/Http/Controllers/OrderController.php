<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{

    public function getSellerOrders()
{
    $sellerId = auth()->id();

    $orders = \App\Models\Order::with(['user', 'orderItems.product'])
        ->whereHas('orderItems', function ($query) use ($sellerId) {
            $query->where('seller_id', $sellerId);
        })
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($orders);
}

public function updateStatus(Request $request, $id)
{
    $validated = $request->validate([
        'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
    ]);

    $sellerId = auth()->id();

    $order = \App\Models\Order::whereHas('orderItems', function ($query) use ($sellerId) {
        $query->where('seller_id', $sellerId);
    })->findOrFail($id);

    $order->update(['status' => $validated['status']]);

    return response()->json(['message' => 'Order status updated successfully.']);
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_info'   => 'required|array',
            'payment_method'  => 'required|string',
            'items'           => 'required|array',
            'subtotal'        => 'required|numeric',
            'shipping'        => 'required|numeric',
            'total'           => 'required|numeric',
        ]);

        $user = Auth::user();

        // ✅ 1️⃣ Create the order
        $order = Order::create([
            'user_id'        => $user->id,
            'delivery_info'  => $validated['delivery_info'],
            'payment_method' => $validated['payment_method'],
            'subtotal'       => $validated['subtotal'],
            'shipping'       => $validated['shipping'],
            'total'          => $validated['total'],
            'status'         => 'pending',
        ]);

        // ✅ 2️⃣ Save each item and deduct stock
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);

            if ($product) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'seller_id'  => $product->seller_id,
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);

                // ✅ Deduct stock
                $newStock = max(0, $product->stock - $item['quantity']);
                $product->update(['stock' => $newStock]);
            }
        }

        // ✅ 3️⃣ Delete cart items (if model exists)
        if (method_exists($user, 'cartItems')) {
            $user->cartItems()->delete();
        }

        return response()->json([
            'message'  => 'Order placed successfully!',
            'order_id' => $order->id,
        ]);
    }

    // ✅ 4️⃣ Cancel order and restock items
    public function cancel($id)
    {
        $user = Auth::user();
        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->status === 'cancelled') {
            return response()->json(['message' => 'Order already cancelled.'], 400);
        }

        // ✅ Restock products
        $items = $order->orderItems;
        foreach ($items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->increment('stock', $item->quantity);
            }
        }

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Order cancelled successfully, stock returned to inventory.'
        ]);
    }

    public function getBuyerOrders()
{
    $user = Auth::user();

    $orders = Order::with(['orderItems.product'])
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($order) {
            return [
                'id' => $order->id,
                'created_at' => $order->created_at,
                'total_amount' => $order->total,
                'status' => $order->status,
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->product->name ?? 'Unknown Product',
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                    ];
                }),
            ];
        });

    return response()->json($orders);
}


}
