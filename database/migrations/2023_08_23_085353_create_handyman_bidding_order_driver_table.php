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
        Schema::create('handyman_bidding_order_driver', function (Blueprint $table) {
            $table->unsignedBigInteger('handyman_bidding_order_id');
            $table->foreign('handyman_bidding_order_id')->on("handyman_bidding_orders")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");

            $table->unsignedInteger('driver_id');
            $table->foreign('driver_id')->on("drivers")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");

            $table->tinyInteger("status")->default(0)->comment("0-pending, 1-accept, 2-counter, 3-reject, 4-finialize");
            $table->decimal("amount",10,2)->nullable();
            $table->string("description")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('handyman_bidding_order_driver');
    }
};
