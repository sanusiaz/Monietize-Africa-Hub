<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\Seo;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // one Seo object per request: controllers fill it in, the page head reads it
        $this->app->scoped(Seo::class);
    }

    public function boot(): void
    {
        $this->applyMailSettings();

        // a site served over https must never build http links (mixed content, insecure redirects)
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('app', fn ($view) => $view->with('seo', app(Seo::class)->toArray()));
    }

    /** SMTP details are managed in Admin → Settings; they override .env when filled in. */
    private function applyMailSettings(): void
    {
        try {
            if ($host = Setting::get('mail_host')) {
                config(['mail.default' => 'smtp', 'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), [
                    'transport' => 'smtp',
                    'scheme' => Setting::get('mail_encryption') === 'ssl' ? 'smtps' : 'smtp',
                    'host' => $host,
                    'port' => (int) Setting::get('mail_port', 587),
                    'username' => Setting::get('mail_username'),
                    'password' => Setting::get('mail_password'),
                ])]);
            }

            if ($from = Setting::get('mail_from_address')) {
                config(['mail.from.address' => $from]);
            }

            config(['mail.from.name' => Setting::get('mail_from_name') ?: Setting::get('site_name', config('app.name'))]);
        } catch (Throwable) {
            // settings table not migrated yet (fresh install / first migrate)
        }
    }
}
