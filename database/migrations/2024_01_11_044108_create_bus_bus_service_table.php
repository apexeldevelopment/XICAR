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
        Schema::create('bus_bus_service', function (Blueprint $table) {
            $table->integer('bus_id')->unsigned();
            $table->foreign("bus_id")->on("buses")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->bigInteger('bus_service_id')->unsigned()->index('language_bus_service');
            $table->foreign("bus_service_id")->on("bus_services")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_bus_service');
    }
};
