<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bank;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class BankController extends Controller
{
     /**
     * Display a list of Banks.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        try{  
            $this->getBank();
            $banks =  Bank::all();
            return $this->respond($banks);

        } catch(Exception $e) {
			return $this->respond($e);
		}
    }


    protected function getBank(){
        $url = env('PAYSTACK_BASEURI') . '/bank?country=NGN';

        $secret = env('PAYSTACK_SECRET_KEY');

        $response = Http::withHeaders([
            'Authorization' => "Bearer ${secret}"
        ])->get($url, []);

        if(!$response->successful()) return false;

        $result = json_decode($response);
        $banks = $result->data;
        
        foreach($banks as $bank){
            $user = Bank::updateOrCreate(
                ['name' =>  $bank->name],
                ['code' => $bank->code, 'currency' => 'NGN']
            );
        }
     }
}
