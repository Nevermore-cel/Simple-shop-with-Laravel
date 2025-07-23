<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        // Получение заказов текущего пользователя (покупателя)
        $orders = Order::where('user_id', auth()->id())->get();
        return OrderResource::collection($orders);
    }

    public function indexAll()
    {
        // Получение всех заказов (только для администратора)
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $orders = Order::all();
        return OrderResource::collection($orders);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = auth()->user();
        $products = $request->input('products');
        $totalAmount = 0;

        DB::beginTransaction();

        try {
            foreach ($products as $productData) {
                $product = Product::findOrFail($productData['id']);
                $quantity = $productData['quantity'];

                if ($product->quantity < $quantity) {
                    throw new \Exception("Not enough {$product->name} in stock.");
                }

                $totalAmount += $product->price * $quantity;
            }

            if ($user->balance < $totalAmount) {
                throw new \Exception("Insufficient balance.");
            }

            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'status' => 'new', //  начальный статус
            ]);

            foreach ($products as $productData) {
                $product = Product::findOrFail($productData['id']);
                $quantity = $productData['quantity'];

                $order->products()->attach($product->id, ['quantity' => $quantity]);

                $product->decrement('quantity', $quantity);
                $user->decrement('balance', $product->price * $quantity);
            }

            DB::commit();

            Log::info("New order created. Order ID: {$order->id}, User ID: {$user->id}");

            $order = Order::with('products')->find($order->id);  // продукты для ответа
            return new OrderResource($order);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error("Order creation failed. User ID: {$user->id}, Error: {$e->getMessage()}");
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateStatus(Request $request, Order $order)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,confirmed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $oldStatus = $order->status;
        $newStatus = $request->input('status');

        if ($oldStatus === $newStatus) {
            return response()->json(['message' => 'Order already has this status'], 400);
        }

        DB::beginTransaction();

        try {
            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                // Возвращаем товары на склад и деньги пользователю
                foreach ($order->products as $product) {
                    $quantity = $product->pivot->quantity;
                    $product->increment('quantity', $quantity);
                    $order->user->increment('balance', $product->price * $quantity);
                }
            } elseif ($newStatus === 'confirmed' && $oldStatus === 'cancelled') {
                // Если заказ подтверждается после отмены, нужно проверить наличие товаров и баланс
                foreach ($order->products as $product) {
                    $quantity = $product->pivot->quantity;
                    if ($product->quantity < $quantity) {
                        throw new \Exception("Not enough {$product->name} in stock.");
                    }
                }

                if (auth()->user()->balance < $order->total_amount) {
                    throw new \Exception("Insufficient balance.");
                }

                foreach ($order->products as $product) {
                    $quantity = $product->pivot->quantity;
                    $product->decrement('quantity', $quantity);
                    $order->user->decrement('balance', $product->price * $quantity);
                }
            }

            $order->update(['status' => $newStatus]);
            DB::commit();

            Log::info("Order status updated. Order ID: {$order->id}, New Status: {$newStatus}");
            return new OrderResource($order);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error("Order status update failed. Order ID: {$order->id}, Error: {$e->getMessage()}");
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}