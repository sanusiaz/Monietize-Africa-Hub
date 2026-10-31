<?php

namespace Tests\Feature;

use App\Jobs\SubmitOrderToProvider;
use App\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderFulfilment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private Provider $provider;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = Provider::create(['name' => 'Panel', 'api_url' => 'https://panel.test/api/v2', 'api_key' => 'secret-key']);
        $this->category = Category::create(['name' => 'IG', 'slug' => 'ig', 'platform' => 'instagram']);
    }

    private function order(float $balance = 0, int $qty = 1000): Order
    {
        $user = User::factory()->create(['balance' => $balance]);
        $service = Service::create([
            'category_id' => $this->category->id, 'provider_id' => $this->provider->id, 'provider_service_id' => '77',
            'name' => 'Followers', 'rate' => 2000, 'min' => 10, 'max' => 5000,
        ]);

        return Order::create(['user_id' => $user->id, 'service_id' => $service->id, 'link' => 'https://x.test/a', 'quantity' => $qty, 'charge' => 2000]);
    }

    public function test_placing_an_order_queues_supplier_submission(): void
    {
        Queue::fake();
        $user = User::factory()->create(['balance' => 5000]);
        $service = Service::create(['category_id' => $this->category->id, 'provider_id' => $this->provider->id, 'provider_service_id' => '77', 'name' => 'F', 'rate' => 2000, 'min' => 10, 'max' => 5000]);

        $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => 'https://x.test', 'quantity' => 1000]);

        Queue::assertPushed(SubmitOrderToProvider::class);
    }

    public function test_submit_sends_order_and_stores_supplier_id(): void
    {
        Http::fake(['panel.test/*' => Http::response(['order' => 9981])]);
        $order = $this->order();

        app(OrderFulfilment::class)->submit($order);

        $this->assertSame('9981', $order->fresh()->provider_order_id);
        $this->assertSame('processing', $order->fresh()->status);
        Http::assertSent(fn ($r) => $r['key'] === 'secret-key' && $r['action'] === 'add' && $r['service'] === '77' && (int) $r['quantity'] === 1000);
    }

    public function test_supplier_error_is_recorded_and_order_stays_pending(): void
    {
        Http::fake(['panel.test/*' => Http::response(['error' => 'Not enough funds on balance'])]);
        $order = $this->order();

        try {
            (new SubmitOrderToProvider($order))->handle(app(OrderFulfilment::class));
            $this->fail('Expected exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Not enough funds', $e->getMessage());
        }

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertStringContainsString('Not enough funds', $order->fresh()->notes);
        $this->assertNull($order->fresh()->provider_order_id);
    }

    public function test_sync_completes_order(): void
    {
        Http::fake(['panel.test/*' => Http::response(['status' => 'Completed', 'start_count' => '120', 'remains' => '0'])]);
        $order = $this->order();
        $order->update(['provider_order_id' => '9981', 'status' => 'processing']);

        app(OrderFulfilment::class)->sync($order);

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(120, $order->fresh()->start_count);
    }

    public function test_partial_refunds_undelivered_share_once(): void
    {
        Http::fake(['panel.test/*' => Http::response(['status' => 'Partial', 'remains' => '250'])]);
        $order = $this->order(balance: 0, qty: 1000);
        $order->update(['provider_order_id' => '9981', 'status' => 'processing']);

        $fulfilment = app(OrderFulfilment::class);
        $fulfilment->sync($order);
        $fulfilment->sync($order->fresh());

        $this->assertEquals(500, $order->user->fresh()->balance); // 25% of 2000, applied once
    }

    public function test_supplier_cancel_fully_refunds_once_and_admin_cancel_does_not_double_refund(): void
    {
        Http::fake(['panel.test/*' => Http::response(['status' => 'Canceled'])]);
        $order = $this->order();
        $order->update(['provider_order_id' => '9981', 'status' => 'processing']);

        app(OrderFulfilment::class)->sync($order);
        $this->assertEquals(2000, $order->user->fresh()->balance);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put("/admin/orders/{$order->id}", ['status' => 'refunded']);
        $this->assertEquals(2000, $order->user->fresh()->balance);
    }

    public function test_import_applies_markup_and_is_idempotent(): void
    {
        Http::fake(['panel.test/*' => Http::response([
            ['service' => 5, 'name' => 'IG Likes', 'category' => 'Instagram', 'rate' => '100.00', 'min' => '10', 'max' => '1000', 'refill' => true],
            ['service' => 6, 'name' => 'IG Views', 'category' => 'Instagram', 'rate' => '50.00', 'min' => '10', 'max' => '1000'],
        ])]);
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = ['category_id' => $this->category->id, 'markup' => 30, 'services' => ['5']];

        $this->actingAs($admin)->post("/admin/providers/{$this->provider->id}/import", $payload);
        $this->actingAs($admin)->post("/admin/providers/{$this->provider->id}/import", $payload);

        $this->assertDatabaseCount('services', 1);
        $this->assertDatabaseHas('services', ['provider_service_id' => '5', 'name' => 'IG Likes', 'rate' => 130.00, 'refill' => true]);
    }

    public function test_sync_command_runs_clean(): void
    {
        Http::fake(['panel.test/*' => Http::response(['status' => 'In progress', 'remains' => '500'])]);
        $order = $this->order();
        $order->update(['provider_order_id' => '9981', 'status' => 'processing']);

        $this->artisan('orders:sync')->assertSuccessful();

        $this->assertSame('in_progress', $order->fresh()->status);
    }
}
