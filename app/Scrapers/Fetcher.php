<?php

namespace App\Scrapers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Fetcher
{
    protected array $defaultHeaders = [
        'User-Agent' => 'CastScraper/1.0 (+https://example.com) PHP/'.PHP_VERSION,
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
    ];

    /**
     * Fetch the content for a given URL with simple retry/backoff.
     */
    public function fetch(string $url, int $retries = 3, int $timeout = 15): ?string
    {
        $attempt = 0;

        while ($attempt <= $retries) {
            try {
                $response = Http::withHeaders($this->defaultHeaders)
                    ->timeout($timeout)
                    ->withOptions(['verify' => true])
                    ->get($url);

                if ($response->successful()) {
                    return $response->body();
                }

                // 429 or server error: backoff and retry
                $status = $response->status();
                if (in_array($status, [429, 500, 502, 503, 504], true)) {
                    $attempt++;
                    sleep((int) pow(2, $attempt));
                    continue;
                }

                Log::warning('Fetcher non-success', ['url' => $url, 'status' => $status]);
                return null;
            } catch (\Throwable $e) {
                Log::warning('Fetcher exception', ['url' => $url, 'err' => $e->getMessage()]);
                $attempt++;
                sleep((int) pow(2, $attempt));
            }
        }

        return null;
    }

    /**
     * Fetch binary content for a given URL. Returns array with contents, mime and size or null on failure.
     *
     * @return array{contents: string, mime: string|null, size: int}|null
     */
    public function fetchBinary(string $url, int $retries = 3, int $timeout = 30): ?array
    {
        $attempt = 0;

        while ($attempt <= $retries) {
            try {
                $response = Http::withHeaders($this->defaultHeaders)
                    ->timeout($timeout)
                    ->withOptions(['verify' => true])
                    ->get($url);

                if ($response->successful()) {
                    $body = $response->body();
                    $mime = $response->header('Content-Type');
                    $size = is_string($body) ? strlen($body) : 0;
                    return ['contents' => $body, 'mime' => $mime, 'size' => $size];
                }

                $status = $response->status();
                if (in_array($status, [429, 500, 502, 503, 504], true)) {
                    $attempt++;
                    sleep((int) pow(2, $attempt));
                    continue;
                }

                Log::warning('Fetcher binary non-success', ['url' => $url, 'status' => $status]);
                return null;
            } catch (\Throwable $e) {
                Log::warning('Fetcher binary exception', ['url' => $url, 'err' => $e->getMessage()]);
                $attempt++;
                sleep((int) pow(2, $attempt));
            }
        }

        return null;
    }
}
