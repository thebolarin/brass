<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FundTransfer;
use App\Models\Wallet;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class FundTransferController extends Controller
{
    /**
     * Display a list of transfers.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,FundTransfer $fundTransfer){
        $validator = \Validator::make([
            'status' => $request->name ?? null,
            'reference' => $request->name ?? null,
            'type' => $request->type ?? null,
            'wallet_id' => $request->type ?? null
        ], [
            'status' => 'nullable|string|in:success,failed,processing',
            'reference' => 'nullable|string|max:100',
            'type' => 'nullable|string|in:Inwards,Outwards',
            'wallet_id' => 'nullable|integer|exists:wallets,id',
        ]);
        
        if ($validator->fails()) {
            return response()
                ->json(["errors" => $validator->errors()], 400);
        }

        $count = isset($request->count) && is_int($request->count) ? $request->count : 10;
        $reference = $request->reference;
        $type = $request->type;
        $wallet_id = $request->wallet_id;

        $user = auth()->guard('user')->user();

        $fundTransfers = $fundTransfer->newQuery();

        $fundTransfers->where('user_id', $user->id);

        if (check_exists($reference)) {
            $fundTransfers->where('payment_reference', $reference);
        }

        if (check_exists($type)) {
            $fundTransfers->where('type', $type);
        }

        if (check_exists($wallet_id)) {
            $fundTransfers->where('wallet_id', $wallet_id);
        }

        $fundTransfers = $fundTransfers->latest()->paginate($count);

        return $fundTransfers;
    }

    /**
     * Get a Single Transfer
     * 
     * @return \Illuminate\Http\Response
     */
    public function show(FundTransfer $fundTransfer){

        $user = auth()->guard('user')->user();

        if($fundTransfer->user_id !== $user->id) {
            return response()->json([ 'error' => 'Only Transfer Owner can get transfer'], 400);
        }

        return $fundTransfer;
    }

    /**
     * Send funds to another wallet.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendFunds(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|between:0,9999999999999.99',
            'debit_wallet_id' => 'required|uuid|exists:wallets,id',
            'beneficiary_wallet_id' => 'required|uuid|exists:wallets,id',
        ]);

        if ($validator->fails()) {
            return response()
            ->json([ 'errors' => $validator->errors() ], 400);
        }

        $user = auth()->guard('user')->user();

        $debitWallet = Wallet::where('status', 'Active')
        ->where('id', $request->debit_wallet_id)
        ->where('user_id', $user->id)->firstOrFail();

        $balance = $debitWallet->amount;

        if($balance < $request->amount){
            return response()->json([ 'error' => 'Insufficient wallet balance'], 400);
        }

        $beneficiaryWallet = Wallet::where('status', 'Active')
        ->where('id', $request->beneficiary_wallet_id)->firstOrFail();

        
        $reference = generate_random_strings(6);

        //create transfer for sender
        FundTransfer::create([
            'user_id' => $user->id,
            'wallet_id' => $request->debit_wallet_id,
            'type' => "Outwards",
            "status" => "success",
            'amount' => $request->amount,
            'payment_reference' => $reference,
            "provider" => 'paystack',
            'narration' => "Wallet Funding"
        ]); 

        //update sender wallet
        $debitWallet->decrement('amount', $request->amount);

        //create transfer for beneficiary
        FundTransfer::create([
            'user_id' => $beneficiaryWallet->user_id,
            'type' => "Inwards",
            "status" => "success",
            'wallet_id' => $request->beneficiary_wallet_id,
            'amount' => $request->amount,
            'payment_reference' => $reference,
            "provider" => 'paystack',
            'narration' => "Wallet Funding"
        ]);

        //update beneficiary wallet
        $beneficiaryWallet->increment('amount', $request->amount);

        return response()->json([ 'message' => 'Funds Transferred Successful'], 200);
    }

     /**
     * Withdraw funds from wallet to bank account.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function withdrawFunds(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|between:0,9999999999999.99',
            'wallet_id' => 'required|uuid|exists:wallets,id'
        ]);

        if ($validator->fails()) {
            return response()
            ->json([ 'errors' => $validator->errors() ], 400);
        }
        
        $user = auth()->guard('user')->user();
        
        $wallet = Wallet::where('status', 'Active')
        ->where('id', $request->wallet_id)
        ->where('user_id', $user->id)->firstOrFail();

        $balance = $wallet->amount;

        if($balance < $request->amount){
            return response()->json([ 'error' => 'Insufficient wallet balance'], 400);
        }

        $wallet->decrement('amount', $request->amount);

        $userBank = $user->userBank;

        $response = $this->paystackTransfer($request->amount * 100, $userBank->transfer_recipient);

        if(!$response) {
            $wallet->increment('amount', $request->amount);
            return response()->json([ 'error' => 'Unsuccessful withdrawal'], 400);
        }

        $responseJson = $response->json();

        FundTransfer::create([
            'user_id' => $user->id,
            'type' => "Withdrawal",
            'wallet_id' => $request->wallet_id,
            "status" => "processing",
            'amount' => $request->amount,
            'payment_reference' => $responseJson['data']['reference'],
            'provider' => 'paystack',
            'narration' => "Wallet Withdrawal"
        ]);

        return response()->json([ 'message' => 'Fund Withdrawn Successfully'], 200);
    }

    protected function paystackTransfer($amount, $recipient){
        $url = env('PAYSTACK_BASEURI') . '/transfer';

        $data = [
            'source' => 'balance',
            'amount' => $amount,
            'recipient' => $recipient
        ];

        $secret = env('PAYSTACK_SECRET_KEY');

        $response = Http::withHeaders([
            'Authorization' => "Bearer ${secret}"
        ])->post($url, $data);

        if(!$response->successful()) return false;

        return $response;
    }

    public function paystackWebhook(Request $request){
        $secret = env('PAYSTACK_SECRET_KEY');
        $secret_hash = $request->header('x-paystack-signature');
        $hash = hash_hmac('sha512', $request, $secret);

        if($hash !== $secret_hash){
         return response()->json([ 'error' => 'Invalid Signature'], 400);
        }
        
        $requestObject = json_decode($request->getContent());
       
        $transfer = FundTransfer::where('payment_reference' , $requestObject->data->reference)
        ->where('provider', 'paystack')->firstOrFail();

       $status = 'failed';
       if($requestObject->data->status == 'success' && $requestObject->event == 'transfer.success') $status = 'success';

       $transfer->status = $requestObject->data->status;
       $transfer->save();

       return response()->json([ 'message' => 'Transfer status updated successfully'], 200);
    }
}
