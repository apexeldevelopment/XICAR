Name<?php

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
        Schema::create('merchant_navigation_drawer_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_navigation_drawer_id');
            $table->foreign('merchant_navigation_drawer_id','mnd_foreign_key')->references('id')->on('merchant_navigation_drawers')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->tinyInteger('sequence')->default(1)->nullable();
            $table->string('name');
            $table->string('type');
            $table->text('value');
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
        Schema::dropIfExists('merchant_navigation_drawer_configs');
    }
};
