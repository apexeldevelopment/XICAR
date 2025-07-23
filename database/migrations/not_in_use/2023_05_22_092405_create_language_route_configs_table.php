<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLanguageRouteConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('language_route_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('route_config_id')->unsigned()->index('language_route_config');
            $table->foreign("route_config_id")->on("route_configs")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->integer('merchant_id')->unsigned()->index('language_route_configs_merchant');
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->string('locale', 10)->index();
            $table->string('title', 200);
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
