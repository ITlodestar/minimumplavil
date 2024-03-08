<?php

namespace App\Http\Controllers;

use App\Models\DepositAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Plan;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'user_type' => 'required',
            'user_tgid' => 'required|unique:users,tgid',
            'user_nickname' => 'required',
            'wallet' => 'required|unique:wallets,wallet',
            'country' => 'required',
        ]);
        if ($validator->fails()) { 
            $response = [
                        'status' => 'false',
                        'error' => $validator->errors(),
                    ];
            return response()->json($response, 401);   
        }
        
        $validatedData = $validator->getData();
        
        // Create a new user
        $user = User::create([
            'type' => $validatedData['user_type'],
            'tgid' => $validatedData['user_tgid'],
            'nickname' => $validatedData['user_nickname'],
            'country' => $validatedData['country'],
        ]);

        // Create a new deposit account
        $depositAccount = DepositAccount::create(
            [
                'user_id' => $user->id,
                'plan_id' => Plan::idByName("BALANCE"),
                'name' => $validatedData["user_nickname"],
            ]
        );

        // Create a new wallet
        $depositAccount = Wallet::create(
            [
                'user_id' => $user->id,
                'wallet' => $validatedData["wallet"],
            ]
        );

        // Return a response with the newly created user
        if ($depositAccount) {
            return response()->json([
                'status' => 'true',
                'message' => 'User created successfully',
                'user' => $user,
            ], 201);
        } else {
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to create the user',
            ], 404);
        }
    }

    public function getUserByTgid(Request $request)
    {
        // Retrieve the user by tgid
        $user = User::with("depositAccount.plan")->with("wallet")->where("tgid", $request->user_tgid)->get();

        // Return a response with the user information
        if ($user) {
            return response()->json([
                'status' => 'true',
                'message' => 'User found',
                'user' => $user,
            ]);
        } else {
            return response()->json([
                'status' => 'false',
                'message' => 'User not found',
            ], 404);
        }
    }

    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'user_tgid' => 'required|exists:users,tgid',
            'amount' => 'required',
        ]);
        
        if ($validator->fails()) {
            $response = [
                        'status' => 'false',
                        'error' => $validator->errors(),
                    ];
            return response()->json($response, 401);
        }
    
        $validatedData = $validator->getData();
        $user = User::userByTgid($validatedData["user_tgid"]);
        $userDepositAccount = $user->depositAccount()->where(["plan_id"=>"2"])->first();

        if(!$user || !$userDepositAccount) return response()->json([
            "status"=> "false",
            "message"=> "User or depositAccount not found",
        ]);

        $systemUser = User::userByTgid("0");
        $systemAccount = DepositAccount::where([
            "user_id"=>$systemUser->id,
            "name"=>"OUT",
        ])->first();

        if(!$systemAccount) return response()->json([
            "status"=> "false",
            "message"=> "SystemAccount not registered yet",
        ]);

        $uuid = Str::uuid();
        $amount = $validatedData["amount"];

        $transaction_0 = Transaction::create([
            "uuid" => $uuid,
            "user_id" => $user->id,
            "deposit_account_id" => $userDepositAccount->id,
            "amount" => $amount,
        ]);
        $transaction_1 = Transaction::create([
            "uuid" => $uuid,
            "user_id" => $systemUser->id,
            "deposit_account_id" => $systemAccount->id,
            "amount" => -$amount,
        ]);

        if ($transaction_0 && $transaction_1) {
            return response()->json([
                'status' => 'true',
                'message' => 'Transaction of '.$amount.' created successfully',
            ], 201);
        } else {
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to create the transaction',
            ], 404);
        }
    }
}
