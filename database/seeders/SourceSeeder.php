<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Source;
use App\Scrapers\Adapters\AttoriCastingAdapter;
use App\Scrapers\Adapters\ExampleSiteAdapter;
use App\Scrapers\Adapters\TelegramAdapter;

class SourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            [
                'name' => 'AttoriCasting Campania',
                'base_url' => 'https://www.attoricasting.it',
                'list_url' => 'https://www.attoricasting.it/casting-regione/campania/',
                'adapter_class' => AttoriCastingAdapter::class,
                'active' => true,
            ],
            [
                'name' => 'Film Commission Regione Campania',
                'base_url' => 'https://www.fcrc.it',
                'list_url' => 'https://www.fcrc.it/news/',
                'adapter_class' => ExampleSiteAdapter::class,
                'active' => true,
            ],
            [
                'name' => 'Casting e Provini',
                'base_url' => 'https://www.castingeprovini.com',
                'list_url' => 'https://www.castingeprovini.com/',
                'adapter_class' => ExampleSiteAdapter::class,
                'active' => true,
            ],
            [
                'name' => 'Klab4',
                'base_url' => 'https://www.klab4.it',
                'list_url' => 'https://www.klab4.it/',
                'adapter_class' => ExampleSiteAdapter::class,
                'active' => true,
            ],
            [
                'name' => 'Casting Kids Studios',
                'base_url' => 'https://www.castingkidsstudios.com',
                'list_url' => 'https://www.castingkidsstudios.com/',
                'adapter_class' => ExampleSiteAdapter::class,
                'active' => true,
            ],
            [
                'name' => 'CinemaFiction',
                'base_url' => 'https://www.cinemafiction.com',
                'list_url' => 'https://www.cinemafiction.com/',
                'adapter_class' => ExampleSiteAdapter::class,
                'active' => true,
            ],
            [
                'name' => 'La Vesuviana Management',
                'base_url' => 'https://www.lavesuviana.com',
                'list_url' => 'https://www.lavesuviana.com/',
                'adapter_class' => ExampleSiteAdapter::class,
                'active' => true,
            ],
            [
                'name' => 'Teatro di Napoli',
                'base_url' => 'https://www.teatrodinapoli.it',
                'list_url' => 'https://www.teatrodinapoli.it/news/',
                'adapter_class' => ExampleSiteAdapter::class,
                'active' => true,
            ],
            [
                'name' => 'Fondazione Campania dei Festival',
                'base_url' => 'https://fondazionecampaniadeifestival.it',
                'list_url' => 'https://fondazionecampaniadeifestival.it/bandi-e-avvisi/',
                'adapter_class' => ExampleSiteAdapter::class,
                'active' => true,
            ],
            [
                'name' => 'Telegram Attoricasting',
                'base_url' => 'https://t.me/attoricasting',
                'list_url' => 'https://t.me/s/attoricasting',
                'adapter_class' => TelegramAdapter::class,
                'active' => true,
            ],
            [
                'name' => 'Telegram Casting e Provini',
                'base_url' => 'https://t.me/castingeprovini',
                'list_url' => 'https://t.me/s/castingeprovini',
                'adapter_class' => TelegramAdapter::class,
                'active' => true,
            ],
        ];

        foreach ($sources as $sourceData) {
            Source::updateOrCreate(
                ['name' => $sourceData['name']],
                $sourceData
            );
        }
    }
}
