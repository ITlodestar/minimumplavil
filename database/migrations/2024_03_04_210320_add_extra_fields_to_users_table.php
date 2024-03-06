<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('tgid')->after('id'); // Assuming `tgid` should be placed right after `id`.
            $table->string('nickname')->charset('utf8')->after('email'); // Placing `nickname` after `email`.
            $table->tinyInteger('type')->after('nickname'); // Placing `type` after `nickname`.
            $table->string('country')->after('type'); // Placing `type` after `nickname`.
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tgid', 'nickname', 'type']);
        });
    }
}

