<?php

namespace App\Support;

use App\Models\Setting;

/** Live-chat widget settings; null when the admin has switched chat off or left the provider unconfigured. */
class Chat
{
    public const PROVIDERS = ['tawk' => 'Tawk.to', 'tidio' => 'Tidio', 'crisp' => 'Crisp', 'custom' => 'Custom embed code'];

    public static function config(): ?array
    {
        if (Setting::get('chat_enabled') !== '1') {
            return null;
        }

        $provider = Setting::get('chat_provider', 'tawk');

        $settings = match ($provider) {
            'tawk' => ($property = Setting::get('chat_tawk_property')) ? ['property' => $property, 'widget' => Setting::get('chat_tawk_widget') ?: 'default'] : null,
            'tidio' => ($key = Setting::get('chat_tidio_key')) ? ['key' => $key] : null,
            'crisp' => ($id = Setting::get('chat_crisp_id')) ? ['id' => $id] : null,
            'custom' => ($code = Setting::get('chat_custom_code')) ? ['code' => $code] : null,
            default => null,
        };

        return $settings ? ['provider' => $provider] + $settings : null;
    }
}
