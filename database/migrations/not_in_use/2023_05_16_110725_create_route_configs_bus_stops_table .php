<?php
//
//use Illuminate\Database\Migrations\Migration;
//use Illuminate\Database\Schema\Blueprint;
//use Illuminate\Support\Facades\Schema;
//
//class CreateRouteConfigsBusStopsTable extends Migration
//{
//    /**
//     * Run the migrations.
//     *
//     * @return void
//     */
//    public function up()
//    {
//        Schema::create('route_configs_bus_stops', function (Blueprint $table) {
//            $table->unsignedInteger("route_config_id");
//            $table->foreign("route_config_id")->on("route_configs")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
//
//            $table->unsignedInteger("bus_stop_id");
//            $table->foreign("bus_stop_id")->on("bus_stops")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
//
//            $table->integer("time",10);
//
//            $table->integer('sequence')->default(1);
//
//        });
//    }
//
//    /**
//     * Reverse the migrations.
//     *
//     * @return void
//     */
//    public function down()
//    {
//        Schema::dropIfExists('bus_routes_bus_stops');
//    }
//}
