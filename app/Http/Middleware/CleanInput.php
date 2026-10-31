<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use Illuminate\Http\Request;

/**
 * Normalises everything users submit before it reaches validation:
 *  - strips control characters and invalid UTF-8 (keeps normal line breaks and tabs)
 *  - lower-cases email addresses
 *  - single-line text fields lose HTML tags and extra whitespace
 * Passwords and long-form content are left as typed, apart from control characters.
 */
class CleanInput extends TransformsRequest
{
    /** Fields that should only ever hold one line of plain text. */
    private const SINGLE_LINE = [
        'name', 'subject', 'title', 'company_name', 'site_name', 'tagline', 'support_hours', 'business_address',
        'business_country', 'support_phone', 'mail_from_name', 'meta_description', 'note', 'platform',
        'seo_home_title', 'seo_home_description', 'seo_contact_title', 'seo_contact_description', 'seo_default_description',
    ];

    public function handle($request, \Closure $next)
    {
        // the Paystack webhook is verified against its raw body; leave it exactly as received
        if ($request->is('webhooks/*')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    protected function transform($key, $value)
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = mb_scrub($value, 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        $field = is_string($key) ? $key : '';

        if (str_contains($field, 'password')) {
            return $value;
        }

        if (str_contains($field, 'email')) {
            return mb_strtolower(trim($value));
        }

        if (in_array($field, self::SINGLE_LINE, true)) {
            return trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
        }

        return $value;
    }
}
