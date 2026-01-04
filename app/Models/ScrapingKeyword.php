<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapingKeyword extends Model
{
    protected $fillable = ['category', 'keyword', 'priority', 'technical_notes', 'active'];
}
