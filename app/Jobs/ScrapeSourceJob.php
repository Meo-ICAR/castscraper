<?php

namespace App\Jobs;

use App\Models\Source;
use App\Scrapers\AdapterFactory;
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
    public int $maxPages;
    public ?string $currentUrl;
    public int $pageCount;

    public function __construct(int $sourceId, int $maxPages = 1, ?string $currentUrl = null, int $pageCount = 1)
    {
        $this->sourceId = $sourceId;
        $this->maxPages = $maxPages;
        $this->currentUrl = $currentUrl;
        $this->pageCount = $pageCount;
    }

    public function handle(): void
    {
        $source = Source::find($this->sourceId);
        if (! $source) {
            Log::warning('ScrapeSourceJob: source not found', ['id' => $this->sourceId]);
            return;
        }

        $fetcher = new Fetcher();
        $url = $this->currentUrl ?: ($source->list_url ?? $source->base_url);

        Log::info('Scraping source', ['source' => $source->name, 'url' => $url, 'page' => $this->pageCount]);

        $html = $fetcher->fetch($url);
        if (is_null($html)) {
            Log::warning('ScrapeSourceJob: empty html', ['source' => $source->id, 'url' => $url]);
            return;
        }

        $adapter = AdapterFactory::make($source);

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

        // Pagination
        if ($this->pageCount < $this->maxPages) {
            $nextUrl = $adapter->getNextPageUrl($html, $url, $source);
            if ($nextUrl && $nextUrl !== $url) {
                self::dispatch($this->sourceId, $this->maxPages, $nextUrl, $this->pageCount + 1)
                    ->delay(now()->addSeconds(5)); // Add a small delay between pages
            }
        }
    }
}
