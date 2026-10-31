<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaystackService
{
    public function __construct(private WalletService $wallet) {}

    public static function secretKey(): ?string
    {
        return Setting::get('paystack_secret_key') ?: config('services.paystack.secret');
    }

    public static function publicKey(): ?string
    {
        return Setting::get('paystack_public_key') ?: config('services.paystack.public');
    }

    private function client(): PendingRequest
    {
        $secret = self::secretKey();

        if (! $secret) {
            throw new RuntimeException('Paystack secret key is not configured. Add it in Admin → Settings.');
        }

        return Http::withToken($secret)->baseUrl('https://api.paystack.co')->acceptJson()->timeout(30);
    }

    /** Start a wallet top-up and return the Paystack checkout URL. */
    public function initialize(User $user, float $amount, string $callbackUrl): string
    {
        $payment = Payment::create([
            'user_id' => $user->id,
            'reference' => 'MH-'.Str::upper(Str::random(14)),
            'amount' => $amount,
        ]);

        $response = $this->client()->post('/transaction/initialize', [
            'email' => $user->email,
            'amount' => (int) round($amount * 100),
            'reference' => $payment->reference,
            'callback_url' => $callbackUrl,
            'currency' => Setting::get('currency', 'NGN'),
        ]);

        if (! $response->json('status')) {
            $payment->update(['status' => 'failed', 'payload' => $response->json()]);
            throw new RuntimeException($response->json('message') ?? 'Unable to start Paystack payment.');
        }

        return $response->json('data.authorization_url');
    }

    /** Verify with Paystack and credit the wallet exactly once. */
    public function verify(string $reference): Payment
    {
        $payment = Payment::where('reference', $reference)->firstOrFail();

        if ($payment->status === 'success') {
            return $payment;
        }

        $response = $this->client()->get('/transaction/verify/'.$reference);

        return $this->settle($payment, $response->json() ?? []);
    }

    public function settle(Payment $payment, array $body): Payment
    {
        $data = $body['data'] ?? [];
        $paid = ($body['status'] ?? false) && ($data['status'] ?? null) === 'success'
            && (int) ($data['amount'] ?? 0) === (int) round($payment->amount * 100);

        $credited = false;

        DB::transaction(function () use ($payment, $paid, $data, $body, &$credited) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($locked->status === 'success') {
                return;
            }

            $locked->update([
                'status' => $paid ? 'success' : (($data['status'] ?? '') === 'abandoned' ? 'abandoned' : 'failed'),
                'channel' => $data['channel'] ?? null,
                'paid_at' => $paid ? now() : null,
                'payload' => $body,
            ]);

            if ($paid) {
                $this->wallet->credit($locked->user, (float) $locked->amount, 'paystack', $locked->reference, 'Wallet top-up via Paystack');
                $credited = true;
            }
        });

        $payment->refresh();

        // receipt goes out once, only when this call is the one that credited the wallet
        if ($credited) {
            app(Notifier::class)->walletFunded($payment);
        }

        return $payment;
    }
}
