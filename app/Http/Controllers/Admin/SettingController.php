<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\Setting;
use App\Services\PaystackService;
use App\Support\Chat;
use App\Support\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Throwable;

class SettingController extends Controller
{
    /** Plain settings the form edits; secrets are handled separately so they are never sent to the browser. */
    private const FIELDS = [
        'site_name', 'currency', 'min_topup', 'tagline',
        'company_name', 'business_address', 'business_country', 'support_email', 'support_phone', 'support_hours',
        'social_instagram', 'social_facebook', 'social_x', 'social_tiktok', 'social_telegram', 'social_whatsapp',
        'admin_email', 'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_from_address', 'mail_from_name',
        'paystack_public_key',
        'chat_enabled', 'chat_provider', 'chat_tawk_property', 'chat_tawk_widget', 'chat_tidio_key', 'chat_crisp_id', 'chat_custom_code',
    ];

    public function edit()
    {
        $values = collect(self::FIELDS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all();

        return Inertia::render('Admin/Settings', [
            'settings' => array_merge($values, [
                'site_name' => Site::name(),
                'chat_enabled' => $values['chat_enabled'] === '1',
                'chat_provider' => $values['chat_provider'] ?? 'tawk',
                'chat_tawk_widget' => $values['chat_tawk_widget'] ?? 'default',
                'currency' => $values['currency'] ?? 'NGN',
                'min_topup' => $values['min_topup'] ?? 500,
                'mail_port' => $values['mail_port'] ?? 587,
                'mail_encryption' => $values['mail_encryption'] ?? 'tls',
                'paystack_public_key' => PaystackService::publicKey(),
                'has_secret' => (bool) PaystackService::secretKey(),
                'has_mail_password' => (bool) Setting::get('mail_password'),
            ]),
            'webhookUrl' => url('/webhooks/paystack'),
            'emailTypes' => EmailPreviewController::TYPES,
            'mailConfigured' => (bool) Setting::get('mail_host'),
            'chatProviders' => Chat::PROVIDERS,
            'chatLive' => Chat::config() !== null,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => 'required|string|max:80',
            'currency' => 'required|in:NGN,GHS,ZAR,USD,KES',
            'min_topup' => 'required|numeric|min:100',
            'tagline' => 'nullable|string|max:160',
            'company_name' => 'nullable|string|max:120',
            'business_address' => 'nullable|string|max:300',
            'business_country' => 'nullable|string|max:80',
            'support_email' => 'nullable|email:rfc|max:190',
            'support_phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+().\-\s]{5,40}$/'],
            'support_hours' => 'nullable|string|max:120',
            'social_instagram' => 'nullable|url:http,https|max:200',
            'social_facebook' => 'nullable|url:http,https|max:200',
            'social_x' => 'nullable|url:http,https|max:200',
            'social_tiktok' => 'nullable|url:http,https|max:200',
            'social_telegram' => 'nullable|url:http,https|max:200',
            'social_whatsapp' => 'nullable|url:http,https|max:200',
            'admin_email' => 'nullable|email:rfc|max:190',
            'mail_host' => ['nullable', 'string', 'max:190', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_encryption' => 'nullable|in:tls,ssl,none',
            'mail_username' => 'nullable|string|max:190',
            'mail_password' => 'nullable|string|max:300',
            'mail_from_address' => 'nullable|email:rfc|max:190',
            'mail_from_name' => 'nullable|string|max:120',
            'paystack_public_key' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'paystack_secret_key' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'chat_enabled' => 'sometimes|boolean',
            'chat_provider' => 'sometimes|in:'.implode(',', array_keys(Chat::PROVIDERS)),
            // provider IDs end up inside a script URL, so only plain identifier characters are allowed
            'chat_tawk_property' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
            'chat_tawk_widget' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
            'chat_tidio_key' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'chat_crisp_id' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'chat_custom_code' => 'nullable|string|max:5000',
        ], [
            'chat_tawk_property.regex' => 'Paste only the property ID, not the whole script.',
            'chat_tawk_widget.regex' => 'Paste only the widget ID, not the whole script.',
            'chat_tidio_key.regex' => 'Paste only the public key, not the whole script.',
            'chat_crisp_id.regex' => 'Paste only the website ID, not the whole script.',
        ]);

        if (array_key_exists('chat_enabled', $data)) {
            $data['chat_enabled'] = $data['chat_enabled'] ? '1' : '0';
        }

        foreach ($data as $key => $value) {
            // blank secret fields mean "keep the current value"
            if (in_array($key, Setting::SECRETS, true) && ! $value) {
                continue;
            }
            Setting::put($key, $value === null ? null : (string) $value);
        }

        return back()->with('success', 'Settings saved.');
    }

    /** Sends a real message through the saved SMTP settings to the signed-in admin. */
    public function testEmail(Request $request)
    {
        try {
            Mail::to($request->user()->email)->send(new TestMail);
        } catch (Throwable $e) {
            return back()->with('error', 'Email failed: '.$e->getMessage());
        }

        return back()->with('success', "Test email sent to {$request->user()->email}.");
    }
}
