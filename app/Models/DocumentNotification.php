<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentNotification extends Model
{
    use HasFactory;

    protected $table = 'document_notifications';

    protected $fillable = [
        'user_id',
        'submit_id',
        'submit_status',
        'notification_status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submit(): BelongsTo
    {
        return $this->belongsTo(Submit::class);
    }
}
