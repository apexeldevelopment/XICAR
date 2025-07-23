<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserCashoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_cashouts', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('merchant_id');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string('amount')->nullable();
            $table->tinyInteger('cashout_status')->default(0)->comment('0 - Initialized, 1 - Success, 2 - Rejected');
            $table->string('action_by')->nullable();
            $table->string('comment')->nullable();
            $table->string('order_id')->nullable();
            $table->string('transaction_id')->nullable();


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
        Schema::dropIfExists('user_cashouts');
    }
}
