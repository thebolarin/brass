<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use Illuminate\Support\Facades\Validator;
use Cknow\Money\Money;

class WalletController extends Controller
{
    /**
     * Display a list of wallets.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Wallet $wallet)
    {
        $count = isset($request->count) && is_int($request->count) ? $request->count : 10;

        $wallets = $wallet->newQuery();
        $user = $request->user;

        $wallets->where('user_id', $user->id);
        $wallets = $wallets->paginate($count);

        return  $this->respond($wallets);
    }

    /**
     * Get a single wallet.
     * 
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Wallet $wallet){

        $user = $request->user;

        if($wallet->user_id !== $user->id) 
            return $this->respond("Only Wallet Owner can get wallet", 403, "Error");

        return  $this->respond($wallet);
    }

    /**
     * Create a new wallet.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
       try{
        $user = $request->user;
        
        Wallet::create([
            "user_id" => $user->id,
            'currency_code' => "NGN",
            'amount' => 0,
            'is_active' => 1
        ]);

        return  $this->respond("Wallet Created Successfully");

    } catch(Exception $e) {
        return $this->respond($e);
    }
        
    }

    /**
     * Fund a wallet.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fundWallet(Request $request, Wallet $wallet){
        try{
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|between:0,9999999999999.99',
            ]);
    
            if ($validator->fails()) {
                return $this->respond($validator->errors(), 400, "Error");
            }
            
            $wallet = Wallet::where('id', $wallet->id)
            ->where('user_id', $request->user->id)
            ->where('is_active', 1)->firstOrFail();
            
            $amount = $request->amount * 100;
            $wallet->increment('amount', $amount);
    
            return $this->respond('Wallet Funded Successfully');

        } catch(Exception $e) {
			return $this->respond($e);
		}
    }

    /**
     * Deactivate Wallet
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deactivateWallet(Wallet $wallet)
    {
        $wallet->deactivateWallet();
        return $this->respond('Wallet deactivated successfully');
    }

   /**
    * Activate Wallet
    * 
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
   public function activateWallet(Wallet $wallet)
   {
       $wallet->activateWallet();
       return $this->respond('Wallet activated successfully');
   }
}
