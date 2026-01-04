<?php

namespace App\Jobs;

use App\Models\Attachment;
use App\Scrapers\Fetcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $attachmentId;

    public function __construct(int $attachmentId)
    {
        $this->attachmentId = $attachmentId;
    }

    public function handle(): void
    {
        $attachment = Attachment::find($this->attachmentId);
        if (! $attachment) {
            Log::warning('DownloadAttachmentJob: attachment not found', ['id' => $this->attachmentId]);
            return;
        }

        // If already downloaded, skip
        if (! empty($attachment->local_path)) {
            return;
        }

        $fetcher = new Fetcher();

        try {
            $binary = $fetcher->fetchBinary($attachment->source_url);
        } catch (\Throwable $e) {
            Log::warning('DownloadAttachmentJob fetchBinary failed', ['err' => $e->getMessage(), 'url' => $attachment->source_url]);
            return;
        }

        if (! is_array($binary) || empty($binary['contents'])) {
            Log::warning('DownloadAttachmentJob no binary content', ['url' => $attachment->source_url]);
            return;
        }

        $mime = $binary['mime'] ?? null;
        $size = $binary['size'] ?? null;

        // Build filename
        $parsed = parse_url($attachment->source_url);
        $basename = $parsed['path'] ?? '';
        $filename = $basename ? ltrim(basename($basename), '/') : null;

        if (empty($filename)) {
            $ext = null;
            if (! empty($mime) && strpos($mime, '/') !== false) {
                $parts = explode('/', $mime);
                $ext = end($parts);
            }
            $filename = sha1($attachment->source_url) . ($ext ? ".{$ext}" : '');
        }

        $dir = "listings/{$attachment->listing_id}";
        $path = rtrim($dir, '/') . '/' . $filename;

        try {
            Storage::disk('public')->put($path, $binary['contents']);
            try {
                Storage::disk('public')->setVisibility($path, 'public');
            } catch (\Throwable $e) {
                // ignore visibility errors
            }

            $attachment->local_path = $path;
            $attachment->mime = $mime;
            $attachment->size = $size;
            $attachment->save();
        } catch (\Throwable $e) {
            Log::warning('DownloadAttachmentJob store failed', ['err' => $e->getMessage(), 'path' => $path]);
        }
    }
}
