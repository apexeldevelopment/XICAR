<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverRewardPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_reward_points', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('merchant_id');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('CASCADE');
            $table->unsignedInteger('driver_id');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('CASCADE');
            $table->string('reward_points');
            $table->string('used_reward_point')->nullable();
            $table->string('remain_reward_point')->nullable();
            $table->string('calling_from')->nullable();
            $table->string('expire_date')->nullable();
            $table->tinyInteger('status')->comment('1:Active, 2:Half Used, 3:Full Used, 4:Expire')->nullable();
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
        Schema::dropIfExists('driver_reward_points');
    }
}
