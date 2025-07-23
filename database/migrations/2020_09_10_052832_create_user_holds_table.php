<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserHoldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_holds', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->unsignedInteger('carpooling_ride_id');
            $table->foreign('carpooling_ride_id')->references('id')->on('carpooling_rides')->onUpdate('RESTRICT')->onDelete('CASCADE')->nullable();
            $table->string('amount')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0:pending,1:success, 2:return');
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
        Schema::dropIfExists('user_holds');
    }
}
