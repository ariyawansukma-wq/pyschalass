<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CameraAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'athlete_id',
        'test_type',
        'category',
        'result_value',
        'result_display',
        'unit',
        'duration_sec',
        'is_estimated',
        'benchmark_snapshot',
        'achievement',
        'performed_at',
        'notes',
        'error_screenshots',
    ];

    protected function casts(): array
    {
        return [
            'result_value'       => 'decimal:4',
            'achievement'        => 'decimal:2',
            'duration_sec'       => 'integer',
            'is_estimated'       => 'boolean',
            'benchmark_snapshot' => 'array',
            'error_screenshots'  => 'array',
            'performed_at'       => 'datetime',
        ];
    }

    // ── Relasi ────────────────────────────────────────────────────────────────

    /**
     * Trainer/officer yang melakukan assessment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Atlet yang dinilai.
     */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }
}
