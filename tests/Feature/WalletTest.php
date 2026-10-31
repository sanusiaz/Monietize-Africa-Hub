<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Services\PaystackService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_credits_and_blocks_overdraw(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $wallet = app(WalletService::class);

        $wallet->credit($user, 500, 'test');
        $this->assertEquals(500, $user->fresh()->balance);

        $this->expectException(RuntimeException::class);
        $wallet->debit($user, 501, 'test');
    }

    public function test_paystack_settlement_credits_exactly_once(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $payment = Payment::create(['user_id' => $user->id, 'reference' => 'MH-TEST', 'amount' => 2500]);
        $body = ['status' => true, 'data' => ['status' => 'success', 'amount' => 250000, 'channel' => 'card']];

        $service = app(PaystackService::class);
        $service->settle($payment, $body);
        $service->settle($payment->fresh(), $body);

        $this->assertEquals(2500, $user->fresh()->balance);
        $this->assertSame('success', $payment->fresh()->status);
    }

    public function test_amount_mismatch_does_not_credit(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $payment = Payment::create(['user_id' => $user->id, 'reference' => 'MH-BAD', 'amount' => 2500]);

        app(PaystackService::class)->settle($payment, ['status' => true, 'data' => ['status' => 'success', 'amount' => 100]]);

        $this->assertEquals(0, $user->fresh()->balance);
        $this->assertSame('failed', $payment->fresh()->status);
    }

    public function test_webhook_rejects_bad_signature_and_non_admin_blocked(): void
    {
        $this->postJson('/webhooks/paystack', ['event' => 'charge.success'], ['x-paystack-signature' => 'nope'])->assertStatus(401);

        $this->actingAs(User::factory()->create(['role' => 'customer']))->get('/admin')->assertForbidden();
    }
}
