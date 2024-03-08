<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;

class WalletController extends Controller
{
    public function checkWallet(Request $request)
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
