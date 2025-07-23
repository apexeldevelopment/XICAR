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
        Schema::create('language_application_themes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('merchant_id')->unsigned();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->integer('application_theme_id')->unsigned();
            $table->foreign('application_theme_id')->references('id')->on('application_themes')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string('locale', 10)->index();
            $table->string('user_intro_text');
            $table->string('driver_intro_text');
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
        Schema::dropIfExists('language_application_themes');
    }
};
