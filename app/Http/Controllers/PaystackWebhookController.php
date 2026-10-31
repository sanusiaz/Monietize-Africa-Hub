<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaystackService;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystack)
    {
        $secret = PaystackService::secretKey();
        $signature = (string) $request->header('x-paystack-signature');

        if (! $secret || ! hash_equals(hash_hmac('sha512', $request->getContent(), $secret), $signature)) {
            abort(401);
        }

        if ($request->input('event') === 'charge.success') {
            $payment = Payment::where('reference', $request->input('data.reference'))->first();

            if ($payment) {
                $paystack->settle($payment, ['status' => true, 'data' => $request->input('data')]);
            }
        }

        return response()->json(['ok' => true]);
    }
}
