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
        Schema::table('bus_price_cards', function (Blueprint $table) {
            $table->tinyInteger('cancel_charges')->nullable()->default(2)->after("end_stop_fare");
            $table->integer('cancel_time')->nullable()->after("cancel_charges");
            $table->string('cancel_amount', 191)->nullable()->after("cancel_time");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bus_price_cards', function (Blueprint $table) {
            $table->dropColumn('cancel_charges');
            $table->dropColumn('cancel_time');
            $table->dropColumn('cancel_amount');
        });
    }
};
