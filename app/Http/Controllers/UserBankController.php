<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserBank;
use App\Models\Bank;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;


class UserBankController extends Controller
{
    /**
     * Display a list of user banks.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, UserBank $userBank)
    {
        $count = isset($request->count) && is_int($request->count) ? $request->count : 10;

        $userBanks = $userBank->newQuery();

        $userBanks->where('user_id', $request->user->id);
        $userBanks = $userBanks->paginate($count);

        return  $this->respond($userBanks);
    }

    /**
     * Store a new User Bank.
     *
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{ 
            $validator = \Validator::make($request->all(), [ 
                'bank_id' => 'required|uuid|exists:banks,id',
                'account_number' => 'required|string',
                'account_name' => 'required|string',
            ]);
    
            if ($validator->fails()) {
                return $this->respond($validator->errors(), 400, "Error");
            }
    
            $bank = Bank::findOrFail($request->bank_id);
        
            $accountDetails =  $this->getAccountName($request->account_number, $bank->code);
    
            if($accountDetails->data->account_name !== $request->account_name){
                return $this->respond("Your user account name and bank account name do not match.", 400, "Error");
            }

            $response = $this->createUserBank($request->user->id, $bank, $request);

            if(!$response) {
                return $this->respond('Unable to process user bank details. Please try again', 400, "Error");
            }
    
            return $this->respond($response, 201);

        } catch(Exception $e) {
			return $this->respond($e);
		}
        
    }

    /**
     * Display the specified User Bank.
     *
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, UserBank $userBank)
    {
        if($userBank->user_id !== $request->user->id) 
            return $this->respond("Only Owner can get user bank", 403, "Error");

        return $this->respond($userBank);
    }


    /**
     * Remove the specified User Bank.
     *
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, UserBank $userBank)
    {
        if($userBank->user_id !== $request->user->id) 
            return $this->respond("Only Owner can get user bank", 403, "Error");

        $userBank->delete();
        return $this->respond("User Bank deleted successfully");
    }

        /**
	 * Create a user bank
	 *
	 * @return void
	 */
    protected function createUserBank($user_id, $bank, $request){
        $response = $this->createTransferRecipient([
            'bank_code' => $bank->code,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
        ]);

       if(!$response) return false;

        $userBank = UserBank::create([
            'user_id' => $user_id,
            'bank_id' => $bank->id,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'transfer_recipient' => $response['data']['recipient_code']
        ]);

        return $userBank;
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

    protected function getAccountName($account_number, $bank_code){
        $url = env('PAYSTACK_BASEURI') . "/bank/resolve";

        $secret = env('PAYSTACK_SECRET_KEY');
        $data = [
            "account_number" => $account_number,
            "bank_code" => $bank_code
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer ${secret}"
        ])->get($url, $data);

        if(!$response->successful()) return false;

        return json_decode($response);
    }

}
