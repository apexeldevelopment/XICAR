<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_account');
            $table->string('amount');
            $table->string('currency');
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->integer('status')->comment('1=> Pending, 2=> Success, 3=> Faild');
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
        Schema::dropIfExists('stripe_payouts');
    }
};
