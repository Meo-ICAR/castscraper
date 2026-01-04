<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'source_url',
        'local_path',
        'mime',
        'size',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
