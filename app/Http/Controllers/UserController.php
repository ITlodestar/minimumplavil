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
use Illuminate\Support\Facades\DB;

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
        
        DB::beginTransaction();
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
                'name' => "",
            ]
        );

        // Create a new wallet
        $wallet = Wallet::create(
            [
                'user_id' => $user->id,
                'wallet' => $validatedData["wallet"],
            ]
        );

        // Return a response with the newly created user
        if ($depositAccount && $wallet) {
            DB::commit();
            return response()->json([
                'status' => 'true',
                'message' => 'User created successfully',
                'user' => $user,
            ], 201);
        } else {
            DB::rollBack();
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to create the user',
            ], 404);
        }
    }

    public function getUserByTgid(Request $request)
    {        
        // Retrieve the user by tgid
        $user = User::with(['depositAccount', 'wallet', 'depositAccount.transactions' => function ($query) {
            $query->selectRaw('deposit_account_id, sum(amount) as balance')
                  ->groupBy('deposit_account_id');
        }])->where("tgid", $request->user_tgid)->first();
        
        // Return a response with the user information
        if ($user && $user->depositAccount) {
            $user->depositAccount->map(function ($depositAccount) {
                if(!$depositAccount) return $depositAccount;
                $depositAccount->balance = $depositAccount->transactions->isNotEmpty()
                ? $depositAccount->transactions->first()->balance
                : 0;
                unset($depositAccount->transactions);
                unset($depositAccount->uuid);
                return $depositAccount;
            });
    
            return response()->json([
                'status' => 'true',
                'message' => 'User found',
                'user' => $user->depositAccount,
            ]);
        } else {
            return response()->json([
                'status' => 'false',
                'message' => 'User not found',
            ], 404);
        }
    }

    public function createDepositAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'user_tgid' => 'required|exists:users,tgid',
            'plan' => 'required|exists:plans,name',
        ]);
        if ($validator->fails()) { 
            $response = [
                        'status' => 'false',
                        'error' => $validator->errors(),
                    ];
            return response()->json($response, 401);   
        }
        
        $validatedData = $validator->getData();
        
        DB::beginTransaction();
        // Create a new deposit account
        try {
            $depositAccount = DepositAccount::create(
                [
                    'user_id' => User::where("tgid", $validatedData["user_tgid"])->first("id")->id,
                    'plan_id' => Plan::idByName($validatedData["plan"]),
                    'name' => "",
                ]
            );
        } catch (\Throwable $th) {
            $depositAccount = null;
        }
        
        // Return a response with the newly created user
        if ($depositAccount) {
            DB::commit();
            return response()->json([
                'status' => 'true',
                'message' => 'User created successfully',
                'depositAccount' => $depositAccount,
            ], 201);
        } else {
            DB::rollBack();
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to create the user',
            ], 404);
        }
    }

    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'user_tgid' => 'required|exists:users,tgid',
            'plan'=> 'required|exists:plans,name',
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
        $systemUserId = User::userByTgid(0)->id;
        $systemPlanId = Plan::planByName("SYSTEM")->id;
        $systemAccount = DepositAccount::where([
            "user_id"=>$systemUserId,
            "plan_id"=>$systemPlanId,
            "name"=>"IN",
        ])->first();
        $userPlanId = Plan::planByName($validatedData["plan"])->id;

        $from_account = $systemAccount;
        $to_account = DepositAccount::with('plan')
            ->where([
                "user_id"=>$user->id,
                "plan_id"=>$userPlanId,
                ])
            ->first();

        if(!$from_account || !$to_account) return response()->json([
            "status"=> "false",
            "message"=> "User or depositAccount not found",
            "error"=>$from_account,
            "data"=>[
                "user_id"=>$systemUserId,
                "plan_id"=>$systemPlanId,
                "name"=>"IN",
            ]
        ]);

        if(!$systemAccount) return response()->json([
            "status"=> "false",
            "message"=> "SystemAccount not registered yet",
        ]);

        $amount = $validatedData["amount"];
        
        DB::beginTransaction();
        $uuid = Str::uuid();
        $transaction_0 = Transaction::create([
            "uuid" => $uuid,
            "user_id" => $to_account->user_id,
            "deposit_account_id" => $to_account->id,
            "amount" => $amount,
        ]);
        $transaction_1 = Transaction::create([
            "uuid" => $uuid,
            "user_id" => $from_account->user_id,
            "deposit_account_id" => $from_account->id,
            "amount" => -$amount,
        ]);
        
        if ($transaction_0 && $transaction_1) {
            DB::commit();
            return response()->json([
                'status' => 'true',
                'message' => 'Deposit transaction of '.$amount.' from '.$from_account->id.' to '.$to_account->id.' created successfully',
            ], 201);
        } else {
            DB::rollBack();
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to create the transaction',
            ], 404);
        }
    }
    
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'from_account_id' => 'required|exists:deposit_accounts,id',
            'to_account_id' => 'required|exists:deposit_accounts,id',
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
        $from_account = DepositAccount::with('plan')
            ->find($validatedData["from_account_id"]);
        $to_account = DepositAccount::with('plan')
            ->find($validatedData["to_account_id"]);

        if(!$from_account || !$to_account) return response()->json([
            "status"=> "false",
            "message"=> "User or depositAccount not found",
        ]);

        $uuid = Str::uuid();
        $amount = $validatedData["amount"];

        DB::beginTransaction();
        $transaction_0 = Transaction::create([
            "uuid" => $uuid,
            "user_id" => $to_account->user_id,
            "deposit_account_id" => $to_account->id,
            "amount" => $amount,
        ]);
        $transaction_1 = Transaction::create([
            "uuid" => $uuid,
            "user_id" => $from_account->user_id,
            "deposit_account_id" => $from_account->id,
            "amount" => -$amount,
        ]);
        
        if ($transaction_0 && $transaction_1) {
            DB::commit();
            return response()->json([
                'status' => 'true',
                'message' => 'Transfer transaction of '.$amount.' from '.$from_account->id.' to '.$to_account->id.' created successfully',
            ], 201);
        } else {
            DB::rollBack();
            return response()->json([
                'status' => 'false',
                'message' => 'Failed to create the transaction',
            ], 404);
        }
    }
}
