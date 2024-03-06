<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * The attributes that should have default value.
     *
     * @var array<int, string>
     */
    // protected $attributes = [
    //     "plan_id" => "1",
    // ];

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
}
