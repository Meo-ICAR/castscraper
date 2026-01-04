<?php

namespace App\Scrapers\Adapters;

use App\Models\Source;
use App\Scrapers\AdapterInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Adapter for attoricasting.it — best-effort parsing with fallbacks.
 *
 * This adapter uses multiple fallback selectors since sites vary. If the site markup
 * changes, prefer updating the `selectors` JSON on the `sources` table rather than
 * editing this adapter for simple changes.
 */
class AttoriCastingAdapter implements AdapterInterface
{
    public function parseList(string $html, Source $source): array
    {
        $crawler = new Crawler($html);

        $selectors = $source->selectors ?? [];

        $candidates = [
            'article a',
            '.post a',
            '.entry a',
            '.listing a',
            '.card a',
            'a[href*="casting"]',
            'a[href*="audizione"]',
            'a[href$=".html"]',
        ];

        if (is_array($selectors) && ! empty($selectors['list'])) {
            array_unshift($candidates, $selectors['list']);
        }

        $found = [];

        foreach ($candidates as $sel) {
            try {
                $nodes = $crawler->filter($sel);
            } catch (\Throwable $e) {
                continue;
            }

            if ($nodes->count() === 0) {
                continue;
            }

            $nodes->each(function (Crawler $node) use (&$found, $source) {
                $href = $node->attr('href');
                $title = trim($node->text());

                if (empty($href) || empty($title)) {
                    return;
                }

                // Basic normalization of URL
                $url = $this->absolutize($href, $source->base_url);

                $found[] = [
                    'external_id' => sha1($url),
                    'url' => $url,
                    'title' => $title,
                ];
            });

            if (! empty($found)) {
                break;
            }
        }

        // Remove duplicates by url
        $unique = [];
        $out = [];
        foreach ($found as $item) {
            if (isset($unique[$item['url']])) {
                continue;
            }
            $unique[$item['url']] = true;
            $out[] = $item;
        }

        return $out;
    }

    public function parseDetail(string $html, Source $source): array
    {
        $crawler = new Crawler($html);

        $title = $crawler->filter('h1')->count() ? trim($crawler->filter('h1')->first()->text()) : null;

        $description = '';
        if ($crawler->filter('.entry-content')->count()) {
            $description = trim($crawler->filter('.entry-content')->first()->text());
        } elseif ($crawler->filter('.post-content')->count()) {
            $description = trim($crawler->filter('.post-content')->first()->text());
        } elseif ($crawler->filter('article')->count()) {
            $description = trim($crawler->filter('article')->first()->text());
        } elseif ($crawler->filter('#content')->count()) {
            $description = trim($crawler->filter('#content')->first()->text());
        }

        $attachments = [];
        $crawler->filter('img')->each(function (Crawler $node) use (&$attachments, $source) {
            $src = $node->attr('src');
            if ($src) {
                $attachments[] = $this->absolutize($src, $source->base_url);
            }
        });

        // meta date hints
        $date = null;
        if ($crawler->filter('time')->count()) {
            $time = $crawler->filter('time')->first();
            $date = $time->attr('datetime') ?: $time->text();
        }

        // Extract emails: prefer mailto links, fall back to regex over HTML/text
        $emails = [];
        try {
            $crawler->filter('a[href^="mailto:"]')->each(function (Crawler $node) use (&$emails) {
                $href = $node->attr('href');
                if ($href) {
                    $email = preg_replace('/^mailto:/i', '', $href);
                    $email = trim($email);
                    if (! empty($email)) {
                        $emails[] = $email;
                    }
                }
            });
        } catch (\Throwable $e) {
            // ignore
        }

        // regex search in HTML for any emails
        if (preg_match_all('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $html, $matches)) {
            foreach ($matches[0] as $m) {
                $emails[] = $m;
            }
        }

        $emails = array_values(array_unique(array_map('trim', $emails)));

        $extra = [];
        if (! empty($emails)) {
            $extra['emails'] = $emails;
            // convenience: first contact email
            $extra['contact_email'] = $emails[0];
        }

        return [
            'title' => $title,
            'description' => $description,
            'date' => $date,
            'attachments' => $attachments,
            'extra' => $extra,
        ];
    }

    public function getNextPageUrl(string $html, string $currentUrl, Source $source): ?string
    {
        $crawler = new Crawler($html);

        $next = $crawler->filter('a.next, a.next-page, a[rel="next"]');

        if ($next->count()) {
            return $this->absolutize($next->first()->attr('href'), $source->base_url);
        }

        return null;
    }

    protected function absolutize(string $url, string $base): string
    {
        if (strpos($url, 'http') === 0) {
            return $url;
        }

        return rtrim($base, '/').'/'.ltrim($url, '/');
    }
}
