<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signatory extends Model
{
    use HasFactory;

    protected $fillable = ['institution_id', 'name', 'position', 'nip'];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
