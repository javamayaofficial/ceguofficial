<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadClick extends Model
{
    public const UPDATED_AT = null; // hanya created_at

    protected $fillable = ['page_path', 'service', 'city', 'wa_number', 'source', 'token', 'opened_at', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'opened_at' => 'datetime',
    ];
}
