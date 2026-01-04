<?php

namespace App\Scrapers;

use App\Models\Source;

interface AdapterInterface
{
    /**
     * Parse a listing page (list results) and return an array of item arrays.
     * Each item array should include at minimum: external_id, url, title, date (optional), and any snippet fields.
     *
     * @param string $html
     * @param Source $source
     * @return array<int,array<string,mixed>>
     */
    public function parseList(string $html, Source $source): array;

    /**
     * Parse a detail page and return normalized fields for a single listing.
     * Fields: external_id, title, description, company, location, city, region, date_posted, valid_until, attachments (array of urls), extra
     *
     * @param string $html
     * @param Source $source
     * @return array<string,mixed>
     */
    public function parseDetail(string $html, Source $source): array;
}
