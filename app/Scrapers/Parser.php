<?php

namespace App\Scrapers;

use Carbon\Carbon;

class Parser
{
    public static function normalizeText(?string $text): string
    {
        if (is_null($text)) {
            return '';
        }

        $text = trim($text);
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
    }

    public static function parseDate(?string $dateStr): ?\DateTimeImmutable
    {
        if (empty($dateStr)) {
            return null;
        }

        try {
            $dt = new Carbon($dateStr);
            return $dt->toImmutable();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function contentHash(array $data): string
    {
        // Use main fields to compute a stable hash
        $title = self::slugify($data['title'] ?? '');
        $company = self::slugify($data['company'] ?? '');
        $description = self::slugify($data['description'] ?? '');

        $seed = "{$title}|{$company}|{$description}";
        return sha1($seed);
    }

    protected static function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]/', '', $text);
        return $text;
    }
}
