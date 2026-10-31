<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** Only these columns can be mass-assigned. */
    protected $fillable = ['category_id', 'provider_id', 'provider_service_id', 'name', 'description', 'rate', 'min', 'max', 'drip_feed', 'refill', 'is_active'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'drip_feed' => 'boolean',
            'refill' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
