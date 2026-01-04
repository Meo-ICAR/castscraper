<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Source;
use App\Jobs\ScrapeSourceJob;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dispatch a scrape for a given source id or name
Artisan::command('scrape:source {identifier}', function ($identifier) {
    // identifier may be numeric id or the source name
    $source = is_numeric($identifier)
        ? Source::find((int) $identifier)
        : Source::where('name', $identifier)->first();

    if (! $source) {
        $this->error('Source not found: ' . $identifier);
        return 1;
    }

    ScrapeSourceJob::dispatch($source->id);
    $this->info('ScrapeSourceJob dispatched for source: ' . $source->id);
    return 0;
})->describe('Dispatch a scrape job for the given source id or name');
