<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 16/5/23
 * Time: 6:08 PM
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePriceCardSlabDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_card_slab_details', function(Blueprint $table)
        {
            $table->increments('id');
            $table->unsignedInteger('price_card_slab_id');
            $table->foreign('price_card_slab_id')->references('id')->on('price_card_slabs')->onDelete('CASCADE');
            $table->string('from_time');
            $table->string('to_time');
            $table->longText('week_days')->nullable();
            $table->longText('details')->nullable();
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
        Schema::drop('price_card_slab_details');
    }
}
