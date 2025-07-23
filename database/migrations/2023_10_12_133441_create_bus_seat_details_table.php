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
        Schema::create('bus_seat_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('bus_id');
            $table->foreign('bus_id')->on("buses")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");
            $table->enum("type",["LOWER", "UPPER"])->default("LOWER");
            $table->string("seat_no");
            $table->string("seat_position")->nullable();
            $table->string("sequence");
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
        Schema::dropIfExists('bus_seat_details');
    }
};
