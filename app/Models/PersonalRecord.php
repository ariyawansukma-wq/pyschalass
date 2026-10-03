<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'athlete_id',
        'indicator_id',
        'folder_id',
        'best_value',
        'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'best_value' => 'decimal:2',
            'achieved_at' => 'date',
        ];
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class)->withTrashed();
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }
}
