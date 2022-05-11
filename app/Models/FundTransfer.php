<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\UsesUuid;

class FundTransfer extends Model
{
    use HasFactory,UsesUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'type',
        'payment_reference',
        'amount',
        'narration',
        'provider',
        'status'
    ];
}
