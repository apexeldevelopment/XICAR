<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToPriceCardValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('price_card_values', function (Blueprint $table) {
            $table->unsignedInteger('price_card_slab_id')->nullable()->after('pricing_parameter_id');
            $table->foreign('price_card_slab_id')->on("price_card_slabs")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");
            $table->tinyInteger('value_type')->nullable()->after('free_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('price_card_values', function (Blueprint $table) {
            //
        });
    }
}
