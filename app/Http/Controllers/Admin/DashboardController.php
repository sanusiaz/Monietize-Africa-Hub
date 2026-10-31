<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $days = 30;
        $from = now()->subDays($days - 1)->startOfDay();
        $labels = collect(range(0, $days - 1))->map(fn ($i) => $from->copy()->addDays($i));

        $series = fn (Collection $rows, callable $value) => $labels->map(
            fn (Carbon $d) => round($rows->filter(fn ($r) => $r->created_at->isSameDay($d))->sum($value), 2)
        )->values();

        $payments = Payment::where('status', 'success')->where('created_at', '>=', $from)->get();
        $orders = Order::where('created_at', '>=', $from)->get();
        $signups = User::where('role', 'customer')->where('created_at', '>=', $from)->get();
        $wallet = WalletTransaction::where('created_at', '>=', $from)->get();

        $revenue = (float) Payment::where('status', 'success')->sum('amount');
        $thisMonth = (float) Payment::where('status', 'success')->where('created_at', '>=', now()->startOfMonth())->sum('amount');
        $lastMonth = (float) Payment::where('status', 'success')
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->sum('amount');

        $byStatus = Order::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        $byPlatform = Order::join('services', 'orders.service_id', '=', 'services.id')
            ->join('categories', 'services.category_id', '=', 'categories.id')
            ->select('categories.platform', DB::raw('sum(orders.charge) as revenue'), DB::raw('count(*) as total'))
            ->groupBy('categories.platform')->orderByDesc('revenue')->get();

        $topServices = Order::join('services', 'orders.service_id', '=', 'services.id')
            ->select('services.name', DB::raw('count(*) as total'), DB::raw('sum(orders.charge) as revenue'))
            ->groupBy('services.id', 'services.name')->orderByDesc('total')->limit(6)->get();

        $channels = Payment::where('status', 'success')
            ->select(DB::raw("coalesce(channel, 'other') as channel"), DB::raw('count(*) as total'))
            ->groupBy('channel')->get();

        return Inertia::render('Admin/Dashboard', [
            'kpis' => [
                'revenue' => $revenue,
                'month_revenue' => $thisMonth,
                'revenue_change' => $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100, 1) : null,
                'users' => User::where('role', 'customer')->count(),
                'new_users' => $signups->count(),
                'orders' => Order::count(),
                'pending_orders' => Order::whereIn('status', ['pending', 'processing', 'in_progress'])->count(),
                'wallet_liability' => (float) User::where('role', 'customer')->sum('balance'),
                'order_value' => (float) Order::whereNotIn('status', ['canceled', 'refunded'])->sum('charge'),
            ],
            'charts' => [
                'labels' => $labels->map->format('M j')->values(),
                'revenue' => $series($payments, fn ($r) => $r->amount),
                'orders' => $series($orders, fn () => 1),
                'signups' => $series($signups, fn () => 1),
                'wallet_in' => $series($wallet->where('type', 'credit'), fn ($r) => $r->amount),
                'wallet_out' => $series($wallet->where('type', 'debit'), fn ($r) => $r->amount),
                'by_status' => $byStatus,
                'by_platform' => $byPlatform,
                'top_services' => $topServices,
                'channels' => $channels,
            ],
            'recentOrders' => Order::with(['user:id,name', 'service:id,name'])->latest()->limit(7)->get(),
            'recentPayments' => Payment::with('user:id,name')->latest()->limit(7)->get(),
        ]);
    }
}
