<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Orders/Index', [
            'orders' => SalesOrder::query()
                ->with('user:id,name,email')
                ->when($request->string('status')->trim()->isNotEmpty(), fn ($query) => $query->where(
                    'status', $request->string('status')->trim()
                ))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('status'),
            'statuses' => [
                SalesOrder::STATUS_PENDING,
                SalesOrder::STATUS_PROCESSING,
                SalesOrder::STATUS_COMPLETED,
                SalesOrder::STATUS_CANCELLED,
            ],
        ]);
    }

    public function show(SalesOrder $order): Response
    {
        $order->load('user:id,name,email', 'items.product:id,slug');

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
            'statuses' => [
                SalesOrder::STATUS_PENDING,
                SalesOrder::STATUS_PROCESSING,
                SalesOrder::STATUS_COMPLETED,
                SalesOrder::STATUS_CANCELLED,
            ],
        ]);
    }

    public function update(Request $request, SalesOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', [
                SalesOrder::STATUS_PENDING,
                SalesOrder::STATUS_PROCESSING,
                SalesOrder::STATUS_COMPLETED,
                SalesOrder::STATUS_CANCELLED,
            ])],
        ]);

        $order->update($validated);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order updated.');
    }
}
