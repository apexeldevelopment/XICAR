<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCmsPagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cms_pages', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('merchant_id');
			$table->integer('country_id')->nullable();
			$table->integer('application');
			$table->string('slug', 191);
            $table->tinyInteger('content_type')->default(1)->nullable()->comment("1-Text Content,2-URL");
            $table->integer('status')->default(1);
            $table->integer('business_segment_id')->nullable();
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
		Schema::drop('cms_pages');
	}

}
