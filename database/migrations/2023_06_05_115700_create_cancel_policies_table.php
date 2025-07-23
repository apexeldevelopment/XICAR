<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCancelPoliciesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cancel_policies', function(Blueprint $table)
		{
			$table->increments('id');
            $table->unsignedInteger('merchant_id')->nullable();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->unsignedInteger('country_area_id')->nullable();
            $table->foreign('country_area_id')->references('id')->on('country_areas')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->unsignedInteger('segment_id')->nullable();
            $table->foreign('segment_id')->references('id')->on('segments')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->integer('application')->comment("1-User,2-Driver");
            $table->tinyInteger('charge_type')->comment('1 fixed 2:perchantage');
            $table->tinyInteger('service_type')->comment('1 now 2:later');
            $table->tinyInteger('free_time')->comment('time in minutes');
            $table->decimal('cancellation_charges',10,2)->comment('time in minutes');
            $table->integer('status')->default(1)->comment("1-Active, 2-Inactive, 3-Expired,4-Deleted");
            $table->softDeletes();
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
		Schema::drop('cancel_policies');
	}

}
