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
        Schema::create('merchant_home_screen_holders', function (Blueprint $table) {
//            $table->increments('id');
            $table->integer('merchant_id')->unsigned()->nullable();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->integer('home_screen_holder_id')->unsigned();
            $table->foreign('home_screen_holder_id')->references('id')->on('home_screen_holders')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->tinyInteger('sequence')->nullable();
//            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_home_screen_holders');
    }
};
