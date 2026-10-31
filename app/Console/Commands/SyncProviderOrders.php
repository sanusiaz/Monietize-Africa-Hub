<?php

namespace App\Console\Commands;

use App\Jobs\SubmitOrderToProvider;
use App\Models\Order;
use App\Services\OrderFulfilment;
use Illuminate\Console\Command;
use Throwable;

class SyncProviderOrders extends Command
{
    protected $signature = 'orders:sync';

    protected $description = 'Retry unsent supplier orders and pull the latest status of in-flight ones';

    public function handle(OrderFulfilment $fulfilment): int
    {
        // Paid orders that never reached the supplier (e.g. it was down, or the queue was stopped).
        Order::where('status', 'pending')->whereNull('provider_order_id')
            ->where('created_at', '<', now()->subMinutes(2))
            ->with('service.provider')->get()
            ->filter(fn (Order $o) => $fulfilment->usesSupplier($o))
            ->each(fn (Order $o) => SubmitOrderToProvider::dispatch($o));

        $synced = 0;

        Order::whereNotNull('provider_order_id')
            ->whereIn('status', ['pending', 'processing', 'in_progress'])
            ->with('service.provider', 'user')->limit(200)->get()
            ->each(function (Order $order) use ($fulfilment, &$synced) {
                try {
                    $fulfilment->sync($order);
                    $synced++;
                } catch (Throwable $e) {
                    $this->warn("Order #{$order->id}: {$e->getMessage()}");
                }
            });

        $this->info("Synced {$synced} order(s).");

        return self::SUCCESS;
    }
}
