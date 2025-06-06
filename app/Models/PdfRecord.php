<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PdfRecord extends Model
{
    use HasFactory;

    protected $table = 'dpf_records';

    protected $fillable = [
        'submit_id',
        'document_id',
        'pdf',
    ];

    public function submit(): BelongsTo
    {
        return $this->belongsTo(Submit::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
