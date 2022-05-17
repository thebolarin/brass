<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function register(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'phone' => 'required|string',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|min:8',
            ]);
    
            if ($validator->fails()) {
                return $this->respond($validator->errors(), 400, "Error");
            }
    
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);
    
            $this->createWallet($user->id);
    
            $user->refresh();
            $user->load(["wallets" ]);
            
            return $this->respond($user, 201);

		} catch(Exception $e) {
			return $this->respond($e);
		}
    }

    /**
	 * Create a wallet
	 *
	 * @return void
	 */
    protected function createWallet($user_id){
        Wallet::create([
            'amount' => 0,
            'currency_code' => 'NGN',
            'is_active' => 1,
            'user_id' =>  $user_id,
        ]);
    }

    
}
