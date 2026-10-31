<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The starter catalogue: 7 categories and 14 services across 5 platforms (Instagram, Facebook, TikTok, YouTube and X).
 *
 * The services are active, with no supplier linked, so orders for them wait in Admin → Orders to be fulfilled
 * by hand. The prices are starting points; change them in Admin → Services.
 *
 * Safe to run more than once: anything that already exists (matched by category slug and service name) is left
 * exactly as the admin edited it, so this never overwrites a price or setting.
 */
class StarterCatalogueSeeder extends Seeder
{
    /** platform => category name => [service name, rate per 1,000] */
    private const CATALOGUE = [
        'instagram' => [
            'Instagram Followers' => [['Followers - Standard', 1800], ['Followers - Premium (Refill)', 3200]],
            'Instagram Likes' => [['Likes - Fast', 600], ['Likes - Real', 1400]],
            'Instagram Views' => [['Reel Views', 250]],
        ],
        'facebook' => ['Facebook Growth' => [['Page Likes', 2100], ['Post Likes', 700], ['Followers', 1900]]],
        'tiktok' => ['TikTok Growth' => [['Followers', 2600], ['Video Views', 150]]],
        'youtube' => ['YouTube Growth' => [['Views', 3000], ['Subscribers', 9000]]],
        'twitter' => ['X / Twitter Growth' => [['Followers', 3800], ['Likes', 1200]]],
    ];

    public function run(): void
    {
        $order = Category::max('sort_order') + 1;
        $created = 0;

        foreach (self::CATALOGUE as $platform => $categories) {
            foreach ($categories as $name => $services) {
                $category = Category::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'platform' => $platform, 'sort_order' => $order++, 'is_active' => true],
                );

                foreach ($services as [$title, $rate]) {
                    $service = Service::firstOrCreate(
                        ['category_id' => $category->id, 'name' => $title],
                        [
                            'rate' => $rate,
                            'min' => 50,
                            'max' => 50000,
                            'refill' => str_contains($title, 'Refill'),
                            'drip_feed' => $platform === 'instagram',
                            'is_active' => true,
                        ],
                    );

                    $created += $service->wasRecentlyCreated ? 1 : 0;
                }
            }
        }

        $this->command?->info("Starter catalogue ready ({$created} new service(s) added).");
    }
}
