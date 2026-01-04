<?php

namespace Tests\Unit;

use App\Models\Source;
use App\Scrapers\Adapters\AttoriCastingAdapter;
use Tests\TestCase;

class AttoriCastingAdapterTest extends TestCase
{
    public function test_parse_detail_extracts_emails_and_attachments(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/attoricasting_detail.html');

        $source = new Source(['base_url' => 'https://www.attoricasting.it']);

        $adapter = new AttoriCastingAdapter();
        $result = $adapter->parseDetail($html, $source);

        $this->assertArrayHasKey('title', $result);
        $this->assertStringContainsString('Casting Attori Principali', $result['title']);

        $this->assertArrayHasKey('extra', $result);
        $this->assertArrayHasKey('emails', $result['extra']);
        $this->assertContains('casting@example.com', $result['extra']['emails']);

        $this->assertArrayHasKey('attachments', $result);
        $this->assertNotEmpty($result['attachments']);
        $this->assertStringContainsString('/images/poster.jpg', $result['attachments'][0]);
        
        $this->assertArrayHasKey('cast_required', $result['extra']);
        $this->assertStringContainsString('Stiamo cercando attori', $result['extra']['cast_required']);
    }

    public function test_parse_list_finds_listing_links(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/attoricasting_list.html');
        $source = new Source(['base_url' => 'https://www.attoricasting.it']);

        $adapter = new AttoriCastingAdapter();
        $items = $adapter->parseList($html, $source);

        $this->assertNotEmpty($items);
        $this->assertEquals('https://www.attoricasting.it/altri-casting/casting-cortometraggi/casting-attori-principali-e-figurazioni-per-il-corto-oltre-il-silenzio/76898/', $items[0]['url']);
        $this->assertStringContainsString('Casting Attori Principali', $items[0]['title']);
    }
}
