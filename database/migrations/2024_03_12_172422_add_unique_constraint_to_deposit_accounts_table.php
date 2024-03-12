<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToDepositAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('deposit_accounts', function (Blueprint $table) {
            $table->unique(['user_id', 'plan_id']);
        });
    }

    public function down()
    {
        Schema::table('deposit_accounts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'plan_id']);
        });
    }
}
