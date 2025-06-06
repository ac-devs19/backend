<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailVerification extends Model
{
    use HasFactory;

    protected $table = 'email_verifications';

    protected $fillable = [
        'information_id',
        'otp',
        'expired_at',
    ];

    public function information(): BelongsTo
    {
        return $this->belongsTo(Information::class);
    }
}
