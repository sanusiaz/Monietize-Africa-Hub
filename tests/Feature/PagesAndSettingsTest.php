<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_privacy_and_terms_ship_with_the_site_and_fill_in_business_details(): void
    {
        Setting::put('site_name', 'Lux Growth');
        Setting::put('support_email', 'help@lux.test');
        Setting::put('business_country', 'Nigeria');

        $this->get('/privacy-policy')->assertOk()->assertSee('Lux Growth')->assertSee('help@lux.test');
        $this->get('/terms-and-conditions')->assertOk()->assertSee('laws of Nigeria');
    }

    public function test_placeholder_fallbacks_read_naturally_when_details_are_missing(): void
    {
        $html = Page::toHtml('Reach us {{support_email|through the contact page}} under {{country|the law where we are based}}.');

        $this->assertStringContainsString('through the contact page', $html);
        $this->assertStringContainsString('the law where we are based', $html);
        $this->assertStringNotContainsString('{{', $html);
    }

    public function test_page_markdown_cannot_inject_scripts(): void
    {
        $html = Page::toHtml("Hello <script>alert(1)</script> [x](javascript:alert(1))");

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_unpublished_and_unknown_pages_are_404(): void
    {
        Page::where('slug', 'privacy-policy')->update(['is_published' => false]);

        $this->get('/privacy-policy')->assertNotFound();
        $this->get('/does-not-exist')->assertNotFound();
    }

    public function test_admin_can_create_edit_and_delete_pages_but_not_take_reserved_addresses(): void
    {
        $admin = $this->admin();
        $payload = ['title' => 'About Us', 'content' => '# About', 'is_published' => true, 'show_in_footer' => true];

        $this->actingAs($admin)->post('/admin/pages', $payload)->assertSessionHasNoErrors();
        $page = Page::where('slug', 'about-us')->firstOrFail();
        $this->get('/about-us')->assertOk()->assertSee('About');

        $this->actingAs($admin)->put("/admin/pages/{$page->id}", $payload + ['title' => 'About', 'slug' => 'about'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pages', ['slug' => 'about']);

        $this->actingAs($admin)->post('/admin/pages', ['title' => 'Login', 'slug' => 'login'] + $payload)->assertSessionHasErrors('slug');
        $this->actingAs($admin)->post('/admin/pages', ['title' => 'Admin', 'slug' => 'admin'] + $payload)->assertSessionHasErrors('slug');

        $this->actingAs($admin)->delete("/admin/pages/{$page->id}");
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_legal_pages_cannot_be_deleted_or_moved(): void
    {
        $admin = $this->admin();
        $privacy = Page::where('slug', 'privacy-policy')->first();

        $this->actingAs($admin)->delete("/admin/pages/{$privacy->id}");
        $this->assertDatabaseHas('pages', ['slug' => 'privacy-policy']);

        $this->actingAs($admin)->put("/admin/pages/{$privacy->id}", ['title' => 'Privacy', 'slug' => 'something-else', 'content' => 'x', 'is_published' => true, 'show_in_footer' => true]);
        $this->assertDatabaseHas('pages', ['slug' => 'privacy-policy', 'title' => 'Privacy']);
    }

    public function test_footer_lists_published_pages_only_and_customers_cannot_manage_pages(): void
    {
        Page::where('slug', 'terms-and-conditions')->update(['show_in_footer' => false]);

        $this->get('/')->assertInertia(fn ($p) => $p->has('footerPages', 1)->where('footerPages.0.slug', 'privacy-policy'));

        $this->actingAs(User::factory()->create())->get('/admin/pages')->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/admin/pages', ['title' => 'x'])->assertForbidden();
    }

    public function test_settings_store_smtp_password_encrypted_and_never_send_it_back(): void
    {
        $admin = $this->admin();
        $base = ['site_name' => 'Lux', 'currency' => 'NGN', 'min_topup' => 500];

        $this->actingAs($admin)->put('/admin/settings', $base + ['mail_host' => 'smtp.test', 'mail_port' => 587, 'mail_encryption' => 'tls', 'mail_password' => 'sup3r-secret', 'support_email' => 'help@lux.test'])
            ->assertSessionHasNoErrors();

        $this->assertNotSame('sup3r-secret', Setting::find('mail_password')->value);
        $this->assertSame('sup3r-secret', Setting::get('mail_password'));

        // blank password on a later save keeps the stored one
        $this->actingAs($admin)->put('/admin/settings', $base + ['mail_host' => 'smtp.test', 'mail_password' => '']);
        $this->assertSame('sup3r-secret', Setting::get('mail_password'));

        $response = $this->actingAs($admin)->get('/admin/settings');
        $this->assertStringNotContainsString('sup3r-secret', $response->getContent());
        $response->assertInertia(fn ($p) => $p->where('settings.has_mail_password', true)->where('mailConfigured', true));
    }

    public function test_admin_can_manage_the_inbox(): void
    {
        $admin = $this->admin();
        $this->post('/contact', ['name' => 'Chidi', 'email' => 'c@example.com', 'subject' => 'Hello', 'message' => 'A real question here.']);
        $message = \App\Models\ContactMessage::first();

        $this->actingAs($admin)->get('/admin/messages')->assertOk()->assertInertia(fn ($p) => $p->where('unread', 1));
        $this->actingAs($admin)->put("/admin/messages/{$message->id}/read");
        $this->assertNotNull($message->fresh()->read_at);
        $this->actingAs($admin)->delete("/admin/messages/{$message->id}");
        $this->assertDatabaseCount('contact_messages', 0);

        $this->actingAs(User::factory()->create())->get('/admin/messages')->assertForbidden();
    }
}
