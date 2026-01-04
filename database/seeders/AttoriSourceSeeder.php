<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class AttoriSourceSeeder extends Seeder
{
    public function run(): void
    {
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
    }
}
