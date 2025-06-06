<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestCredential extends Model
{
    use HasFactory;

    protected $table = 'request_credentials';

    protected $fillable = [
        'credential_id',
        'request_id',
        'page',
        'credential_amount',
        'request_credential_status',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    public function credential_purpose(): HasMany
    {
        return $this->hasMany(CredentialPurpose::class, 'request_credential_id');
    }
}
