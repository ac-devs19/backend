<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purpose extends Model
{
    use HasFactory;

    protected $table = 'purposes';

    protected $fillable = [
        'purpose_name',
    ];

    public function credential_purpose(): HasMany
    {
        return $this->hasMany(CredentialPurpose::class, 'purpose_id');
    }
}
