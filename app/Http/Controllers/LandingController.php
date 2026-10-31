<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Support\Faq;
use App\Support\Seo;
use App\Support\Site;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function __invoke(Seo $seo)
    {
        $categories = Category::where('is_active', true)->get(['id', 'platform']);

        $services = Service::where('is_active', true)->whereIn('category_id', $categories->pluck('id'))
            ->with('category:id,name,platform')->orderBy('rate')->limit(300)->get();

        $faqs = Faq::items();

        $seo->page(
            Seo::value('seo_home_title'),
            Seo::value('seo_home_description'),
            fullTitle: true,
            jsonLd: [
                $seo->organization(),
                $seo->website(),
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(fn ($f) => [
                        '@type' => 'Question', 'name' => $f['q'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
                    ], $faqs),
                ],
            ],
        );

        return Inertia::render('Landing', [
            'platforms' => $categories->pluck('platform')->unique()->values(),
            'serviceCount' => $services->count(),
            'minRate' => $services->min('rate'),
            // the full price list: every active service, grouped by platform, cheapest first
            'catalogue' => $services->groupBy(fn ($s) => $s->category->platform)->map(fn ($group) => $group->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category->name,
                'rate' => $s->rate,
                'min' => $s->min,
                'max' => $s->max,
                'refill' => $s->refill,
                'drip_feed' => $s->drip_feed,
            ])->values()),
            'faqs' => $faqs,
            'currencyCode' => Site::currency(),
        ]);
    }
}
