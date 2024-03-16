<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Validator;
use App\Models\User;
use App\Models\Transaction;
use App\Models\DepositAccount;
use App\Helper\Helper;
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
        print('transfering');
        $rules = [
            'from_account_id' => 'required|exists:deposit_accounts,id',
            'to_account_id' => 'required|exists:deposit_accounts,id',
            'amount' => 'required',
        ];
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            var_dump($validator->errors());
            return false;
        }
    
        $validatedData = $validator->getData();
        $from_account = DepositAccount::find($validatedData["from_account_id"]);
        $to_account = DepositAccount::find($validatedData["to_account_id"]);

        if(!$from_account || !$to_account) return false;

        $amount = $validatedData["amount"];

        if($amount <= 0) return false;

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
        Helper::send_tg_msg("You earned $amount today, congratulations!", User::find($to_account->user_id)->tgid);
        
        return $transaction_0 && $transaction_1;
    }
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Retrieve the wallet by tgid
        $systemUserId = User::userByTgid(0)->id;
        $systemPlanId = Plan::planByName("BALANCE")->id;
        $systemAccount = DepositAccount::where([
            "user_id"=>$systemUserId,
            "plan_id"=>$systemPlanId,
            "name"=>"PERCENTAGE",
        ])->first();

        $from_account = $systemAccount;
        if(!$from_account) {
            printf("SystemAccount is not registered yet");
            return "SystemAccount is not registered yet";
        }
        $validUserDepositAccounts = DepositAccount::notExpiredAccounts();
        
        $transactionResult = true;
        DB::beginTransaction();
        print("Begin\n");
        foreach($validUserDepositAccounts as $account) {
            if(!$account) {
                $transactionResult = false;
                break;
            }
            $balance = $account->getAccountPureBalance();
            $percentage = $account->getAccountPercentage();
            
            $amount = $balance * $percentage / 100;
            if($amount <= 0) continue;
            $data = [
                'from_account_id' => $from_account->id,
                'to_account_id' => $account->id,
                'amount' => $amount,
            ];
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
