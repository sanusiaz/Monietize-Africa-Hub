<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderFulfilment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SubmitOrderToProvider implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public Order $order) {}

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(OrderFulfilment $fulfilment): void
    {
        try {
            $fulfilment->submit($this->order);
        } catch (Throwable $e) {
            $fulfilment->recordFailure($this->order, $e);
            throw $e;
        }
    }
}
