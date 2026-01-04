<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Models\Source;
use App\Models\ScrapingKeyword;
use App\Models\Profession;
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
use Illuminate\Support\Str;
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

        // Merge list-level and detail-level data (detail overrides, but keep list description if detail is empty)
        $data = array_merge($this->item, array_filter($detail));
        $data['source_id'] = $source->id;

        $data['title'] = Str::limit(Parser::normalizeText($data['title'] ?? null), 250);
        $data['description'] = Parser::normalizeText($data['description'] ?? null);

        if (! isset($data['extra'])) {
            $data['extra'] = [];
        }

        // Search for professions
        if (! empty($data['description'])) {
            $foundProfessions = [];
            // Get all professions for searching
            $professions = Profession::all();
            foreach ($professions as $prof) {
                // Case insensitive search
                if (stripos($data['description'], $prof->title) !== false) {
                    $foundProfessions[] = [
                        'area' => $prof->area,
                        'title' => $prof->title,
                    ];
                }
            }
            if (! empty($foundProfessions)) {
                $data['extra']['professions'] = array_values($foundProfessions);
            }
        }

        // If cast_required is not yet in extra, try to extract it from the description using DB keywords
        if (empty($data['extra']['cast_required']) && ! empty($data['description'])) {
            $castRequired = '';
            $keywords = ScrapingKeyword::where('active', true)->pluck('keyword')->toArray();
            
            if (empty($keywords)) {
                $keywords = ['Si cerca', 'Si cercano', 'Profili ricercati', 'Requisiti', 'Personaggi', 'Ruoli', 'Casting per', 'Stiamo cercando'];
            }

            $lines = explode("\n", $data['description']);
            if (count($lines) === 1) {
                $lines = explode(". ", $data['description']);
            }

            foreach ($lines as $line) {
                $line = trim($line);
                foreach ($keywords as $keyword) {
                    if (stripos($line, $keyword) !== false && strlen($line) > 10) {
                        $castRequired .= $line . "\n";
                        break;
                    }
                }
            }

            if ($castRequired) {
                $data['extra']['cast_required'] = trim($castRequired);
            }
        }

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

        // Create special attachment for cast_required text
        if (! empty($data['extra']['cast_required'])) {
            try {
                $content = $data['extra']['cast_required'];
                $filename = 'casting_requirements.txt';
                $dir = "listings/{$listing->id}";
                $path = "{$dir}/{$filename}";
                
                Storage::disk('public')->put($path, $content);
                try {
                    Storage::disk('public')->setVisibility($path, 'public');
                } catch (\Throwable $e) {
                    // ignore visibility errors
                }
                
                $listing->attachments()->create([
                    'source_url' => 'virtual://cast_required',
                    'local_path' => $path,
                    'mime' => 'text/plain',
                    'size' => strlen($content),
                ]);
            } catch (\Throwable $e) {
                Log::warning('ProcessListingJob cast_required attachment save failed', ['err' => $e->getMessage()]);
            }
        }

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
