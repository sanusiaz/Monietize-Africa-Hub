<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\StarterCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_five_categories_and_fourteen_services_and_nothing_else(): void
    {
        $this->seed(StarterCatalogueSeeder::class);

        $this->assertSame(7, Category::count());
        $this->assertSame(14, Service::count());
        $this->assertSame(0, Category::where('is_active', false)->count());
        $this->assertSame(0, Service::where('is_active', false)->count());
        $this->assertSame(0, Service::whereNotNull('provider_id')->count()); // manual fulfilment until a supplier is linked
        $this->assertSame(['facebook', 'instagram', 'tiktok', 'twitter', 'youtube'], Category::pluck('platform')->unique()->sort()->values()->all());

        // no customers, orders or payments are ever created by it
        $this->assertSame(0, User::count());
        $this->assertSame(0, Order::count());
        $this->assertSame(0, Payment::count());
    }

    public function test_running_it_again_never_duplicates_or_overwrites_admin_edits(): void
    {
        $this->seed(StarterCatalogueSeeder::class);

        $service = Service::where('name', 'Likes - Fast')->first();
        $service->update(['rate' => 999, 'is_active' => false]);
        Category::where('name', 'Instagram Views')->update(['name' => 'Renamed by admin', 'is_active' => false]);

        $this->seed(StarterCatalogueSeeder::class);

        $this->assertSame(14, Service::count());
        $this->assertSame(7, Category::count());
        $this->assertEquals(999, $service->fresh()->rate);
        $this->assertFalse($service->fresh()->is_active);
        $this->assertTrue(Category::where('name', 'Renamed by admin')->exists());
    }

    public function test_the_home_page_then_shows_prices_platforms_and_the_lowest_price(): void
    {
        $this->seed(StarterCatalogueSeeder::class);

        $this->get('/')->assertInertia(fn ($p) => $p
            ->where('serviceCount', 14)
            ->has('platforms', 5)
            ->where('minRate', fn ($v) => (float) $v === 150.0)
            ->has('catalogue.instagram', 5)
            ->has('catalogue.facebook', 3)
            ->has('catalogue.tiktok', 2)
            ->has('catalogue.youtube', 2)
            ->has('catalogue.twitter', 2));
    }
}
