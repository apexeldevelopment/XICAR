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
        Schema::table('booking_configurations', function (Blueprint $table) {
            $table->tinyInteger('request_show_price')->default(1)->nullable();
            $table->tinyInteger('request_distance')->default(1)->nullable();
            $table->tinyInteger('request_customer_details')->default(1)->nullable();
            $table->tinyInteger('request_payment_method')->default(1)->nullable();
            $table->tinyInteger('location_editable')->nullable()->comment('1:Enable for taxi, 2:Enable for delivery , 3: Enable for both , 4: Disable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_configurations', function (Blueprint $table) {
            //
        });
    }
};
