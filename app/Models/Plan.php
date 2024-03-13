<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\DepositAccount;

class Plan extends Model
{
    use HasFactory;
    protected $table = 'plans';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'percentage',
        'max_days',
    ];
    
    /**
     * Get the deposit_account associated with the user.
     */
    public function depositAccount(): HasMany
    {
        return $this->HasMany(DepositAccount::class);
    }
    
    public static function idByName($name)
    {
        return Plan::where("name", $name)->first("id")->id;
    }

    public static function planByName($name)
    {
        return Plan::where("name", $name)->first();
    }
}
