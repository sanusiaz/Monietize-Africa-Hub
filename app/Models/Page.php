<?php

namespace App\Models;

use App\Support\Site;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    /** URLs the application already uses; a page may not take them. */
    public const RESERVED = [
        'admin', 'login', 'logout', 'register', 'account', 'wallet', 'new-order', 'my-orders',
        'contact', 'webhooks', 'up', 'build', 'storage', 'api', 'sitemap', 'robots', 'images', 'seo',
    ];

    /** Only these columns can be mass-assigned. */
    protected $fillable = ['title', 'slug', 'content', 'meta_description', 'is_published', 'show_in_footer', 'sort_order'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'show_in_footer' => 'boolean'];
    }

    /**
     * Replace {{token}} / {{token|fallback}} placeholders with the business details
     * managed in Admin → Settings, so policy text never needs hand-editing for names.
     */
    public static function fillTokens(string $text, ?\DateTimeInterface $updatedAt = null): string
    {
        $site = Site::profile();
        $values = [
            'site_name' => $site['name'],
            'company_name' => $site['company_name'],
            'support_email' => $site['support_email'],
            'country' => $site['country'],
            'updated_at' => ($updatedAt ?? now())->format('F j, Y'),
        ];

        return preg_replace_callback('/\{\{\s*(\w+)\s*(?:\|([^}]*))?\}\}/', function ($m) use ($values) {
            $value = $values[$m[1]] ?? null;

            return ($value !== null && $value !== '') ? $value : trim($m[2] ?? '');
        }, $text);
    }

    public static function toHtml(string $markdown, ?\DateTimeInterface $updatedAt = null): string
    {
        return Str::markdown(self::fillTokens($markdown, $updatedAt), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function html(): string
    {
        return self::toHtml($this->content, $this->updated_at);
    }
}
