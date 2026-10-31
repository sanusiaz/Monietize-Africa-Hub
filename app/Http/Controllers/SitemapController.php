<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Seo;

class SitemapController extends Controller
{
    public function sitemap()
    {
        $urls = [[
            'loc' => url('/'),
            'lastmod' => Service::max('updated_at') ?: now(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ], [
            'loc' => url('/contact'),
            'lastmod' => null,
            'changefreq' => 'yearly',
            'priority' => '0.6',
        ]];

        foreach (Page::where('is_published', true)->orderBy('sort_order')->get(['slug', 'updated_at']) as $page) {
            $urls[] = ['loc' => url($page->slug), 'lastmod' => $page->updated_at, 'changefreq' => 'monthly', 'priority' => '0.5'];
        }

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $lines = ['User-agent: *'];

        if (Setting::get('seo_noindex') === '1') {
            $lines[] = 'Disallow: /';
        } else {
            foreach (['/admin', '/account', '/wallet', '/new-order', '/my-orders', '/login', '/register', '/webhooks'] as $path) {
                $lines[] = "Disallow: {$path}";
            }
            $lines[] = 'Allow: /';
        }

        if ($extra = trim((string) Setting::get('seo_robots_extra', Seo::DEFAULTS['seo_robots_extra']))) {
            $lines[] = '';
            $lines[] = $extra;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
