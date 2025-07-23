<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateViupayPaymentInstrumentsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('viupay_payment_instruments', function(Blueprint $table)
        {
            $table->increments('id');
            $table->tinyInteger('for')->comment("1-User, 2-Driver");
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->unsignedInteger('driver_id')->nullable();
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('CASCADE');
            $table->string('slug');
            $table->string('payment_instrument_token', 191);
            $table->string('detail', 191)->nullable();
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
        Schema::drop('viupay_payment_instruments');
    }

}
