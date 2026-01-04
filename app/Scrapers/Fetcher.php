<?php

namespace App\Scrapers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Fetcher
{
    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
    ];

    protected function getHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9',
            'Accept-Language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Upgrade-Insecure-Requests' => '1',
        ];
    }

    /**
     * Fetch the content for a given URL with simple retry/backoff.
     */
    public function fetch(string $url, int $retries = 3, int $timeout = 15): ?string
    {
        $attempt = 0;

        while ($attempt <= $retries) {
            try {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout($timeout)
                    ->withOptions(['verify' => true, 'allow_redirects' => true])
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
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout($timeout)
                    ->withOptions(['verify' => true, 'allow_redirects' => true])
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
