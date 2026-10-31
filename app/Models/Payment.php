<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** Only these columns can be mass-assigned. */
    protected $fillable = ['user_id', 'reference', 'gateway', 'amount', 'status', 'channel', 'paid_at', 'payload', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'payload' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
