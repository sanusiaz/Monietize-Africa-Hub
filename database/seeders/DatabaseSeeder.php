<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * `php artisan db:seed` runs everything the site needs:
     *   settings and SEO defaults, the shipped legal pages, the starter catalogue (7 categories, 14 services)
     *   and the admin account from .env. Fake customers, orders and payments are added on development installs only.
     * Every seeder is safe to run again; none overwrites what the admin has edited (except AdminSeeder,
     * which resets the admin password to the one in .env).
     */
    public function run(): void
    {
        $this->call([
            SeoSettingsSeeder::class,
            DefaultPages::class,
            StarterCatalogueSeeder::class,
            AdminSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
