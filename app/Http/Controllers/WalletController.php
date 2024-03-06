<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function checkWallet(Request $request)
    {
        // Retrieve the wallet by tgid
        $wallet = Wallet::where("wallet", $request->user_tgid)->get();

        // Return a response with the wallet information
        if ($wallet) {
            return response()->json([
                'status' => 'true',
                'count' => 1,
            ]);
        } else {
            return response()->json([
                'status' => 'false',
                'count' => 0,
            ], 404);
        }
    }
}
