<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Seo;
use Illuminate\Database\Seeder;

/**
 * Default SEO, branding and chat settings. Only fills settings that do not exist yet,
 * so running it again never overwrites what the admin has edited in Admin → SEO / Settings.
 */
class SeoSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = Seo::DEFAULTS + [
            'site_name' => 'Monetize Hub',
            'tagline' => 'Social media growth services, paid for from a wallet.',
            'chat_enabled' => '0',
            'chat_provider' => 'tawk',
        ];

        foreach ($defaults as $key => $value) {
            if (! Setting::whereKey($key)->exists()) {
                Setting::put($key, $value === '' ? null : $value);
            }
        }
    }
}
