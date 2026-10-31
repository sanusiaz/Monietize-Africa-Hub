<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function service(): Service
    {
        $cat = Category::create(['name' => 'IG', 'slug' => 'ig', 'platform' => 'instagram']);

        return Service::create(['category_id' => $cat->id, 'name' => 'Followers', 'rate' => 2000, 'min' => 10, 'max' => 5000]);
    }

    // ---------- browser protections ----------

    public function test_security_headers_and_a_nonce_based_csp_are_sent(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString("script-src 'unsafe-inline'", $csp);
        $this->assertDoesNotMatchRegularExpression("/script-src[^;]*'unsafe-inline'/", $csp);

        // the nonce in the header is the one stamped on our own inline script
        preg_match("/'nonce-([^']+)'/", $csp, $m);
        $this->assertNotEmpty($m[1]);
        $this->assertStringContainsString('nonce="'.$m[1].'"', $response->getContent());
    }

    public function test_each_response_gets_a_fresh_nonce_and_https_adds_hsts(): void
    {
        preg_match("/nonce-([^']+)'/", $this->get('/')->headers->get('Content-Security-Policy'), $a);
        preg_match("/nonce-([^']+)'/", $this->get('/contact')->headers->get('Content-Security-Policy'), $b);
        $this->assertNotSame($a[1], $b[1]);

        $secure = $this->get('https://localhost/');
        $secure->assertHeader('Strict-Transport-Security');
        $this->assertStringContainsString('upgrade-insecure-requests', $secure->headers->get('Content-Security-Policy'));
    }

    // ---------- input cleaning / XSS ----------

    public function test_single_line_fields_lose_html_and_emails_are_lowercased_and_control_characters_removed(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => '  <script>alert(1)</script>Ada   Okafor ',
            'email' => 'ADA@Example.COM',
            "subject" => "Hello\r
Bcc: evil@example.com",
            'message' => "Line one\x00\x07 and line two
still here",
        ])->assertSessionHasNoErrors();

        $m = ContactMessage::first();
        $this->assertSame('alert(1)Ada Okafor', $m->name);
        $this->assertSame('ada@example.com', $m->email);
        $this->assertSame('Hello Bcc: evil@example.com', $m->subject); // header injection collapsed to plain text
        $this->assertStringNotContainsString("\x00", $m->message);
        $this->assertStringContainsString("
", $m->message); // normal line breaks survive
    }

    public function test_contact_message_html_is_escaped_in_the_admin_email(): void
    {
        $message = ContactMessage::create(['name' => 'Eve', 'email' => 'eve@example.com', 'subject' => 'Hi', 'message' => '<img src=x onerror=alert(1)><script>steal()</script>']);

        $html = (new ContactMessageMail($message))->render();

        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<script>steal', $html);
        $this->assertStringContainsString('&lt;img src=x', $html);
    }

    public function test_passwords_are_not_altered_by_input_cleaning(): void
    {
        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => ' Pa<ss>wd 12', 'password_confirmation' => ' Pa<ss>wd 12']);

        $this->assertTrue(\Hash::check(' Pa<ss>wd 12', User::where('email', 'ada@example.com')->first()->password));
    }

    // ---------- SQL injection ----------

    public function test_sql_injection_strings_are_treated_as_plain_text_everywhere(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Normal Person', 'email' => 'normal@example.com']);
        $this->service();
        $evil = ["' OR '1'='1", "1; DROP TABLE users; --", "%' UNION SELECT password FROM users --", '\\', "' OR 1=1 --"];

        foreach ($evil as $payload) {
            $this->post('/login', ['email' => $payload, 'password' => $payload])->assertSessionHasErrors('email');
            $this->get('/'.rawurlencode($payload))->assertNotFound();
        }

        foreach ($evil as $payload) {
            $this->actingAs($admin)->get('/admin/users?search='.urlencode($payload))
                ->assertOk()->assertInertia(fn ($p) => $p->has('users.data', 0));
            $this->actingAs($admin)->get('/admin/orders?search='.urlencode($payload).'&status='.urlencode($payload))->assertOk();
            $this->actingAs($admin)->get('/admin/services?search='.urlencode($payload).'&category='.urlencode($payload))->assertOk();
            $this->actingAs($admin)->get('/admin/messages?search='.urlencode($payload))->assertOk();
            $this->actingAs($admin)->get('/admin/payments?status='.urlencode($payload))->assertOk();
            $this->actingAs($admin)->get('/my-orders?status='.urlencode($payload))->assertOk();
        }

        $this->assertSame(2, User::count()); // admin + normal: nothing was dropped or added
        $this->assertDatabaseHas('users', ['email' => 'normal@example.com']);
    }

    // ---------- privilege escalation / mass assignment ----------

    public function test_registration_cannot_grant_admin_role_or_balance(): void
    {
        $this->post('/register', [
            'name' => 'Mallory', 'email' => 'mallory@example.com', 'password' => 'Passw0rdOK1', 'password_confirmation' => 'Passw0rdOK1',
            'role' => 'admin', 'balance' => 99999, 'is_active' => true,
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'mallory@example.com')->first();
        $this->assertSame('customer', $user->role);
        $this->assertEquals(0, $user->balance);
    }

    public function test_models_only_mass_assign_whitelisted_columns(): void
    {
        $order = (new Order)->fill(['id' => 77, 'bogus' => 'x', 'quantity' => 5]);

        $this->assertNull($order->id);
        $this->assertArrayNotHasKey('bogus', $order->getAttributes());
        $this->assertSame(5, $order->quantity);
    }

    public function test_a_customer_cannot_change_their_own_role_or_reach_admin_actions(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->put("/admin/users/{$customer->id}", ['name' => 'X', 'email' => $customer->email, 'role' => 'admin', 'is_active' => true])->assertForbidden();
        $this->actingAs($customer)->post("/admin/users/{$customer->id}/wallet", ['type' => 'credit', 'amount' => 5000])->assertForbidden();
        $this->assertSame('customer', $customer->fresh()->role);
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    public function test_customers_cannot_see_or_settle_other_peoples_data(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $service = $this->service();
        Order::create(['user_id' => $a->id, 'service_id' => $service->id, 'link' => 'https://a.test/secret', 'quantity' => 10, 'charge' => 20]);
        $payment = Payment::create(['user_id' => $a->id, 'reference' => 'MH-A1', 'amount' => 1000]);

        $this->actingAs($b)->get('/my-orders')->assertInertia(fn ($p) => $p->has('orders.data', 0));
        $this->actingAs($b)->get('/wallet/callback?reference=MH-A1')->assertRedirect('/wallet');
        $this->assertSame('pending', $payment->fresh()->status);
    }

    // ---------- brute force / passwords ----------

    public function test_repeated_wrong_passwords_lock_the_account_even_for_the_right_password(): void
    {
        User::factory()->create(['email' => 'victim@example.com', 'password' => 'RightPass123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'victim@example.com', 'password' => 'wrong'.$i])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => 'victim@example.com', 'password' => 'RightPass123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringContainsString('Too many', session('errors')->first('email'));
    }

    public function test_successful_login_clears_the_failure_count(): void
    {
        User::factory()->create(['email' => 'ok@example.com', 'password' => 'RightPass123']);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => 'ok@example.com', 'password' => 'bad']);
        }
        $this->post('/login', ['email' => 'ok@example.com', 'password' => 'RightPass123'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_weak_passwords_are_rejected_at_registration(): void
    {
        foreach (['short1', 'onlyletterspassword', '1234567890123'] as $weak) {
            $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => $weak, 'password_confirmation' => $weak])->assertSessionHasErrors('password');
        }
        $this->assertDatabaseCount('users', 0);
    }

    // ---------- links, URLs and amounts ----------

    public function test_order_links_must_be_real_http_or_https_urls(): void
    {
        $user = User::factory()->create(['balance' => 100000]);
        $service = $this->service();

        foreach (['javascript:alert(1)', 'data:text/html,<script>', 'ftp://x.test/a', 'not a url', 'file:///etc/passwd'] as $bad) {
            $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => $bad, 'quantity' => 100])->assertSessionHasErrors('link');
        }
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => 'https://instagram.com/ada', 'quantity' => 100])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_amounts_and_quantities_are_bounded_and_formatted(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        foreach (['abc', '-5', '10.999', '1e9', '999999999999'] as $bad) {
            $this->actingAs($admin)->post("/admin/users/{$customer->id}/wallet", ['type' => 'credit', 'amount' => $bad])->assertSessionHasErrors('amount');
        }
        $this->actingAs($customer)->post('/wallet/topup', ['amount' => '1000.555'])->assertSessionHasErrors('amount');
        $this->actingAs($customer)->post('/wallet/topup', ['amount' => '99999999999'])->assertSessionHasErrors('amount');
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    public function test_settings_urls_cannot_use_script_schemes_and_fields_have_safe_formats(): void
    {
        $admin = $this->admin();
        $base = ['site_name' => 'Monetize Hub', 'currency' => 'NGN', 'min_topup' => 500];

        foreach (['social_instagram' => 'javascript:alert(1)', 'social_x' => 'data:text/html,x', 'support_phone' => '<b>call</b>', 'mail_host' => 'smtp.host.com; rm -rf /', 'support_email' => 'not-an-email'] as $field => $bad) {
            $this->actingAs($admin)->put('/admin/settings', $base + [$field => $bad])->assertSessionHasErrors($field);
        }

        $this->actingAs($admin)->put('/admin/settings', $base + ['social_instagram' => 'https://instagram.com/monetizehub', 'support_phone' => '+234 801 234 5678'])->assertSessionHasNoErrors();
    }

    public function test_site_name_cannot_carry_html(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/settings', ['site_name' => '<img src=x onerror=alert(1)>Shop', 'currency' => 'NGN', 'min_topup' => 500]);

        $this->assertSame('Shop', \App\Models\Setting::get('site_name'));
    }

    // ---------- server-side request forgery ----------

    public function test_supplier_urls_must_be_public_https_addresses(): void
    {
        $admin = $this->admin();
        $payload = ['name' => 'Panel', 'api_key' => 'k', 'is_active' => true];

        foreach (['http://panel.example.com/api', 'https://localhost/api', 'https://127.0.0.1/api', 'https://169.254.169.254/latest/meta-data', 'https://10.0.0.5/api', 'https://user:pass@panel.example.com/api', 'file:///etc/passwd', 'gopher://x'] as $bad) {
            $this->actingAs($admin)->post('/admin/providers', $payload + ['api_url' => $bad])->assertSessionHasErrors('api_url');
        }
        $this->assertDatabaseCount('providers', 0);

        $this->actingAs($admin)->post('/admin/providers', $payload + ['api_url' => 'https://panel.example.com/api/v2'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('providers', 1);
    }

    // ---------- admin seeder ----------

    public function test_admin_seeder_reads_email_and_password_from_config_and_is_repeatable(): void
    {
        config(['admin.name' => 'Site Owner', 'admin.email' => 'Owner@Example.com', 'admin.password' => 'Str0ng-enough-pass']);

        app(AdminSeeder::class)->run();
        app(AdminSeeder::class)->run();

        $this->assertSame(1, User::where('role', 'admin')->count());
        $admin = User::where('email', 'owner@example.com')->first();
        $this->assertSame('Site Owner', $admin->name);
        $this->assertTrue(\Hash::check('Str0ng-enough-pass', $admin->password));

        config(['admin.password' => 'A-brand-new-pass-99']);
        app(AdminSeeder::class)->run();
        $this->assertTrue(\Hash::check('A-brand-new-pass-99', $admin->fresh()->password));
        $this->assertSame(1, User::where('role', 'admin')->count());
    }

    public function test_admin_seeder_refuses_missing_or_weak_values_in_production(): void
    {
        $this->app['env'] = 'production';

        config(['admin.email' => null, 'admin.password' => 'Str0ng-enough-pass']);
        try { app(AdminSeeder::class)->run(); $this->fail('missing email accepted'); } catch (InvalidArgumentException $e) { $this->assertStringContainsString('ADMIN_EMAIL', $e->getMessage()); }

        config(['admin.email' => 'owner@example.com', 'admin.password' => 'short1']);
        try { app(AdminSeeder::class)->run(); $this->fail('short password accepted'); } catch (InvalidArgumentException $e) { $this->assertStringContainsString('too short', $e->getMessage()); }

        config(['admin.password' => 'ChangeMe!2026']);
        try { app(AdminSeeder::class)->run(); $this->fail('placeholder accepted'); } catch (InvalidArgumentException $e) { $this->assertStringContainsString('placeholder', $e->getMessage()); }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_production_seeding_creates_the_admin_but_no_demo_data(): void
    {
        $this->app['env'] = 'production';
        config(['admin.email' => 'owner@example.com', 'admin.password' => 'Str0ng-enough-pass']);

        app(DatabaseSeeder::class)->run();

        $this->assertSame(1, User::count());
        $this->assertSame(14, Service::count()); // the starter catalogue is part of a normal seed
        $this->assertSame(0, Order::count());
        $this->assertSame(0, Payment::count());
        $this->assertDatabaseHas('settings', ['key' => 'site_name']);
        $this->assertDatabaseHas('pages', ['slug' => 'privacy-policy']);
    }
}
