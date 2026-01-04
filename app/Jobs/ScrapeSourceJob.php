<?php

namespace App\Jobs;

use App\Models\Source;
use App\Scrapers\Adapters\ExampleSiteAdapter;
use App\Scrapers\Fetcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $sourceId;

    public function __construct(int $sourceId)
    {
        $this->sourceId = $sourceId;
    }

    public function handle(): void
    {
        $source = Source::find($this->sourceId);
        if (! $source) {
            Log::warning('ScrapeSourceJob: source not found', ['id' => $this->sourceId]);
            return;
        }

        $fetcher = new Fetcher();

        $html = $fetcher->fetch($source->list_url ?? $source->base_url);
        if (is_null($html)) {
            Log::warning('ScrapeSourceJob: empty html', ['source' => $source->id]);
            return;
        }

        $adapterClass = $source->adapter_class ?: ExampleSiteAdapter::class;
        $adapter = new $adapterClass();

        try {
            $items = $adapter->parseList($html, $source);
        } catch (\Throwable $e) {
            Log::error('ScrapeSourceJob parseList failed', ['err' => $e->getMessage(), 'source' => $source->id]);
            return;
        }

        foreach ($items as $item) {
            // Ensure minimal structure
            if (empty($item['url']) && empty($item['external_id'])) {
                continue;
            }

            ProcessListingJob::dispatch($source->id, $item);
        }
    }
}
