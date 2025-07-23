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
        Schema::table('price_cards', function (Blueprint $table) {
            $table->unsignedInteger('base_fare_price_card_slab_id')->nullable()->after('free_time');
            $table->foreign('base_fare_price_card_slab_id')->on("price_card_slabs")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");
            $table->string('additional_stop_charges')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('price_cards', function (Blueprint $table) {
            //
        });
    }
};
