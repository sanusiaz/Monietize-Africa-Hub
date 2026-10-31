<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const SECRETS = ['paystack_secret_key', 'mail_password'];

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    /** Only these columns can be mass-assigned. */
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings', fn () => static::pluck('value', 'key')->all());
        $value = $all[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        return in_array($key, self::SECRETS, true) ? decrypt($value) : $value;
    }

    public static function put(string $key, ?string $value): void
    {
        if ($value !== null && $value !== '' && in_array($key, self::SECRETS, true)) {
            $value = encrypt($value);
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings');
    }
}
