<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Mail\NewOrderAdminMail;
use App\Mail\OrderCompletedMail;
use App\Mail\OrderPlacedMail;
use App\Mail\WalletFundedMail;
use App\Mail\WelcomeMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function service(): Service
    {
        $cat = Category::create(['name' => 'IG', 'slug' => 'ig', 'platform' => 'instagram']);

        return Service::create(['category_id' => $cat->id, 'name' => 'Followers', 'rate' => 2000, 'min' => 10, 'max' => 5000]);
    }

    public function test_signup_sends_welcome_email_to_the_new_user(): void
    {
        Mail::fake();

        $this->post('/register', ['name' => 'Ada Okafor', 'email' => 'ada@example.com', 'password' => 'password123', 'password_confirmation' => 'password123']);

        Mail::assertSent(WelcomeMail::class, fn ($m) => $m->hasTo('ada@example.com'));
    }

    public function test_placing_an_order_emails_the_customer_and_the_configured_admin(): void
    {
        Mail::fake();
        Setting::put('admin_email', 'owner@example.com');
        $user = User::factory()->create(['balance' => 5000]);
        $service = $this->service();

        $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => 'https://x.test/a', 'quantity' => 1000]);

        Mail::assertSent(OrderPlacedMail::class, fn ($m) => $m->hasTo($user->email));
        Mail::assertSent(NewOrderAdminMail::class, fn ($m) => $m->hasTo('owner@example.com'));
    }

    public function test_admin_alert_falls_back_to_admin_accounts_when_no_address_is_set(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'boss@example.com']);
        $user = User::factory()->create(['balance' => 5000]);
        $service = $this->service();

        $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => 'https://x.test/a', 'quantity' => 1000]);

        Mail::assertSent(NewOrderAdminMail::class, fn ($m) => $m->hasTo($admin->email));
    }

    public function test_a_rejected_order_sends_no_emails(): void
    {
        Mail::fake();
        $user = User::factory()->create(['balance' => 1]);
        $service = $this->service();

        $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => 'https://x.test/a', 'quantity' => 1000]);

        Mail::assertNothingSent();
    }

    public function test_marking_an_order_completed_emails_the_customer_once(): void
    {
        $user = User::factory()->create();
        $service = $this->service();
        $order = Order::create(['user_id' => $user->id, 'service_id' => $service->id, 'link' => 'https://x.test/a', 'quantity' => 100, 'charge' => 200]);
        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();

        $this->actingAs($admin)->put("/admin/orders/{$order->id}", ['status' => 'processing']);
        Mail::assertNothingSent();

        $this->actingAs($admin)->put("/admin/orders/{$order->id}", ['status' => 'completed']);
        $this->actingAs($admin)->put("/admin/orders/{$order->id}", ['status' => 'completed', 'notes' => 'saved again']);

        Mail::assertSent(OrderCompletedMail::class, 1);
        Mail::assertSent(OrderCompletedMail::class, fn ($m) => $m->hasTo($user->email));
    }

    public function test_wallet_top_up_sends_one_receipt(): void
    {
        $this->withoutDefer(); // no HTTP request here, so run deferred mail immediately
        Mail::fake();
        $user = User::factory()->create(['balance' => 0]);
        $payment = Payment::create(['user_id' => $user->id, 'reference' => 'MH-R1', 'amount' => 2500]);
        $body = ['status' => true, 'data' => ['status' => 'success', 'amount' => 250000, 'channel' => 'card']];

        $service = app(PaystackService::class);
        $service->settle($payment, $body);
        $service->settle($payment->fresh(), $body);

        Mail::assertSent(WalletFundedMail::class, 1);
    }

    public function test_contact_form_saves_message_and_emails_admin_with_reply_to(): void
    {
        Mail::fake();
        Setting::put('admin_email', 'owner@example.com');

        $this->post('/contact', ['name' => 'Chidi', 'email' => 'chidi@example.com', 'subject' => 'Refill question', 'message' => 'Do refills cover drops after a week?'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contact_messages', ['email' => 'chidi@example.com', 'subject' => 'Refill question']);
        Mail::assertSent(ContactMessageMail::class, fn ($m) => $m->hasTo('owner@example.com') && $m->hasReplyTo('chidi@example.com'));
    }

    public function test_contact_form_validates_and_ignores_bots(): void
    {
        Mail::fake();

        $this->post('/contact', ['name' => '', 'email' => 'nope', 'subject' => '', 'message' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->post('/contact', ['name' => 'Bot', 'email' => 'b@example.com', 'subject' => 'Hi', 'message' => 'buy cheap things now', 'website' => 'http://spam'])
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_every_email_preview_renders_for_admins_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['welcome', 'order-placed', 'new-order', 'order-completed', 'wallet-funded', 'contact', 'test'] as $type) {
            $this->actingAs($admin)->get("/admin/emails/{$type}")->assertOk()->assertSee('</html>', false);
        }

        $this->actingAs(User::factory()->create())->get('/admin/emails/welcome')->assertForbidden();
    }

    public function test_order_email_shows_the_order_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/emails/order-placed')
            ->assertSee('Order #1042', false)->assertSee('Total charged')->assertSee('₦8,000.00');
    }
}
