<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Seo;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show(string $slug, Seo $seo)
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();

        $description = $page->meta_description ? Page::fillTokens($page->meta_description) : null;

        $seo->page($page->title, $description, jsonLd: [[
            '@type' => 'WebPage',
            'name' => $page->title,
            'url' => url($page->slug),
            'dateModified' => $page->updated_at->toAtomString(),
            'isPartOf' => ['@id' => url('/').'#website'],
        ]]);

        return Inertia::render('PageView', [
            'page' => [
                'title' => $page->title,
                'html' => $page->html(),
                'updated_at' => $page->updated_at->toIso8601String(),
            ],
        ]);
    }
}
