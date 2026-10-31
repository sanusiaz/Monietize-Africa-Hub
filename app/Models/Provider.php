<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    /** Only these columns can be mass-assigned. */
    protected $fillable = ['name', 'api_url', 'api_key', 'is_active'];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'api_key' => 'encrypted'];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
