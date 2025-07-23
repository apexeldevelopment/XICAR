<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyToPriceCardDistanceSlab extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('price_cards', function (Blueprint $table) {
            //
            $table->integer('distance_slab_id')->unsigned()->nullable();
            $table->foreign('distance_slab_id')->references('id')->on('distance_slabs')->onUpdate('RESTRICT')->onDelete('CASCADE');
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
            $table->dropForeign('distance_slab_id');
        });
    }
}
