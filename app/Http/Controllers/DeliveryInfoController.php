<?php

// app/Http/Controllers/DeliveryInfoController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryInfo;

class DeliveryInfoController extends Controller
{
    public function show(Request $request)
    {
        $deliveryInfo = DeliveryInfo::where('user_id', $request->user()->id)->first();
        return response()->json($deliveryInfo);
    }

    public function storeOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string',
        ]);

        $deliveryInfo = DeliveryInfo::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json(['message' => 'Delivery info saved successfully', 'data' => $deliveryInfo]);
    }
}
