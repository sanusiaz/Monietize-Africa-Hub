<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Setting;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Customer/Wallet', [
            'balance' => $user->balance,
            'minTopup' => (float) Setting::get('min_topup', 500),
            'transactions' => $user->walletTransactions()->latest()->limit(15)->get(),
        ]);
    }

    public function topup(Request $request, PaystackService $paystack)
    {
        $min = (float) Setting::get('min_topup', 500);
        $data = $request->validate(['amount' => "required|numeric|decimal:0,2|min:$min|max:10000000"]);

        try {
            $url = $paystack->initialize($request->user(), (float) $data['amount'], route('wallet.callback'));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return Inertia::location($url);
    }

    public function callback(Request $request, PaystackService $paystack)
    {
        $reference = $request->query('reference', $request->query('trxref'));
        $payment = Payment::where('reference', $reference)->where('user_id', $request->user()->id)->first();

        if (! $payment) {
            return redirect('/wallet')->with('error', 'Payment not found.');
        }

        try {
            $payment = $paystack->verify($payment->reference);
        } catch (Throwable $e) {
            return redirect('/wallet')->with('error', $e->getMessage());
        }

        return redirect('/wallet')->with(
            $payment->status === 'success' ? 'success' : 'error',
            $payment->status === 'success' ? 'Wallet funded successfully.' : 'Payment was not completed.'
        );
    }
}
