<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;

class FinanceController extends Controller
{
    public function transfer(Request $request)
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
    public function finance(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'wallet' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                        'status' => 'false',
                        'error' => $validator->errors(),
                    ];
            return response()->json($response, 401);
        }
        
        $validatedData = $validator->getData();
        // Retrieve the wallet by tgid
        $wallet_count = Wallet::where([
            "wallet" => $validatedData["wallet"],
            ])->count();

        // Return a response with the wallet information
        return response()->json([
            'status' => 'true',
            'count' => $wallet_count,
        ]);
    }

    public function updateWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'user_tgid' => 'required|exists:users,tgid',
            'wallet' => 'required|unique:wallets,wallet',
        ]);
        
        if ($validator->fails()) {
            $response = [
                        'status' => 'false',
                        'error' => $validator->errors(),
                    ];
            return response()->json($response, 401);
        }
    
        $validatedData = $validator->getData();
        // Create wallet by tgid and wallet
        $wallet = Wallet::create([
            "user_id" => User::userByTgid($validatedData["user_tgid"])->id,
            "wallet" => $validatedData["wallet"],
        ]);

        // Return a response with the wallet information
        if ($wallet) {
            return response()->json([
                'status' => 'true',
                'wallet' => $wallet,
            ]);
        } else {
            return response()->json([
                'status' => 'false',
                'error' => "Wallet not created",
            ], 404);
        }
    }
}
