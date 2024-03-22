<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Plan;
use App\Models\Transaction;

class DepositAccount extends Model
{
    use HasFactory;

    protected $table = 'deposit_accounts';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'name',
    ];

    /**
     * Get the user that owns the depositAccount.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the plan associated with the deposit_account.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the plan associated with the deposit_account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
    
    /**
     * Get a balance of the deposit_account which is not stacked by daily profit.
     */
    public function getAccountPureBalance()
    {
        $systemAccount = DepositAccount::where("user_id","=", 0)->where("name","=", "PERCENTAGE")->first();
        if(!$systemAccount) return 0;
        // Calc and return sum of amount
        return $this->transactions()
            ->whereRaw('NOT EXISTS (
                SELECT 1
                FROM transactions as t2
                WHERE t2.uuid = transactions.uuid
                AND t2.user_id = 0
                AND t2.deposit_account_id = '.$systemAccount->id.')'
            )->sum('amount');
    }
    
    /**
     * Get the current balance of the deposit_account.
     */
    public function getAccountBalance()
    {
        // Calc and return sum of amount
        return $this->transactions()->sum('amount');
    }
    
    public function getAccountPercentage()
    {
        // Calc and return sum of amount
        return $this->plan()->first()->percentage;
    }

    public static function notExpiredUsers()
    {
        return DepositAccount::with('user', 'plan')
            ->join('plans', 'deposit_accounts.plan_id', '=', 'plans.id')
            ->join('users', 'deposit_accounts.user_id', '=', 'users.id') // Add this join
            ->where(DB::raw('DATE_ADD(deposit_accounts.created_at, INTERVAL plans.max_days DAY)'), '>', now())
            ->where('plans.percentage', '>', 0)
            ->get(['deposit_accounts.*', 'users.*', 'plans.*']);
    }
    
    public static function notExpiredAccounts()
    {
        return DepositAccount::with('plan')
            ->join('plans', 'deposit_accounts.plan_id', '=', 'plans.id')
            ->where(DB::raw('DATE_ADD(deposit_accounts.created_at, INTERVAL plans.max_days DAY)'), '>', now())
            ->where('plans.percentage', '>', 0)
            ->get("deposit_accounts.*");
    }
}
