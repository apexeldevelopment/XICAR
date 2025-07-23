<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLanguageBusStopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('language_bus_stops', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bus_stop_id')->unsigned()->index('language_bus_stops');
            $table->foreign("bus_stop_id")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->integer('merchant_id')->unsigned()->index('language_bus_stops_merchant');
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->string('locale', 10)->index();
            $table->string('name', 200);
            $table->softDeletes();
            // $table->unique(['country_id', 'locale']);

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
        Schema::dropIfExists('language_bus_stops');
    }
}
