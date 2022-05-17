<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\UsesUuid;
use Cknow\Money\Money;

class Wallet extends Model
{
    use HasFactory,UsesUuid;

     /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'currency_code',
        'amount',
        'is_active',
    ];

    protected $appends = ['balance'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activateWallet()
    {
        $this->is_active = 1;              
        $this->save();
    }

    public function deactivateWallet()
    {
        $this->is_active = 0;             
        $this->save();
    }

    public function getBalanceAttribute()
    {
        if(!isset($this->attributes['amount'])){
            return "";
        }

        $amount = json_decode(json_encode(Money::NGN($this->attributes['amount'])));
        return $amount->formatted;
    }
}
