<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserBank;
use Illuminate\Support\Facades\Validator;


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

        $userBanks = $userBanks->paginate($count);

        return $userBanks;
    }

    /**
     * Store a new user bank data.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [ 
            'account_name' => 'required|string',
            'account_number' => 'required|string',
            'bank_name' => 'required|string',
        ]);

        if ($validator->fails()) {  
            return response()->json(["errors"=>$validator->errors()], 400);
        }

        $userBank = new userBank;
        $userBank->account_name = $request->account_name;
        $userBank->account_number = $request->account_number;
        $userBank->bank_name = $request->bank_name;
        $userBank->save();

        return $userBank;
    }
}
