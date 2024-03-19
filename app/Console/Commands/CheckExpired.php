<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Validator;
use App\Models\User;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\DepositAccount;
use App\Helper\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class CheckExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expiration of deposit accounts';

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
        
        return $transaction_0 && $transaction_1;
    }
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Retrieve the expired accounts by maxdays
        $expiredAccounts = DepositAccount::select('deposit_accounts.*')
            ->join('plans', 'deposit_accounts.plan_id', '=', 'plans.id')
            ->where(DB::raw('DATE_ADD(deposit_accounts.created_at, INTERVAL plans.max_days DAY)'), '>', now()->subDay())
            ->where(DB::raw('DATE_ADD(deposit_accounts.created_at, INTERVAL plans.max_days DAY)'), '<', now())
            ->get();
        
        $transactionResult = true;
        DB::beginTransaction();
        print("Begin\n");
        foreach($expiredAccounts as $account) {
            if(!$account) {
                $transactionResult = false;
                break;
            }

            $balance = $account->getAccountBalance();
            $amount = $balance;
            if($amount <= 0) continue;

            $balancePlanId = Plan::planByName("BALANCE")->id;
            $userBalanceAccount = DepositAccount::where([
                "user_id"=>$account->user_id,
                "plan_id"=>$balancePlanId,
            ])->first();

            if(!$userBalanceAccount) {
                print($account->user_id." balance account does not exist.");
                continue;
            }
            $data = [
                'from_account_id' => $account->id,
                'to_account_id' => $userBalanceAccount->id,
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
