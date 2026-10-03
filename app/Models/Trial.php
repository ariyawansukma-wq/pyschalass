<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trial extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'athlete_id',
        'indicator_id',
        'trial_number',
        'value',
        'is_valid',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'is_valid' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class)->withTrashed();
    }
}
