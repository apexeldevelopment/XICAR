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
        Schema::create('bus_pickup_drop_points', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger("merchant_id");
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->tinyInteger('sequence');
            $table->tinyInteger('status')->default(1);
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
        Schema::dropIfExists('bus_pickup_drop_points');
    }
};
