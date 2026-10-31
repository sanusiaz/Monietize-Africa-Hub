<?php

namespace App\Http\Middleware;

use App\Models\ContactMessage;
use App\Models\Page;
use App\Support\Chat;
use App\Support\Seo;
use App\Support\Site;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function handle(Request $request, \Closure $next)
    {
        // SEO state belongs to one request; never let a long-lived worker carry it into the next
        app()->forgetInstance(Seo::class);

        return parent::handle($request, $next);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user?->only('id', 'name', 'email', 'role', 'balance'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // business details used by the public header/footer, contact page and titles
            'site' => fn () => Site::profile() + ['currency' => Site::currency()],
            'footerPages' => fn () => Page::where('is_published', true)->where('show_in_footer', true)
                ->orderBy('sort_order')->orderBy('title')->get(['title', 'slug']),
            'seo' => fn () => app(Seo::class)->toArray(),
            'chat' => fn () => Chat::config(),
            'unreadMessages' => fn () => $user?->isAdmin() ? ContactMessage::whereNull('read_at')->count() : 0,
        ]);
    }
}
