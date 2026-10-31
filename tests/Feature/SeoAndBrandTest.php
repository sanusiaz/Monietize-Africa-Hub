<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\Seo;
use Database\Seeders\SeoSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAndBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_head_has_title_description_canonical_social_tags_and_structured_data(): void
    {
        $this->seed(SeoSettingsSeeder::class);
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<title inertia>Monetize Hub | Instagram, TikTok &amp; YouTube Growth Services</title>', $html);
        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/').'"', $html);
        $this->assertStringContainsString('name="robots" content="index,follow', $html);
        $this->assertStringContainsString('property="og:image" content="'.url('/images/og-image.png').'"', $html);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
        $this->assertStringContainsString('rel="icon" href="/favicon.ico"', $html);
        $this->assertStringContainsString('rel="apple-touch-icon"', $html);

        preg_match('#<script type="application/ld\+json" id="seo-jsonld">(.*?)</script>#s', $html, $m);
        $graph = json_decode($m[1], true)['@graph'];
        $this->assertEqualsCanonicalizing(['Organization', 'WebSite', 'FAQPage'], array_column($graph, '@type'));
    }

    public function test_seeded_defaults_fit_google_display_limits(): void
    {
        $this->seed(SeoSettingsSeeder::class);

        $this->assertLessThanOrEqual(60, strlen(Seo::value('seo_home_title')));
        $this->assertLessThanOrEqual(160, strlen(Seo::value('seo_home_description')));
        $this->assertGreaterThan(70, strlen(Seo::value('seo_home_description')));
        $this->assertSame('Monetize Hub', Setting::get('site_name'));
    }

    public function test_seeder_never_overwrites_admin_edits(): void
    {
        Setting::put('seo_home_title', 'My own title');
        $this->seed(SeoSettingsSeeder::class);

        $this->assertSame('My own title', Setting::get('seo_home_title'));
    }

    public function test_private_pages_are_noindex_and_public_pages_are_indexable(): void
    {
        $this->get('/login')->assertSee('name="robots" content="noindex,nofollow"', false);
        $this->get('/contact')->assertSee('index,follow', false)->assertSee('<title inertia>Contact Us | ', false);
        $this->get('/privacy-policy')->assertSee('index,follow', false)->assertSee('"@type":"WebPage"', false);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin')->assertSee('noindex,nofollow', false);
    }

    public function test_sitemap_lists_public_pages_and_hides_unpublished_ones(): void
    {
        Page::create(['title' => 'Draft', 'slug' => 'draft-page', 'content' => 'x', 'is_published' => false]);

        $response = $this->get('/sitemap.xml')->assertOk();
        $xml = $response->getContent();

        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<loc>'.url('/').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/contact').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/privacy-policy').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/terms-and-conditions').'</loc>', $xml);
        $this->assertStringNotContainsString('draft-page', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'sitemap must be valid XML');
    }

    public function test_robots_blocks_private_areas_points_to_sitemap_and_can_hide_the_whole_site(): void
    {
        $txt = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString('Disallow: /admin', $txt);
        $this->assertStringContainsString('Disallow: /wallet', $txt);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $txt);

        Setting::put('seo_noindex', '1');
        $this->assertStringContainsString("Disallow: /\n", $this->get('/robots.txt')->getContent());
        $this->get('/')->assertSee('noindex,nofollow', false);
        $this->assertStringNotContainsString('rel="canonical"', $this->get('/')->getContent());
    }

    public function test_admin_can_edit_seo_and_it_appears_in_the_page_head(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = [
            'seo_title_template' => '{title} - {site_name}',
            'seo_home_title' => 'Best prices | {site_name}',
            'seo_home_description' => 'Cheap and clear prices for every platform.',
            'seo_default_description' => 'Default text.',
            'seo_contact_title' => 'Talk to us',
            'seo_contact_description' => 'We reply fast.',
            'seo_og_image' => 'https://cdn.example.com/share.png',
            'seo_twitter_handle' => '@monetizehub',
            'seo_google_verification' => 'abc123_XYZ-9',
            'seo_bing_verification' => '',
            'seo_noindex' => false,
            'seo_robots_extra' => "User-agent: BadBot\nDisallow: /",
        ];

        $this->actingAs($admin)->put('/admin/seo', $payload)->assertSessionHasNoErrors();
        auth()->logout();

        $home = $this->get('/')->getContent();
        $this->assertStringContainsString('Best prices | Monetize Hub', $home);
        $this->assertStringContainsString('content="https://cdn.example.com/share.png"', $home);
        $this->assertStringContainsString('name="twitter:site" content="@monetizehub"', $home);
        $this->assertStringContainsString('name="google-site-verification" content="abc123_XYZ-9"', $home);
        $this->get('/contact')->assertSee('<title inertia>Talk to us - Monetize Hub</title>', false);
        $this->assertStringContainsString('User-agent: BadBot', $this->get('/robots.txt')->getContent());
    }

    public function test_seo_validation_and_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $base = ['seo_title_template' => '{title}', 'seo_home_title' => 'a', 'seo_home_description' => 'b', 'seo_default_description' => 'c', 'seo_contact_title' => 'd', 'seo_contact_description' => 'e', 'seo_noindex' => false];

        $this->actingAs($admin)->put('/admin/seo', ['seo_title_template' => 'no placeholder'] + $base)->assertSessionHasErrors('seo_title_template');
        $this->actingAs($admin)->put('/admin/seo', ['seo_google_verification' => '"><script>'] + $base)->assertSessionHasErrors('seo_google_verification');

        $this->actingAs($admin)->get('/admin/seo')->assertOk();
        $this->actingAs(User::factory()->create())->get('/admin/seo')->assertForbidden();
        $this->actingAs(User::factory()->create())->put('/admin/seo', $base)->assertForbidden();
    }

    public function test_restore_defaults_resets_edited_seo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('seo_home_title', 'Edited');

        $this->actingAs($admin)->post('/admin/seo/reset');

        $this->assertSame(Seo::DEFAULTS['seo_home_title'], Setting::get('seo_home_title'));
    }

    public function test_brand_assets_exist_and_manifest_is_valid(): void
    {
        foreach (['favicon.ico', 'images/favicon-16.png', 'images/favicon-32.png', 'images/apple-touch-icon.png', 'images/icon-192.png', 'images/icon-512.png', 'images/logo-mark.png', 'images/og-image.png'] as $file) {
            $this->assertFileExists(public_path($file));
            $this->assertGreaterThan(200, filesize(public_path($file)), $file);
        }

        $this->assertSame(1200, getimagesize(public_path('images/og-image.png'))[0]);
        $this->assertSame(630, getimagesize(public_path('images/og-image.png'))[1]);
        $this->assertSame('Monetize Hub', json_decode(file_get_contents(public_path('site.webmanifest')), true)['name']);
    }

    public function test_live_chat_is_off_by_default_and_admin_can_turn_it_on_and_off(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $base = ['site_name' => 'Monetize Hub', 'currency' => 'NGN', 'min_topup' => 500, 'chat_provider' => 'tawk', 'chat_tawk_property' => '5f3a1b2c4d5e6f7a8b9c0d1e', 'chat_tawk_widget' => 'default'];

        $this->get('/')->assertInertia(fn ($p) => $p->where('chat', null));

        $this->actingAs($admin)->put('/admin/settings', $base + ['chat_enabled' => true])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/')->assertInertia(fn ($p) => $p->where('chat.provider', 'tawk')->where('chat.property', '5f3a1b2c4d5e6f7a8b9c0d1e'));

        $this->actingAs($admin)->put('/admin/settings', $base + ['chat_enabled' => false]);
        auth()->logout();
        $this->get('/')->assertInertia(fn ($p) => $p->where('chat', null));
    }

    public function test_chat_enabled_without_provider_details_stays_hidden_and_ids_cannot_inject_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $base = ['site_name' => 'Monetize Hub', 'currency' => 'NGN', 'min_topup' => 500, 'chat_enabled' => true, 'chat_provider' => 'tidio'];

        $this->actingAs($admin)->put('/admin/settings', $base)->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/')->assertInertia(fn ($p) => $p->where('chat', null));

        $this->actingAs($admin)->put('/admin/settings', $base + ['chat_tidio_key' => 'abc"></script><script>alert(1)'])->assertSessionHasErrors('chat_tidio_key');
        $this->actingAs($admin)->put('/admin/settings', ['chat_provider' => 'crisp', 'chat_crisp_id' => '../../evil'] + $base)->assertSessionHasErrors('chat_crisp_id');
    }

    public function test_custom_chat_code_is_passed_through_for_admin_supplied_embeds(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/settings', ['site_name' => 'Monetize Hub', 'currency' => 'NGN', 'min_topup' => 500, 'chat_enabled' => true, 'chat_provider' => 'custom', 'chat_custom_code' => '<script src="https://chat.example.com/w.js"></script>']);
        auth()->logout();

        $this->get('/')->assertInertia(fn ($p) => $p->where('chat.provider', 'custom')->where('chat.code', '<script src="https://chat.example.com/w.js"></script>'));
    }

    public function test_pages_cannot_use_the_new_reserved_addresses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = ['title' => 'X', 'content' => 'y', 'is_published' => true, 'show_in_footer' => true];

        foreach (['sitemap', 'robots', 'images', 'seo'] as $slug) {
            $this->actingAs($admin)->post('/admin/pages', $payload + ['slug' => $slug])->assertSessionHasErrors('slug');
        }
    }

    public function test_price_list_shows_real_prices_and_privacy_policy_mentions_chat_cookies(): void
    {
        $cat = Category::create(['name' => 'IG', 'slug' => 'ig', 'platform' => 'instagram']);
        Service::create(['category_id' => $cat->id, 'name' => 'Followers', 'rate' => 1800, 'min' => 50, 'max' => 5000]);

        $this->get('/')->assertInertia(fn ($p) => $p->where('catalogue.instagram.0.rate', fn ($v) => (float) $v === 1800.0));
        $this->get('/privacy-policy')->assertSee('live chat');
    }
}
