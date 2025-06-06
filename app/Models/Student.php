<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    protected $fillable = [
        'user_id',
        'information_id',
        'student_number',
        'course',
        'student_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function information(): BelongsTo
    {
        return $this->belongsTo(Information::class);
    }

    public function submit(): HasOne
    {
        return $this->hasOne(Submit::class, 'student_id');
    }

    public function request(): HasOne
    {
        return $this->hasOne(Request::class, 'student_id');
    }
}
