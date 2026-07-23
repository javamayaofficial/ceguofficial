<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'province', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }
}
