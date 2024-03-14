<?php

namespace App\Console\Commands;

use App\Models\Plan;
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
            'from_account_id' => 'required|exists:deposit_accounts,id',
            // 'from_plan' => 'required|string|exists:plans,name',
            'to_account_id' => 'required|exists:deposit_accounts,id',
            // 'to_plan' => 'required|string|exists:plans,name',
            'amount' => 'required',
        ];
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            var_dump($validator->errors());
            return false;
        }
    
        $validatedData = $validator->getData();
        $from_account = DepositAccount::with('plan')
            // ->whereHas('plan', function ($subQuery) {
            //     $subQuery->where('name', 'BALANCE');
            // })
            ->find($validatedData["from_account_id"]);
        $to_account = DepositAccount::with('plan')
            // ->whereHas('plan', function ($subQuery) {
            //     $subQuery->where('name', 'BALANCE');
            // })
            ->find($validatedData["to_account_id"]);

        if(!$from_account || !$to_account) return false;

        $amount = $validatedData["amount"];

        if($amount <= 0) return false;

        // DB::beginTransaction();
        $uuid = Str::uuid();
        $transaction_0 = Transaction::create([
            "uuid" => $uuid,
            "user_id" => $to_account->user_id,
            "deposit_account_id" => $to_account->id,
            "amount" => $amount,
        ]);
        $uuid = Str::uuid();
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
        $systemUserId = User::userByTgid(0)->id;
        $systemPlanId = Plan::planByName("SYSTEM")->id;
        $systemAccount = DepositAccount::where([
            "user_id"=>$systemUserId,
            "plan_id"=>$systemPlanId,
            "name"=>"IN",
        ])->first();

        $from_account = $systemAccount;
        
        if(!$from_account) return "SystemAccount not registered yet";
        $validUserDepositAccounts = DepositAccount::notExpiredAccounts();
        
        $transactionResult = true;
        DB::beginTransaction();
        print("Begin\n");
        foreach($validUserDepositAccounts as $account) {
            if(!$account) {
                $transactionResult = false;
                break;
            }
            $balance = $account->getAccountBalance();
            var_dump($balance);
            $percentage = $account->getAccountPercentage();
            var_dump($percentage);
            
            $amount = $balance * $percentage / 100;
            if($amount <= 0) continue;
            $data = [
                'from_account_id' => $from_account->id,
                // 'from_plan' => "BALANCE",
                'to_account_id' => $account->id,
                // 'to_plan' => "BALANCE",
                'amount' => $amount,
            ];
            var_dump($data);
            $transactionResult = $this->transfer($data);
            if (!$transactionResult) {
                break;
            }
        };

        // Return a response with the wallet information
        if($transactionResult) {
            printf("Commit");
            DB::commit();
            return 'Percent transaction from created successfully';
        } else {
            printf("Rollback");
            DB::rollBack();
            return $balance;
        }
    }
}
