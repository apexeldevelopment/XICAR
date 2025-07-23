<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->integer('merchant_id')->unsigned();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->integer('country_area_id')->unsigned();
            $table->foreign('country_area_id')->references('id')->on('country_areas')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string('event_image');
            $table->text("event_link")->nullable();
            $table->tinyInteger('status')->default(1);
            $table->integer('sequence');
            $table->tinyInteger('delete')->nullable();
            $table->dateTime('deleted_at')->nullable();

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
        Schema::dropIfExists('events');
    }
}
