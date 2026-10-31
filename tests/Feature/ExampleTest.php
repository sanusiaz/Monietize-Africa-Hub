<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_lists_only_active_services_grouped_by_platform_with_the_lowest_price(): void
    {
        $ig = Category::create(['name' => 'IG', 'slug' => 'ig', 'platform' => 'instagram']);
        $hidden = Category::create(['name' => 'TT', 'slug' => 'tt', 'platform' => 'tiktok', 'is_active' => false]);
        Service::create(['category_id' => $ig->id, 'name' => 'Followers', 'rate' => 1800, 'min' => 50, 'max' => 5000]);
        Service::create(['category_id' => $ig->id, 'name' => 'Likes', 'rate' => 600, 'min' => 50, 'max' => 5000]);
        Service::create(['category_id' => $ig->id, 'name' => 'Off', 'rate' => 1, 'min' => 1, 'max' => 5, 'is_active' => false]);
        Service::create(['category_id' => $hidden->id, 'name' => 'Hidden', 'rate' => 5, 'min' => 1, 'max' => 5]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Landing')
            ->where('platforms', ['instagram'])
            ->where('serviceCount', 2)
            ->where('minRate', fn ($v) => (float) $v === 600.0)
            ->has('catalogue.instagram', 2)
            ->where('catalogue.instagram.0.name', 'Likes') // cheapest first
            ->missing('catalogue.tiktok')
            ->has('faqs', 5));
    }

    public function test_guests_can_open_login(): void
    {
        $this->get('/login')->assertOk();
    }
}
