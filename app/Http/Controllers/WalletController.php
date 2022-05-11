<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use Illuminate\Support\Facades\Validator;

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
        $user = auth()->guard('user')->user();

        $wallets->where('user_id', $user->id);
        $wallets = $wallets->paginate($count);

        return $wallets;
    }

    public function show(Wallet $wallet){

        $user = auth()->guard('user')->user();

        if($wallet->user_id !== $user->id) 
            return response()->json([ 'error' => 'Only Wallet Owner can get wallet'], 400);

        return $wallet;
    }

    public function store(Request $request){
       
        $user = auth()->guard('user')->user();
        
        Wallet::create([
            "user_id" => $user->id,
            'currency_code' => "NGN",
            'amount' => "0.00",
            'status' => "Active"
        ]);

        return response()->json([ 'message' => 'Wallet Created Successfully'], 200);
    }

    public function fundWallet(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|between:0,9999999999999.99',
            'wallet_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors() ], 400);
        }

        $user = auth()->guard('user')->user();
        $wallet = Wallet::where('id', $request->wallet_id)->where('user_id', $user->id)->firstOrFail();

        $wallet->amount = $request->amount;
        $wallet->save();

        return response()->json([ 'message' => 'Wallet Funded Successfully'], 200);
    }

    public function updateStatus(Request $request, Wallet $wallet){
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Active,Inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors() ], 400);
        }

        $wallet->status = $request->status;
        $wallet->save();

        return response()->json([ 'message' => 'Wallet Updated Successfully'], 200);
    }
    
}
