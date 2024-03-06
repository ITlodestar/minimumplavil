<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'user_type' => 'required',
            'user_tgid' => 'required|unique:users,tgid',
            'user_nickname' => 'required',
            'name' => 'required',
            'email' => 'required',
        ]);
        // dd($validatedData['user_type']);exit;

        // Create a new user
        $user = User::create([
            'type' => $validatedData['user_type'],
            'tgid' => $validatedData['user_tgid'],
            'nickname' => $validatedData['user_nickname'],
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
        ]);

        // Create a new deposit account
        $depositAccount = $user->depositAccount()->updateOrCreate(
            [
                'user_id' => $user->id,
                'plan_id' => "2",
            ],
            ['name' => $validatedData["name"]]
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

    public function update(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'tgid' => 'required',
            'type' => 'required',
            'new_data' => 'required',
        ]);
        // dd($validatedData['user_type']);exit;

        if ($validatedData["type"] == "wallet") {
            // Create a new user
            $user = User::where([
                'tgid' => $validatedData['tgid'],
            ])->with("wallet")->first();
    
            if(!$user) return response()->json([
                "status"=> "false",
                "message"=> "User not found",
            ]);
    
            // ->firstOrCreate(["wallet"], [$validatedData["new_data"]]);
    
            // Return a response with the newly created user
            if ($user->wallet) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Wallet already created',
                    'user' => $user,
                ], 201);
            } else {
                $wallet = Wallet::create([
                    "user_id" => $user->id,
                    "wallet" => $validatedData["new_data"]
                ]);
                if ($wallet) {
                    return response()->json([
                        'status' => 'true',
                        'message' => 'Wallet created successfully',
                    ], 404);
                } else {
                    return response()->json([
                        'status' => 'false',
                        'message' => 'Failed to create the wallet',
                    ], 404);
                }
            }
        } else if ($validatedData["type"] == "add_balance") {
            // Create a new user
            $user = User::where([
                'tgid' => $validatedData['tgid'],
            ])->with("depositAccount")->first();
    
            if(!$user || !$user->depositAccount) return response()->json([
                "status"=> "false",
                "message"=> "User or depositAccount not found",
            ]);
    
            // ->firstOrCreate(["wallet"], [$validatedData["new_data"]]);
    
            // Return a response with the newly created user
            $transaction_up = $user->depositAccount->transactions()->create([
                // "uuid" => "01",
                "user_id" => $user->id,
                "deposit_account_id" => $user->depositAccount->id,
                "amount" => -$validatedData["new_data"],
            ]);
            $transaction_up = $user->depositAccount->transactions()->create([
                // "uuid" => "01",
                "user_id" => $user->id,
                "deposit_account_id" => $user->depositAccount->id,
                "amount" => $validatedData["new_data"],
            ]);

            if ($transaction_up) {
                return response()->json([
                    'status' => 'true',
                    'message' => 'Transaction created successfully',
                ], 404);
            } else {
                return response()->json([
                    'status' => 'false',
                    'message' => 'Failed to create the transaction',
                ], 404);
            }
        }
    }
}
