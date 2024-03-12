<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Validator;
use App\Models\User;
use App\Models\Transaction;
use App\Models\DepositAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Finance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:finance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Percentage deposit';

    public function __construct() {
        parent::__construct();
    }

    public function transfer($data)
    {
        $rules = [
            'from_account_id' => 'required|exists:users,tgid',
            'from_plan' => 'required|string|exists:plans,name',
            'to_account_id' => 'required|exists:users,tgid',
            'to_plan' => 'required|string|exists:plans,name',
            'amount' => 'required|numeric',
        ];
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            $response = [
                        'status' => 'false',
                        'error' => $validator->errors(),
                    ];
            return response()->json($response, 401);
        }
    
        $validatedData = $validator->getData();
        $from_account = DepositAccount::with('plan')
            ->whereHas('plan', function ($subQuery) {
                $subQuery->where('name', 'BALANCE');
            })->find($validatedData["from_account_id"]);
        $to_account = DepositAccount::with('plan')
            ->whereHas('plan', function ($subQuery) {
                $subQuery->where('name', 'BALANCE');
            })->find($validatedData["to_account_id"]);

        if(!$from_account || !$to_account) return response()->json([
            "status"=> "false",
            "message"=> "User or depositAccount not found",
        ]);

        $uuid = Str::uuid();
        $amount = $validatedData["amount"];

        // DB::beginTransaction();
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
        
        return $transaction_0 && $transaction_1;
    }
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Retrieve the wallet by tgid
        $systemUser = User::userByTgid("0");
        
        if(!$systemUser) return response()->json([
            "status"=> "false",
            "message"=> "SystemAccount not registered yet",
        ]);

        $validUserDepositAccounts = DepositAccount::notExpiredAccounts();
        
        $transactionResult = true;
        DB::beginTransaction();
        foreach($validUserDepositAccounts as $account) {
            if(!$account) {
                $transactionResult = false;
                break;
            }
            $amount = 100;
            $data = [
                'from_account_id' => $systemUser->depositAccount()->where("name", "BALANCE")->first()->id,
                'from_plan' => "BALANCE",
                'to_account_id' => $account->id,
                'to_plan' => "BALANCE",
                'amount' => $amount,
            ];
            $transactionResult = $this->transfer($data);
            
            if (!$transactionResult) {
                break;
            }
        };

        // Return a response with the wallet information
        if($transactionResult) {
            DB::commit();
            return response()->json([
                'status' => 'true',
                'message' => 'Percent transaction from created successfully',
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
