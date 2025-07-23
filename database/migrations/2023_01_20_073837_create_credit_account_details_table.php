<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditAccountDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_account_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger("merchant_id");
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("user_id")->nullable();
            $table->foreign("user_id")->on("users")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("driver_id")->nullable();
            $table->foreign("driver_id")->on("drivers")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("payment_option_id");
            $table->foreign("payment_option_id")->on("payment_options")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("payment_options_configuration_id");
            $table->foreign("payment_options_configuration_id")->on("payment_options_configurations")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->string("name")->nullable();
            $table->string("phone_number")->nullable();
            $table->string("card_no")->nullable();
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
        Schema::dropIfExists('credit_account_details');
    }
}
