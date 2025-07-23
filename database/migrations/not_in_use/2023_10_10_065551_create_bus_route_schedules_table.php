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
        Schema::create('bus_route_schedules', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger("bus_route_id");
            $table->foreign("bus_route_id")->on("bus_routes")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->longText("available_days");
            $table->longText("timing");
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
        Schema::dropIfExists('bus_route_schedules');
    }
};
