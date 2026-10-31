<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Fake customers, orders and payments so a development install has something to look at. Never runs in production. */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // the same starter catalogue a live site can load on its own
        $this->call(StarterCatalogueSeeder::class);
        $services = Service::with('category')->get();

        if (User::where('role', 'customer')->exists()) {
            return;
        }

        $statuses = ['completed', 'completed', 'completed', 'in_progress', 'pending', 'processing', 'partial', 'canceled'];
        $channels = ['card', 'card', 'bank_transfer', 'bank'];

        for ($u = 1; $u <= 24; $u++) {
            $joined = now()->subDays(rand(0, 29))->subHours(rand(0, 23));
            $user = User::create([
                'name' => fake()->name(), 'email' => "customer{$u}@example.com", 'password' => Str::random(24),
                'role' => 'customer', 'created_at' => $joined, 'updated_at' => $joined,
            ]);

            for ($p = 0; $p < rand(1, 3); $p++) {
                $at = $joined->copy()->addDays(rand(0, 5))->min(now());
                $amount = collect([2000, 5000, 10000, 20000, 50000])->random();
                Payment::create([
                    'user_id' => $user->id, 'reference' => 'MH-'.Str::upper(Str::random(14)), 'amount' => $amount,
                    'status' => 'success', 'channel' => collect($channels)->random(), 'paid_at' => $at, 'created_at' => $at, 'updated_at' => $at,
                ]);
                $user->increment('balance', $amount);
                WalletTransaction::create([
                    'user_id' => $user->id, 'type' => 'credit', 'amount' => $amount, 'balance_after' => $user->fresh()->balance,
                    'source' => 'paystack', 'description' => 'Wallet top-up via Paystack', 'created_at' => $at, 'updated_at' => $at,
                ]);
            }

            for ($o = 0; $o < rand(1, 5); $o++) {
                $service = $services->random();
                $qty = rand(1, 40) * 50;
                $charge = round($service->rate * $qty / 1000, 2);
                $user->refresh();
                if ($user->balance < $charge) {
                    continue;
                }
                $at = $joined->copy()->addDays(rand(0, 8))->min(now());
                $user->decrement('balance', $charge);
                $order = Order::create([
                    'user_id' => $user->id, 'service_id' => $service->id, 'link' => 'https://'.$service->category->platform.'.com/'.fake()->userName(),
                    'quantity' => $qty, 'charge' => $charge, 'status' => collect($statuses)->random(), 'created_at' => $at, 'updated_at' => $at,
                ]);
                WalletTransaction::create([
                    'user_id' => $user->id, 'type' => 'debit', 'amount' => $charge, 'balance_after' => $user->fresh()->balance,
                    'source' => 'order', 'reference' => "order-{$order->id}", 'description' => "Order #{$order->id}", 'created_at' => $at, 'updated_at' => $at,
                ]);
            }
        }

        Payment::create(['user_id' => User::where('role', 'customer')->first()->id, 'reference' => 'MH-'.Str::upper(Str::random(14)), 'amount' => 5000, 'status' => 'pending']);
    }
}
