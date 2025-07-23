<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLanguageBusRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('language_bus_routes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bus_route_id')->unsigned()->index('language_bus_routes');
            $table->foreign("bus_route_id")->on("bus_routes")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->integer('merchant_id')->unsigned()->index('language_bus_routes_merchant');
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->string('locale', 10)->index();
            $table->string('title', 200);
            $table->softDeletes();
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
