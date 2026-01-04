<?php

namespace App\Scrapers\Adapters;

use App\Models\Source;
use App\Scrapers\AdapterInterface;
use App\Scrapers\Parser;
use Symfony\Component\DomCrawler\Crawler;

class TelegramAdapter implements AdapterInterface
{
    public function parseList(string $html, Source $source): array
    {
        $crawler = new Crawler($html);
        $items = [];

        $crawler->filter('.tgme_widget_message_wrap')->each(function (Crawler $node) use (&$items, $source) {
            $messageNode = $node->filter('.tgme_widget_message');
            if ($messageNode->count() === 0) {
                return;
            }

            $postId = $messageNode->attr('data-post');
            if (!$postId) {
                return;
            }

            $url = $source->base_url . '/' . str_replace($source->name . '/', '', $postId);
            // Public preview URLs usually look like t.me/s/channel/123
            // We want the clean link: t.me/channel/123
            $cleanUrl = str_replace('/s/', '/', $url);

            $textNode = $node->filter('.tgme_widget_message_text');
            if ($textNode->count() === 0) {
                return;
            }

            $fullText = $textNode->text();
            $title = Parser::normalizeText(explode("\n", $fullText)[0]);

            // Date
            $dateNode = $node->filter('.tgme_widget_message_date time');
            $date = $dateNode->count() ? $dateNode->attr('datetime') : null;

            $items[] = [
                'external_id' => $postId,
                'url' => $cleanUrl,
                'title' => $title,
                'description' => $fullText,
                'date' => $date,
            ];
        });

        return array_reverse($items); // Newest messages are usually at the bottom in web view
    }

    public function parseDetail(string $html, Source $source): array
    {
        // For Telegram, the list already contains the full text of the message.
        // But we implement this to satisfy the interface.
        $crawler = new Crawler($html);
        
        $textNode = $crawler->filter('.tgme_widget_message_text');
        $description = $textNode->count() ? $textNode->text() : '';
        $title = Parser::normalizeText(explode("\n", $description)[0]);

        $dateNode = $crawler->filter('.tgme_widget_message_date time');
        $date = $dateNode->count() ? $dateNode->attr('datetime') : null;

        $attachments = [];
        $crawler->filter('.tgme_widget_message_photo_wrap')->each(function (Crawler $node) use (&$attachments) {
            $style = $node->attr('style');
            if (preg_match("/url\(['\"]?([^'\")]+)['\"]?\)/", $style, $matches)) {
                $attachments[] = $matches[1];
            }
        });

        return [
            'title' => $title,
            'description' => $description,
            'date' => $date,
            'attachments' => $attachments,
        ];
    }

    public function getNextPageUrl(string $html, string $currentUrl, Source $source): ?string
    {
        // Telegram web preview uses "before" parameter for pagination usually, 
        // but for now we just scrape the latest.
        return null;
    }
}
