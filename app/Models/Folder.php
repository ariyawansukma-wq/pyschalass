<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function athletes(): BelongsToMany
    {
        return $this->belongsToMany(Athlete::class)->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}
