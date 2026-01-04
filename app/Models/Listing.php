<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'external_id',
        'title',
        'description',
        'company',
        'location',
        'city',
        'region',
        'country',
        'date_posted',
        'valid_until',
        'url',
        'content_hash',
        'canonical',
        'scraped_at',
        'parsed_at',
        'raw_html',
        'extra',
    ];

    protected $casts = [
        'date_posted' => 'datetime',
        'valid_until' => 'datetime',
        'scraped_at' => 'datetime',
        'parsed_at' => 'datetime',
        'canonical' => 'boolean',
        'extra' => 'array',
    ];

    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}
