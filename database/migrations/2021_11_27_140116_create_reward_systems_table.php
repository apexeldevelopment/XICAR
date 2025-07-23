<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRewardSystemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reward_systems', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('merchant_id');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('CASCADE');
            $table->tinyInteger('application')->comment('1:User, 2:Driver');
            $table->unsignedInteger('country_id')->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('CASCADE');
            $table->unsignedInteger('country_area_id')->nullable();
            $table->foreign('country_area_id')->references('id')->on('country_areas')->onDelete('CASCADE');
            $table->tinyInteger('rating_reward')->nullable();
            $table->integer('rating_points')->nullable();
            $table->string('rating_expire_in_days')->nullable();
            $table->tinyInteger('comment_reward')->nullable();
            $table->integer('comment_min_words')->nullable();
            $table->integer('comment_points')->nullable();
            $table->string('comment_expire_in_days')->nullable();
            $table->tinyInteger('referral_reward')->nullable();
            $table->integer('referral_points')->nullable();
            $table->string('referral_expire_in_days')->nullable();
            $table->tinyInteger('trip_expense_reward')->nullable();
            $table->integer('amount_per_points')->nullable();
            $table->integer('no_of_trips')->nullable();
            $table->integer('trips_type')->nullable();
            $table->double('expense_amount',10,2)->nullable();
            $table->int('point_against_trips')->nullable();
            $table->double('reward_value')->nullable();
            $table->int('status')->default(2);
            $table->integer('trips_type')->nullable();
            $table->integer('trips_type')->nullable();
            $table->string('expenses_expire_in_days')->nullable();
            $table->tinyInteger('online_time_reward')->nullable();
            $table->integer('points_per_hour')->nullable();
            $table->string('online_time_expire_in_days')->nullable();
            $table->tinyInteger('commission_paid_reward')->nullable();
            $table->integer('commission_amount_per_point')->nullable();
            $table->string('commission_expire_in_days')->nullable();
            $table->tinyInteger('peak_hours')->nullable();
            $table->longText('slab_data')->nullable();
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
        Schema::dropIfExists('reward_systems');
    }
}
