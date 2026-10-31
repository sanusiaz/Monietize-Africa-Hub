<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WalletTransaction;
use Throwable;

class OrderFulfilment
{
    public function __construct(private WalletService $wallet) {}

    public function usesSupplier(Order $order): bool
    {
        $service = $order->service;

        return $service->provider_id && $service->provider_service_id && $service->provider?->is_active;
    }

    /** Send an order to its supplier. Throws on failure so queued jobs can retry. */
    public function submit(Order $order): void
    {
        $order->refresh()->loadMissing('service.provider');

        if ($order->provider_order_id || ! $this->usesSupplier($order) || $order->status !== 'pending') {
            return;
        }

        $providerOrderId = (new SmmClient($order->service->provider))
            ->add($order->service->provider_service_id, $order->link, $order->quantity);

        $order->update(['provider_order_id' => $providerOrderId, 'status' => 'processing', 'notes' => null]);
    }

    /** Pull the latest status from the supplier and apply it, refunding when needed. */
    public function sync(Order $order): void
    {
        $order->loadMissing('service.provider');

        if (! $order->provider_order_id || ! $order->service->provider) {
            return;
        }

        $r = (new SmmClient($order->service->provider))->status($order->provider_order_id);

        $status = match (strtolower((string) ($r['status'] ?? ''))) {
            'pending' => 'pending',
            'processing' => 'processing',
            'in progress', 'in_progress' => 'in_progress',
            'completed' => 'completed',
            'partial' => 'partial',
            'canceled', 'cancelled', 'fail', 'failed' => 'canceled',
            'refunded' => 'refunded',
            default => $order->status,
        };

        $order->update([
            'status' => $status,
            'start_count' => isset($r['start_count']) ? (int) $r['start_count'] : $order->start_count,
            'remains' => isset($r['remains']) ? (int) $r['remains'] : $order->remains,
        ]);

        if (in_array($status, ['canceled', 'refunded'], true)) {
            $this->refund($order);
        } elseif ($status === 'partial') {
            $this->refund($order, round($order->charge * min(1, ($order->remains ?? 0) / max(1, $order->quantity)), 2));
        }
    }

    /** Refund an order to the customer's wallet. Safe to call repeatedly: only the first refund is applied. */
    public function refund(Order $order, ?float $amount = null): bool
    {
        $amount ??= (float) $order->charge;

        if ($amount <= 0 || WalletTransaction::where('reference', "refund-{$order->id}")->exists()) {
            return false;
        }

        $this->wallet->credit($order->user, $amount, 'refund', "refund-{$order->id}", "Refund for order #{$order->id}");

        return true;
    }

    public function recordFailure(Order $order, Throwable $e): void
    {
        $order->update(['notes' => 'Supplier error: '.$e->getMessage()]);
    }
}
