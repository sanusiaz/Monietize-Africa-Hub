<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\WalletTransaction;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Payments/Index', [
            'payments' => Payment::with('user:id,name,email')
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest()->paginate(12)->withQueryString(),
            'transactions' => WalletTransaction::with('user:id,name')->latest()->limit(15)->get(),
            'filters' => $request->only('status'),
            'totals' => [
                'success' => (float) Payment::where('status', 'success')->sum('amount'),
                'pending' => (float) Payment::where('status', 'pending')->sum('amount'),
                'failed' => Payment::whereIn('status', ['failed', 'abandoned'])->count(),
            ],
        ]);
    }

    public function verify(Payment $payment, PaystackService $paystack)
    {
        try {
            $result = $paystack->verify($payment->reference);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with($result->status === 'success' ? 'success' : 'error', "Payment is {$result->status}.");
    }
}
