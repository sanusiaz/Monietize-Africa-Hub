<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Seo;
use App\Support\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeoController extends Controller
{
    public function edit()
    {
        $values = [];
        foreach (Seo::DEFAULTS as $key => $default) {
            $values[$key] = Setting::get($key) ?? $default;
        }

        return Inertia::render('Admin/Seo', [
            'values' => $values,
            'defaults' => Seo::DEFAULTS,
            'siteName' => Site::name(),
            'links' => ['sitemap' => url('/sitemap.xml'), 'robots' => url('/robots.txt')],
            'appUrl' => url('/'),
            'localhost' => in_array(parse_url(url('/'), PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true) || str_ends_with((string) parse_url(url('/'), PHP_URL_HOST), '.test'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'seo_title_template' => 'required|string|max:120|regex:/\{title\}/',
            'seo_home_title' => 'required|string|max:120',
            'seo_home_description' => 'required|string|max:320',
            'seo_default_description' => 'required|string|max:320',
            'seo_contact_title' => 'required|string|max:120',
            'seo_contact_description' => 'required|string|max:320',
            'seo_og_image' => 'nullable|url:http,https|max:300',
            'seo_twitter_handle' => ['nullable', 'string', 'max:40', 'regex:/^@?[A-Za-z0-9_]+$/'],
            'seo_google_verification' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'seo_bing_verification' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'seo_noindex' => 'required|boolean',
            'seo_robots_extra' => 'nullable|string|max:2000',
        ], [
            'seo_title_template.regex' => 'The title template must contain {title}.',
            'seo_google_verification.regex' => 'Paste only the verification code, not the whole tag.',
            'seo_bing_verification.regex' => 'Paste only the verification code, not the whole tag.',
        ]);

        $data['seo_noindex'] = $data['seo_noindex'] ? '1' : '0';

        foreach ($data as $key => $value) {
            Setting::put($key, $value === null ? null : (string) $value);
        }

        return back()->with('success', 'SEO settings saved.');
    }

    public function reset()
    {
        foreach (Seo::DEFAULTS as $key => $default) {
            Setting::put($key, $default === '' ? null : $default);
        }

        return back()->with('success', 'SEO settings restored to the recommended defaults.');
    }
}
