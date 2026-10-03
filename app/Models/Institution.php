<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'header_lines', 'phone', 'email', 'signer_name', 'signer_title', 'signature_city', 'signature_date'];

    public function signatories(): HasMany
    {
        return $this->hasMany(Signatory::class);
    }

    public function letterheadTemplates(): HasMany
    {
        return $this->hasMany(LetterheadTemplate::class);
    }

    public function logos(): HasMany
    {
        return $this->hasMany(InstitutionLogo::class)->orderBy('sort_order');
    }

    public function activeTemplate()
    {
        return $this->letterheadTemplates()->where('is_active', true)->first();
    }
}
