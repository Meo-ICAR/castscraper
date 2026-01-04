<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'list_url',
        'adapter_class',
        'selectors',
        'rate_limit_per_minute',
        'active',
        'last_scraped_at',
    ];

    protected $casts = [
        'selectors' => 'array',
        'active' => 'boolean',
        'last_scraped_at' => 'datetime',
    ];

    public function listings()
    {
        return $this->hasMany(Listing::class);
    }

    public function runs()
    {
        return $this->hasMany(ScrapeRun::class);
    }
}
