<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('table_bookings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger("merchant_id");
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("user_id")->nullable();
            $table->foreign("user_id")->on("users")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("segment_id")->nullable();
            $table->foreign("segment_id")->on("segments")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("business_segment_id");
            $table->foreign("business_segment_id")->on("business_segments")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("country_area_id");
            $table->foreign("country_area_id")->on("country_areas")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("service_type_id");
            $table->foreign("service_type_id")->on("service_types")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->string("no_of_tables")->nullable();
            $table->string("status")->nullable();
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
        Schema::dropIfExists('table_bookings');
    }
}
