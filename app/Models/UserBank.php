<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBank extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'bank_code',
        'account_number',
        'account_name',
        'transfer_recipient',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
