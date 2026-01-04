<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScrapeRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'started_at',
        'finished_at',
        'status',
        'items_found',
        'items_saved',
        'errors_count',
        'meta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    public function source()
    {
        return $this->belongsTo(Source::class);
    }
}
