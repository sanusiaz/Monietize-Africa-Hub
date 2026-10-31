<?php

namespace App\Models;

use App\Services\Notifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public const STATUSES = ['pending', 'processing', 'in_progress', 'completed', 'partial', 'canceled', 'refunded'];

    /** Only these columns can be mass-assigned. */
    protected $fillable = ['user_id', 'service_id', 'link', 'quantity', 'charge', 'status', 'start_count', 'remains', 'provider_order_id', 'notes', 'created_at', 'updated_at'];

    protected static function booted(): void
    {
        // however an order reaches "completed" (admin edit or supplier sync), the customer is told once
        static::updated(function (Order $order) {
            if ($order->wasChanged('status') && $order->status === 'completed') {
                app(Notifier::class)->orderCompleted($order->loadMissing('user'));
            }
        });
    }

    protected function casts(): array
    {
        return ['charge' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
