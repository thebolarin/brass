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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'bank_code' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
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

        // $response = $this->createTransferRecipient([
        //     'bank_code' => $request->bank_code,
        //     'account_number' => $request->account_number,
        //     'account_name' => $request->account_name,
        // ]);

       // if(!$response) abort(response('Unable to process user bank. Please try again', 400) );

        $userBank = UserBank::create([
            'user_id' => $user->id,
            'bank_code' => $request->bank_code,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            //'transfer_recipient' => $response->data->recipient_code
        ]);

        $wallet = Wallet::create([
            'amount' => 0.00,
            'currency_code' => 'NGN',
            'status' => 'Active',
            'user_id' =>  $user->id,
        ]);
        
        return response()->json([
            'message' => "User successfully created.",
            "user" => $user
        ],201);
    }

    protected function createTransferRecipient($userBank){
        $url = env('PAYSTACK_BASEURI') . '/transferrecipient';

        $data = [
            'bank_code' => $userBank->bank_code,
            'account_number' => $userBank->account_number,
            'name' => $userBank->account_name,
            'type' => 'nuban',
            "currency" => "NGN"
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer' . env('PAYSTACK_SECRET_KEY')
        ])->post($url, $data);

        if(!$response->successful()) return false;

        return $response->json();
    }
}
