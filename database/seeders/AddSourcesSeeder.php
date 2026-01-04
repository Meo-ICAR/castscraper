<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class AddSourcesSeeder extends Seeder
{
    public function run(): void
    {
        // Film Commission Regione Campania
        Source::updateOrCreate(
            ['base_url' => 'https://filmcommissioncampania.it'],
            [
                'name' => 'Film Commission Regione Campania',
                'list_url' => 'https://filmcommissioncampania.it/news',
                'adapter_class' => \App\Scrapers\Adapters\ExampleSiteAdapter::class,
                'selectors' => json_encode(['list' => 'article a']),
                'rate_limit_per_minute' => 30,
                'active' => true,
            ]
        );

        // AttoriCasting (already seeded by AttoriSourceSeeder, but ensure present)
        Source::updateOrCreate(
            ['base_url' => 'https://www.attoricasting.it'],
            [
                'name' => 'AttoriCasting',
                'list_url' => 'https://www.attoricasting.it',
                'adapter_class' => \App\Scrapers\Adapters\AttoriCastingAdapter::class,
                'selectors' => json_encode(['list' => 'article a']),
                'rate_limit_per_minute' => 60,
                'active' => true,
            ]
        );

        // Casting e Provini
        Source::updateOrCreate(
            ['base_url' => 'https://castingprovini.it'],
            [
                'name' => 'Casting e Provini',
                'list_url' => 'https://castingprovini.it',
                'adapter_class' => \App\Scrapers\Adapters\ExampleSiteAdapter::class,
                'selectors' => json_encode(['list' => 'article a']),
                'rate_limit_per_minute' => 60,
                'active' => true,
            ]
        );
    }
}
