<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::with('items')
                       ->where('user_id', $user->id)
                       ->orderByDesc('created_at')
                       ->paginate(25);

        return response()->json($orders);
    }

    public function staffIndex(Request $request)
    {
        $orders = Order::with('items', 'user')
                       ->orderByDesc('created_at')
                       ->paginate(50);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $user->isStaff() && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($order->load('items'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $rules = [
            'items'        => 'required|array|min:1',
            'items.*.sku'  => 'required|string|max:20',
            'items.*.name' => 'required|string|max:200',
            'items.*.price'=> 'required|numeric|min:0',
            'items.*.qty'  => 'required|integer|min:1|max:100',
            'client_name'  => 'nullable|string|max:100',
            'client_phone' => 'nullable|string|max:20',
        ];

        if (! $user) {
            $rules['client_email'] = 'required|email|max:255';
            $rules['client_phone'] = 'required|string|max:20';
            $rules['client_name']  = 'required|string|max:100';
        } else {
            $rules['client_email'] = 'nullable|email|max:255';
        }

        $request->validate($rules);

        $items = $request->items;

        // Validate prices against database
        $priceErrors = [];
        foreach ($items as $item) {
            $product = Product::where('sku', strtoupper($item['sku']))->where('active', true)->first();
            if (! $product) { $priceErrors[] = "SKU {$item['sku']} not found or inactive."; continue; }
            if ((int) $product->price !== (int) $item['price']) {
                $priceErrors[] = "Price mismatch for {$item['sku']}: expected {$product->price}, got {$item['price']}.";
            }
        }
        if (! empty($priceErrors)) {
            return response()->json(['message' => 'Price validation failed.', 'errors' => $priceErrors], 422);
        }

        $total        = collect($items)->sum(fn($i) => $i['price'] * $i['qty']);
        $ref          = 'LA-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        $receiptToken = Str::random(48);

        $order = Order::create([
            'reference'     => $ref,
            'receipt_token' => $receiptToken,
            'user_id'       => $user?->id,
            'client_name'   => $user?->name  ?? strip_tags($request->client_name  ?? 'Guest'),
            'client_email'  => $user?->email ?? $request->client_email,
            'client_phone'  => $user?->phone ?? $request->client_phone,
            'total'         => $total,
            'status'        => 'pending',
        ]);

        foreach ($items as $item) {
            OrderItem::create([
                'order_id'         => $order->id,
                'product_sku'      => strtoupper(strip_tags($item['sku'])),
                'product_name'     => strip_tags($item['name']),
                'product_category' => isset($item['category']) ? strip_tags($item['category']) : null,
                'unit_price'       => $item['price'],
                'quantity'         => $item['qty'],
                'subtotal'         => $item['price'] * $item['qty'],
            ]);
        }

        return response()->json($order->load('items'), 201);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,paid,delivered,cancelled',
        ]);

        $data = ['status' => $request->status];
        if ($request->status === 'paid')      $data['paid_at']      = now();
        if ($request->status === 'delivered') $data['delivered_at'] = now();

        $order->update($data);
        $order->refresh();

        return response()->json($order->load('items'));
    }

    public function publicReceipt(string $token)
    {
        $order = Order::with('items')->where('receipt_token', $token)->first();

        if (! $order) {
            return response()->json(['message' => 'Receipt not found.'], 404);
        }

        return response()->json([
            'reference'    => $order->reference,
            'client_name'  => $order->client_name,
            'client_email' => $order->client_email,
            'total'        => $order->total,
            'status'       => $order->status,
            'created_at'   => $order->created_at,
            'items'        => $order->items->map(fn($i) => [
                'name'     => $i->product_name,
                'category' => $i->product_category,
                'qty'      => $i->quantity,
                'price'    => $i->unit_price,
                'subtotal' => $i->subtotal,
            ]),
        ]);
    }
}
