<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBankDetailColumnToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_name', 191)->nullable();
            $table->string('account_holder_name', 191)->nullable();
            $table->string('account_number', 191)->nullable();
            $table->unsignedInteger('account_type_id')->nullable();
            $table->foreign('account_type_id')->references('id')->on('account_types')->onUpdate('RESTRICT');
            $table->string('online_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
