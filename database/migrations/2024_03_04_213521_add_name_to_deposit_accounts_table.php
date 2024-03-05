<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameToDepositAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('deposit_accounts', function (Blueprint $table) {
            $table->string('name', 255)->after('plan_id');
        });
    }

    public function down()
    {
        Schema::table('deposit_accounts', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
}

