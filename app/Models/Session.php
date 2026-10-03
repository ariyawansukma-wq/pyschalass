<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $table = 'test_sessions';

    protected $fillable = ['folder_id', 'name', 'color', 'location', 'date_time'];

    protected function casts(): array
    {
        return [
            'date_time' => 'datetime',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function trials(): HasMany
    {
        return $this->hasMany(Trial::class);
    }

    public function anthropometries(): HasMany
    {
        return $this->hasMany(Anthropometry::class);
    }

    public function getColorOrDefaultAttribute(): string
    {
        return $this->color ?? '#3B82F6';
    }
}
