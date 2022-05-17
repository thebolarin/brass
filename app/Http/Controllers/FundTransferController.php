<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FundTransfer;
use App\Models\Wallet;
use App\Models\UserBank;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Cknow\Money\Money;

class FundTransferController extends Controller
{
    /**
     * Display a list of transfers.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,FundTransfer $fundTransfer){
        $validator = \Validator::make([
            'wallet_id' => $request->type ?? null,
            'search_term' => $request->search_term ?? null,
        ], [
            'search_term' => 'nullable|string|max:100',
            'wallet_id' => 'nullable|integer|exists:wallets,id',
        ]);
        
        if ($validator->fails()) {
            return $this->respond($validator->errors(), 400, "Error");
        }

        $count = isset($request->count) && is_int($request->count) ? $request->count : 10;
        $wallet_id = $request->wallet_id;
        $search_term = $request->search_term;

        $user = $request->user;

        $fundTransfers = $fundTransfer->newQuery();

        $fundTransfers->where('user_id', $user->id);

        if(check_exists($search_term)) { 
            $fundTransfers->search($search_term);
        }

        if (check_exists($wallet_id)) {
            $fundTransfers->where('wallet_id', $wallet_id);
        }

        $fundTransfers = $fundTransfers->latest()->paginate($count);

        return $this->respond($fundTransfers);
    }

    /**
     * Get a Single Transfer
     * 
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,FundTransfer $fundTransfer){

        $user = $request->user;

        if($fundTransfer->user_id !== $user->id) {
            return $this->respond('Only Transfer Owner can get transfer', 403, "Error");
        }

        return $this->respond($fundTransfer);
    }

    /**
     * Send funds to another wallet.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendFundsToAWallet(Request $request, Wallet $wallet){
        try{
            $validator = Validator::make($request->all(), [
                'amount' => 'required|integer',
                'beneficiary_wallet_id' => 'required|uuid|exists:wallets,id',
            ]);
    
            if ($validator->fails()) {
                return $this->respond($validator->errors(), 400, "Error");
            }
    
            $user = $request->user;
    
            $debitWallet = Wallet::where('is_active', 1)
            ->where('id', $wallet->id)
            ->where('user_id', $user->id)->firstOrFail();
    
            $walletAmount = $debitWallet->amount;
    
            if($walletAmount < $request->amount){
                return $this->respond("Insufficient wallet balance", 400, "Error");
            }
    
            $beneficiaryWallet = Wallet::where('is_active', 1)
            ->where('id', $request->beneficiary_wallet_id)->firstOrFail();
    
            $reference = generate_random_strings(6);
    
            //create transfer for sender
            FundTransfer::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
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
    
            return $this->respond("Funds Transferred Successful");

        } catch(Exception $e) {
			return $this->respond($e);
		}

       
    }

     /**
     * Withdraw funds from wallet to bank account.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function withdrawFundsToBank(Request $request,Wallet $wallet){
        try{
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|between:0,9999999999999.99',
                'user_bank_id' => 'required|uuid|exists:user_banks,id',
            ]);
    
            if ($validator->fails()) {
                return $this->respond($validator->errors(), 400, "Error");
            }
            
            $user = $request->user;
            
            $debitWallet = Wallet::where('is_active', 1)
            ->where('id', $wallet->id)
            ->where('user_id', $user->id)->firstOrFail();
    
            $walletBalance = $debitWallet->amount;
    
            if($walletBalance < $request->amount){
                return $this->respond("Insufficient wallet balance", 400, "Error");
            }
    
            $wallet->decrement('amount', $request->amount);
    
            $userBank = UserBank::findOrFail($request->user_bank_id);
    
            $response = $this->paystackTransfer($request->amount, $userBank->transfer_recipient);
    
            if(!$response) {
                $wallet->increment('amount', $request->amount);
                return $this->respond("Unsuccessful withdrawal", 400, "Error");
            }
    
            $responseJson = $response->json();
    
            FundTransfer::create([
                'user_id' => $user->id,
                'type' => "Withdrawal",
                'wallet_id' => $wallet->id,
                "status" => "processing",
                'amount' => $request->amount,
                'payment_reference' => $responseJson['data']['reference'],
                'provider' => 'paystack',
                'narration' => "Wallet Withdrawal"
            ]);
    
            return $this->respond("Fund has been transferred to your bank account successfully");
        } catch(Exception $e) {
			return $this->respond($e);
		}
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
         return $this->respond("Invalid Signature", 400, "Error");
        }
        
        $requestObject = json_decode($request->getContent());

        try{
            $transfer = FundTransfer::where('payment_reference' , $requestObject->data->reference)
            ->where('provider', 'paystack')->firstOrFail();
    
           $status = 'failed';
           if($requestObject->data->status == 'success' && $requestObject->event == 'transfer.success') $status = 'success';
    
           $transfer->status = $status;
           $transfer->save();
    
           return $this->respond("Transfer status updated successfully");
        } catch(Exception $e) {
			return $this->respond($e);
		}
       
    }
}
