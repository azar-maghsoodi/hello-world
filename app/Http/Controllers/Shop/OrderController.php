<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Shop/Orders/Index', [
            'orders' => $request->user()
                ->salesOrders()
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(Request $request, SalesOrder $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load('items.product:id,slug');

        return Inertia::render('Shop/Orders/Show', [
            'order' => $order,
        ]);
    }
}
