<?php

namespace App\Http\Controllers;

use App\Jobs\SubmitOrderToProvider;
use App\Models\Category;
use App\Models\Order;
use App\Models\Service;
use App\Services\Notifier;
use App\Services\OrderFulfilment;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use RuntimeException;

class StoreController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $orders = $user->orders();

        return Inertia::render('Customer/Dashboard', [
            'stats' => [
                'balance' => $user->balance,
                'orders' => $orders->count(),
                'active' => $user->orders()->whereIn('status', ['pending', 'processing', 'in_progress'])->count(),
                'completed' => $user->orders()->where('status', 'completed')->count(),
                'spent' => (float) $user->orders()->whereNotIn('status', ['canceled', 'refunded'])->sum('charge'),
            ],
            'recent' => $user->orders()->with('service:id,name')->latest()->limit(5)->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Customer/NewOrder', [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')
                ->with(['services' => fn ($q) => $q->where('is_active', true)->orderBy('rate')
                    ->select('id', 'category_id', 'name', 'description', 'rate', 'min', 'max', 'refill', 'drip_feed')])
                ->get(['id', 'name', 'platform'])
                ->filter(fn ($c) => $c->services->isNotEmpty())->values(),
        ]);
    }

    public function store(Request $request, WalletService $wallet, OrderFulfilment $fulfilment)
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'link' => 'required|string|url:http,https|max:500',
            'quantity' => 'required|integer|min:1|max:100000000',
        ]);

        $service = Service::with('category')->findOrFail($data['service_id']);

        if (! $service->is_active || ! $service->category->is_active) {
            return back()->withErrors(['service_id' => 'This service is currently unavailable.']);
        }

        if ($data['quantity'] < $service->min || $data['quantity'] > $service->max) {
            return back()->withErrors(['quantity' => "Quantity must be between {$service->min} and {$service->max}."]);
        }

        $charge = round($service->rate * $data['quantity'] / 1000, 2);

        try {
            $order = DB::transaction(function () use ($request, $service, $data, $charge, $wallet) {
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'service_id' => $service->id,
                    'link' => $data['link'],
                    'quantity' => $data['quantity'],
                    'charge' => $charge,
                ]);
                $wallet->debit($request->user(), $charge, 'order', "order-{$order->id}", "Order #{$order->id} · {$service->name}");

                return $order;
            });
        } catch (RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage().' Please fund your wallet first.']);
        }

        if ($fulfilment->usesSupplier($order)) {
            SubmitOrderToProvider::dispatch($order)->afterCommit();
        }

        app(Notifier::class)->orderPlaced($order->load('service.provider', 'user'));

        return redirect('/my-orders')->with('success', "Order #{$order->id} placed successfully.");
    }

    public function orders(Request $request)
    {
        return Inertia::render('Customer/Orders', [
            'orders' => $request->user()->orders()->with('service:id,name')
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest()->paginate(10)->withQueryString(),
            'statuses' => Order::STATUSES,
            'filters' => $request->only('status'),
        ]);
    }
}
