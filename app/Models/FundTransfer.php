<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\UsesUuid;
use Cknow\Money\Money;

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

    protected $appends = ['amount_transferred'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [ 'searchtext' ];

    public function scopeSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }
        return $query->whereRaw('searchtext @@ to_tsquery(\'english\', ?)', [$search])
            ->orderByRaw('ts_rank(searchtext, to_tsquery(\'english\', ?)) DESC', [$search]);
    }
    
    public function getAmountTransferredAttribute()
    {
        if(!isset($this->attributes['amount'])){
            return "";
        }

        $amount = json_decode(json_encode(Money::NGN($this->attributes['amount'])));
        return $amount->formatted;
    }
}
