<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Athlete extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'athlete_number',
        'name',
        'gender',
        'date_of_birth',
        'event_number',
        'sport_branch_id',
        'photo_path',
        'height',
        'weight',
        'bmi',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'height' => 'decimal:1',
            'weight' => 'decimal:1',
            'bmi' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sportBranch(): BelongsTo
    {
        return $this->belongsTo(SportBranch::class);
    }

    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class)->withTimestamps();
    }

    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class);
    }

    public function anthropometries(): HasMany
    {
        return $this->hasMany(Anthropometry::class);
    }

    public function latestAnthropometry()
    {
        return $this->hasOne(Anthropometry::class)->latestOfMany();
    }

    public function canBeDeleted(): bool
    {
        return true;
    }
}
