<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The single record holding all site-wide texts, links, menus and page-section settings (see App\Support\Site). */
class SiteSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
