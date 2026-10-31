<?php

namespace App\Support;

use App\Models\Setting;

/** Read-only accessors for admin-managed business details, shared by pages and emails. */
class Site
{
    private const SYMBOLS = ['NGN' => '₦', 'USD' => '$', 'GHS' => 'GH₵', 'ZAR' => 'R', 'KES' => 'KSh '];

    public static function name(): string
    {
        return (string) Setting::get('site_name', config('app.name'));
    }

    public static function currency(): string
    {
        return (string) Setting::get('currency', 'NGN');
    }

    public static function money(float|int|string|null $amount): string
    {
        return (self::SYMBOLS[self::currency()] ?? '').number_format((float) $amount, 2);
    }

    public static function supportEmail(): ?string
    {
        return Setting::get('support_email') ?: Setting::get('admin_email');
    }

    /** Business details shown in the footer, on the contact page and inside policy text. */
    public static function profile(): array
    {
        return [
            'name' => self::name(),
            'company_name' => Setting::get('company_name') ?: self::name(),
            'tagline' => Setting::get('tagline', 'Social media growth services, paid for from a wallet.'),
            'support_email' => self::supportEmail(),
            'phone' => Setting::get('support_phone'),
            'address' => Setting::get('business_address'),
            'country' => Setting::get('business_country'),
            'hours' => Setting::get('support_hours'),
            'socials' => array_filter([
                'instagram' => Setting::get('social_instagram'),
                'facebook' => Setting::get('social_facebook'),
                'x' => Setting::get('social_x'),
                'tiktok' => Setting::get('social_tiktok'),
                'telegram' => Setting::get('social_telegram'),
                'whatsapp' => Setting::get('social_whatsapp'),
            ]),
        ];
    }
}
