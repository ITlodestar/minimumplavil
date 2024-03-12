<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepositAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    Schema::create('deposit_accounts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->foreignId('plan_id')->constrained('plans');
        $table->unique(['user_id', 'plan_id']);
        $table->string('name', 255); // Add this line
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
        Schema::table('deposit_accounts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'plan_id']);
            $table->dropIfExists();
        });
    }
}
