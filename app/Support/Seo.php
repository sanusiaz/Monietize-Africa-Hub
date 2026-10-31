<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Per-request SEO state. Controllers of public pages describe themselves with page();
 * everything else stays noindex by default. Values come from Admin → SEO, falling back to DEFAULTS.
 */
class Seo
{
    /** Defaults used until the admin edits them; the SeoSettingsSeeder stores these too. */
    public const DEFAULTS = [
        'seo_title_template' => '{title} | {site_name}',
        'seo_home_title' => '{site_name} | Instagram, TikTok & YouTube Growth Services',
        'seo_home_description' => 'Grow your Instagram, Facebook, TikTok, YouTube and X accounts. See every price per 1,000 up front, pay from your wallet with Paystack and track each order.',
        'seo_default_description' => '{site_name} sells social media growth services with clear prices per 1,000, a built-in wallet and live order tracking.',
        'seo_contact_title' => 'Contact Us',
        'seo_contact_description' => 'Questions about an order, your wallet or our services? Send {site_name} a message and we will reply by email.',
        'seo_og_image' => '',
        'seo_twitter_handle' => '',
        'seo_google_verification' => '',
        'seo_bing_verification' => '',
        'seo_noindex' => '0',
        'seo_robots_extra' => '',
    ];

    private ?string $title = null;

    private bool $fullTitle = false;

    private ?string $description = null;

    private ?string $image = null;

    private bool $indexable = false;

    private string $type = 'website';

    private array $jsonLd = [];

    /** Value of one SEO setting with {site_name} filled in. */
    public static function value(string $key): string
    {
        $value = (string) (Setting::get($key) ?? self::DEFAULTS[$key] ?? '');

        return str_replace('{site_name}', Site::name(), $value);
    }

    /** Describe an indexable public page. $fullTitle skips the title template (used by the home page). */
    public function page(string $title, ?string $description = null, bool $fullTitle = false, string $type = 'website', array $jsonLd = [], ?string $image = null): static
    {
        $this->indexable = true;
        $this->title = $title;
        $this->fullTitle = $fullTitle;
        $this->description = $description;
        $this->type = $type;
        $this->jsonLd = $jsonLd;
        $this->image = $image;

        return $this;
    }

    public function organization(): array
    {
        $site = Site::profile();
        $org = array_filter([
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => $site['company_name'],
            'url' => url('/'),
            'logo' => url('/images/icon-512.png'),
            'sameAs' => array_values($site['socials']) ?: null,
            'contactPoint' => $site['support_email'] || $site['phone'] ? array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $site['support_email'],
                'telephone' => $site['phone'],
            ]) : null,
        ]);

        return $org;
    }

    public function website(): array
    {
        return ['@type' => 'WebSite', '@id' => url('/').'#website', 'url' => url('/'), 'name' => Site::name(), 'publisher' => ['@id' => url('/').'#organization']];
    }

    public function toArray(): array
    {
        $siteName = Site::name();
        $noindexSite = Setting::get('seo_noindex', self::DEFAULTS['seo_noindex']) === '1';
        $index = $this->indexable && ! $noindexSite;

        $title = $this->title ?? $siteName;
        if ($this->indexable && ! $this->fullTitle) {
            $title = str_replace(['{title}', '{site_name}'], [$title, $siteName], self::value('seo_title_template'));
        }

        $image = $this->image ?: self::value('seo_og_image') ?: url('/images/og-image.png');

        return [
            'title' => $title,
            'description' => $this->description ?: self::value('seo_default_description'),
            'canonical' => $index ? url()->current() : null,
            'robots' => $index ? 'index,follow,max-image-preview:large,max-snippet:-1' : 'noindex,nofollow',
            'image' => str_starts_with($image, 'http') ? $image : url($image),
            'type' => $this->type,
            'site_name' => $siteName,
            'twitter' => ltrim(self::value('seo_twitter_handle'), '@') ?: null,
            'google_verification' => self::value('seo_google_verification') ?: null,
            'bing_verification' => self::value('seo_bing_verification') ?: null,
            'jsonld' => $index && $this->jsonLd ? ['@context' => 'https://schema.org', '@graph' => $this->jsonLd] : null,
        ];
    }
}
