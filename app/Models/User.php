<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\DepositAccount;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'password',
        'tgid',
        'nickname',
        'type',
        'remember_token',
        'country',
    ];

    /**
     * The attributes that should have default value.
     *
     * @var array<int, string>
     */
    protected $attributes = [
        "password" => "0",
        "remember_token" => "0",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the deposit_account associated with the user.
     */
    public function depositAccount(): HasMany
    {
        return $this->hasMany(DepositAccount::class);
    }
    
    /**
     * Get the deposit_account associated with the user.
     */
    public function validDepositAccount(): HasMany
    {
        return $this->hasMany(DepositAccount::class)
        ->select('deposit_accounts.*')
        // ->groupBy('transactions.deposit_account_id')
        ->join('plans', 'deposit_accounts.plan_id', '=', 'plans.id')
        // ->join('transactions', 'deposit_accounts.id', '=', 'transactions.deposit_account_id')
        ->where(DB::raw('DATE_ADD(deposit_accounts.created_at, INTERVAL plans.max_days DAY)'), '>', now());
    }
    
    /**
     * Get the deposit_account associated with the user.
     */
    public function wallet(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }
    
    /**
     * Get the deposit_account associated with the user.
     */
    public function latestWallet(): HasOne
    {
        return $this->hasOne(Wallet::class)->orderByDesc('created_at');
    }
    
    /**
     * Get the transactions associated with the user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
    
    public static function userByTgid($tgid)
    {
        return User::where("tgid", $tgid)->first();
    }
}
