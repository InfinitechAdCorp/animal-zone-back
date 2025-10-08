<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
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

        // ✅ 2️⃣ Save each item to order_items table (if you have one)
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'quantity'   => $item['quantity'] ?? 1,
                    'price'      => $item['price'] ?? 0,
                ]);
            }
        }

        // ✅ 3️⃣ Clear the user's cart
        if (method_exists($user, 'cartItems')) {
            $user->cartItems()->delete();
        }

        return response()->json([
            'message'   => 'Order placed successfully',
            'order_id'  => $order->id,
        ]);
    }
}
