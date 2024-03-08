<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\DepositAccount;

class Transaction extends Model
{
    use HasFactory;
    protected $table = 'transactions';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'deposit_account_id',
        'amount',
    ];
    
    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the depositAccount that owns the transaction.
     */
    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(DepositAccount::class);
    }
}
