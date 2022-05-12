<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserBank;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class RegisterController extends Controller
{
    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required|string',
            'bank_code' => 'required|string',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $response = $this->createUserBank($user->id,$request);

        if(!$response) {
            $user->delete();
            abort(response('Unable to process user bank details. Please try again', 400) );
        }

        $this->createWallet($user->id);

        $user->refresh();
        $user->load([ "userBank", "wallets" ]);
        
        return response()->json([
            'message' => "User successfully created.",
            "user" => $user
        ],201);
    }

    /**
	 * Create a user bank
	 *
	 * @return void
	 */
    protected function createUserBank($user_id, $request){
        $response = $this->createTransferRecipient([
            'bank_code' => $request->bank_code,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
        ]);

       if(!$response) return false;

        $userBank = UserBank::create([
            'user_id' => $user_id,
            'bank_code' => $request->bank_code,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'transfer_recipient' => $response['data']['recipient_code']
        ]);

        return $userBank;
    }

    /**
	 * Create a wallet
	 *
	 * @return void
	 */
    protected function createWallet($user_id){
        Wallet::create([
            'amount' => 0.00,
            'currency_code' => 'NGN',
            'status' => 'Active',
            'user_id' =>  $user_id,
        ]);
    }

    /**
	 * Create a transfer recipient on paystack
	 *
	 * @return void
	 */
    protected function createTransferRecipient($userBank){
        $url = env('PAYSTACK_BASEURI') . '/transferrecipient';

        $userBank = json_decode(json_encode($userBank));

        $data = [
            'bank_code' => $userBank->bank_code,
            'account_number' => $userBank->account_number,
            'name' => $userBank->account_name,
            'type' => 'nuban',
            "currency" => "NGN"
        ];

        $secret = env('PAYSTACK_SECRET_KEY');

        $response = Http::withHeaders([
            'Authorization' => "Bearer ${secret}"
        ])->post($url, $data);

        if(!$response->successful()) return false;

        return $response->json();
    }
}
