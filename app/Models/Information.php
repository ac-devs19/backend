<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Information extends Model
{
    use HasFactory;

    protected $table = 'informations';

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'gender',
        'email_address',
        'contact_number',
    ];

    public function email_verification(): HasMany
    {
        return $this->hasMany(EmailVerification::class, 'information_id');
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class, 'information_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'information_id');
    }
}
