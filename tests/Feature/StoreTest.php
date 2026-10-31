<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    private function service(array $attrs = []): Service
    {
        $cat = Category::create(['name' => 'IG', 'slug' => 'ig', 'platform' => 'instagram']);

        return Service::create($attrs + ['category_id' => $cat->id, 'name' => 'Followers', 'rate' => 2000, 'min' => 100, 'max' => 5000]);
    }

    public function test_order_debits_wallet_and_records_ledger(): void
    {
        $user = User::factory()->create(['balance' => 10000]);
        $service = $this->service();

        $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => 'https://instagram.com/x', 'quantity' => 1000])
            ->assertRedirect('/my-orders');

        $this->assertEquals(8000, $user->fresh()->balance);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'charge' => 2000, 'status' => 'pending']);
        $this->assertDatabaseHas('wallet_transactions', ['user_id' => $user->id, 'type' => 'debit', 'amount' => 2000]);
    }

    public function test_insufficient_balance_and_bad_quantity_create_no_order(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $service = $this->service();

        $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => 'https://x.test/a', 'quantity' => 1000])->assertSessionHasErrors('quantity');
        $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => 'https://x.test/a', 'quantity' => 5])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('orders', 0);
        $this->assertEquals(100, $user->fresh()->balance);
    }

    public function test_inactive_service_rejected_and_admin_refund_restores_balance(): void
    {
        $user = User::factory()->create(['balance' => 5000]);
        $service = $this->service();
        $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => 'https://x.test/a', 'quantity' => 1000]);
        $order = $user->orders()->first();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put("/admin/orders/{$order->id}", ['status' => 'canceled']);
        $this->assertEquals(5000, $user->fresh()->balance);

        $service->update(['is_active' => false]);
        $this->actingAs($user)->post('/new-order', ['service_id' => $service->id, 'link' => 'https://x.test/a', 'quantity' => 1000])->assertSessionHasErrors('service_id');
    }
}
