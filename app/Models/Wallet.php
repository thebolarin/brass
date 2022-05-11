<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\UsesUuid;

class Wallet extends Model
{
    use HasFactory,UsesUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'currency_code',
        'amount',
        'status',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
