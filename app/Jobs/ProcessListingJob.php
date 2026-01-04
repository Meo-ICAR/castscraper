<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Models\Source;
use App\Scrapers\AdapterFactory;
use App\Scrapers\Fetcher;
use App\Scrapers\Parser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Jobs\DownloadAttachmentJob;

class ProcessListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $sourceId;
    public array $item;

    public function __construct(int $sourceId, array $item)
    {
        $this->sourceId = $sourceId;
        $this->item = $item;
    }

    public function handle(): void
    {
        $source = Source::find($this->sourceId);
        if (! $source) {
            Log::warning('ProcessListingJob: source not found', ['id' => $this->sourceId]);
            return;
        }

        $fetcher = new Fetcher();
        $adapter = AdapterFactory::make($source);

        // Check if listing already exists by external_id before fetching detail
        if (! empty($this->item['external_id'])) {
            $existing = Listing::where('source_id', $source->id)
                ->where('external_id', $this->item['external_id'])
                ->first();

            if ($existing && $existing->scraped_at && $existing->scraped_at->gt(now()->subDays(1))) {
                // Already scraped recently, just update timestamp
                $existing->update(['scraped_at' => now()]);
                return;
            }
        }

        $detailHtml = null;
        if (! empty($this->item['url'])) {
            $detailHtml = $fetcher->fetch($this->item['url']);
        }

        $detail = [];
        if (! empty($detailHtml)) {
            try {
                $detail = $adapter->parseDetail($detailHtml, $source);
            } catch (\Throwable $e) {
                Log::warning('ProcessListingJob parseDetail failed', ['err' => $e->getMessage()]);
            }
        }

        // Merge list-level and detail-level data (detail overrides)
        $data = array_merge($this->item, $detail);
        $data['source_id'] = $source->id;

        $data['title'] = Parser::normalizeText($data['title'] ?? null);
        $data['description'] = Parser::normalizeText($data['description'] ?? null);

        // Attempt to parse date
        if (! empty($data['date'])) {
            $dt = Parser::parseDate($data['date']);
            if ($dt) {
                $data['date_posted'] = $dt->format('Y-m-d H:i:s');
            }
        }

        // Compute content hash
        $data['content_hash'] = Parser::contentHash($data);

        // Dedupe by external_id if available (already checked above, but might be new)
        // or by content_hash
        if (! isset($existing) || ! $existing) {
            if (! empty($data['external_id'])) {
                $existing = Listing::where('source_id', $source->id)
                    ->where('external_id', $data['external_id'])
                    ->first();
            }

            if (! $existing) {
                $existing = Listing::where('content_hash', $data['content_hash'])->first();
            }
        }

        if ($existing) {
            // Update timestamps and fields if changed
            $existing->fill([
                'title' => $data['title'] ?? $existing->title,
                'description' => $data['description'] ?? $existing->description,
                'url' => $data['url'] ?? $existing->url,
                'scraped_at' => now(),
                'parsed_at' => now(),
            ]);
            $existing->save();
            return;
        }

        // Create new listing
        $listing = Listing::create([
            'source_id' => $data['source_id'],
            'external_id' => $data['external_id'] ?? null,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'company' => $data['company'] ?? null,
            'location' => $data['location'] ?? null,
            'city' => $data['city'] ?? null,
            'region' => $data['region'] ?? null,
            'country' => $data['country'] ?? 'Italy',
            'date_posted' => $data['date_posted'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'url' => $data['url'] ?? null,
            'content_hash' => $data['content_hash'],
            'scraped_at' => now(),
            'parsed_at' => now(),
            'raw_html' => $detailHtml,
            'extra' => $data['extra'] ?? null,
        ]);

        // Save attachments metadata and dispatch download jobs
        if (! empty($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $attUrl) {
                try {
                    $attachment = $listing->attachments()->create([
                        'source_url' => $attUrl,
                        'local_path' => null,
                        'mime' => null,
                        'size' => null,
                    ]);

                    // Dispatch a separate job to download the attachment
                    DownloadAttachmentJob::dispatch($attachment->id);
                } catch (\Throwable $e) {
                    Log::warning('ProcessListingJob attachment save failed', ['err' => $e->getMessage()]);
                }
            }
        }
    }
}
