<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderFulfilment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['user:id,name,email', 'service:id,name,provider_id,provider_service_id'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('link', 'like', "%$s%")->orWhere('id', $s)))
            ->latest()->paginate(12)->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'statuses' => Order::STATUSES,
            'filters' => $request->only('status', 'search'),
        ]);
    }

    public function submit(Order $order, OrderFulfilment $fulfilment)
    {
        if ($order->provider_order_id || $order->status !== 'pending' || ! $fulfilment->usesSupplier($order)) {
            return back()->with('error', 'This order cannot be sent to a supplier.');
        }

        try {
            $fulfilment->submit($order);
        } catch (Throwable $e) {
            $fulfilment->recordFailure($order, $e);

            return back()->with('error', 'Supplier error: '.$e->getMessage());
        }

        return back()->with('success', 'Order sent to supplier.');
    }

    public function update(Request $request, Order $order, OrderFulfilment $fulfilment)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
            'start_count' => 'nullable|integer|min:0',
            'remains' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $refunding = in_array($data['status'], ['canceled', 'refunded'], true)
            && ! in_array($order->status, ['canceled', 'refunded'], true);

        $order->update($data);

        $refunded = $refunding && $fulfilment->refund($order);

        return back()->with('success', $refunded ? 'Order updated and customer refunded.' : 'Order updated.');
    }
}
