<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submit extends Model
{
    use HasFactory;

    protected $table = 'submits';

    protected $fillable = [
        'student_id',
        'message',
        'submit_status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function record(): HasMany
    {
        return $this->hasMany(Record::class, 'submit_id');
    }

    public function document_notification(): HasMany
    {
        return $this->hasMany(DocumentNotification::class, 'submit_id');
    }

    public function pdf_record(): HasOne
    {
        return $this->hasOne(PdfRecord::class, 'submit_id');
    }
}
