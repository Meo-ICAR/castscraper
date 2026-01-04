<?php

namespace App\Scrapers\Adapters;

use App\Models\Source;
use App\Scrapers\AdapterInterface;
use Symfony\Component\DomCrawler\Crawler;

class ExampleSiteAdapter implements AdapterInterface
{
    public function parseList(string $html, Source $source): array
    {
        $crawler = new Crawler($html);

        $items = [];

        // Best-effort default: find article links
        $crawler->filter('a')->each(function (Crawler $node) use (&$items, $source) {
            $href = $node->attr('href');
            $title = trim($node->text());

            if (empty($href) || empty($title)) {
                return;
            }

            $items[] = [
                'external_id' => sha1($href),
                'url' => $this->absolutize($href, $source->base_url),
                'title' => $title,
            ];
        });

        return $items;
    }

    public function parseDetail(string $html, Source $source): array
    {
        $crawler = new Crawler($html);

        $title = $crawler->filter('h1')->count() ? trim($crawler->filter('h1')->first()->text()) : null;
        $description = '';
        if ($crawler->filter('article')->count()) {
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

        return [
            'title' => $title,
            'description' => $description,
            'attachments' => $attachments,
        ];
    }

    protected function absolutize(string $url, string $base): string
    {
        if (strpos($url, 'http') === 0) {
            return $url;
        }

        return rtrim($base, '/').'/'.ltrim($url, '/');
    }
}
