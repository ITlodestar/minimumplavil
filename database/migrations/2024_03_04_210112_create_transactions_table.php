<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->string('uuid', 100);
    $table->foreignId('user_id')->constrained('users');
    $table->foreignId('deposit_account_id')->constrained('deposit_accounts');
    $table->decimal('amount', 10, 2);
    $table->timestamps();
});
    
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
