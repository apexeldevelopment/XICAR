<?php
//
//use Illuminate\Database\Migrations\Migration;
//use Illuminate\Database\Schema\Blueprint;
//use Illuminate\Support\Facades\Schema;
//
//class CreateRouteConfigsTable extends Migration
//{
//    /**
//     * Run the migrations.
//     *
//     * @return void
//     */
//    public function up()
//    {
//        Schema::create('route_configs', function (Blueprint $table) {
//
//            $table->increments('id');
//
//            $table->unsignedInteger("country_area_id");
//            $table->foreign("country_area_id")->on("country_areas")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
//
//            $table->unsignedInteger('bus_route_id');
//            $table->foreign("bus_route_id")->on("bus_routes")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
//
//            $table->unsignedInteger("merchant_id");
//            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
//
//            $table->unsignedInteger("service_time_slot_id")->nullable();
//            $table->foreign("service_time_slot_id")->on("service_time_slots")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
//
//            $table->tinyInteger('status');
//
//            $table->timestamps();
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
//        Schema::dropIfExists('route_config');
//    }
//}
