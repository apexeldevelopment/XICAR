<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateApplicationThemesTable extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('application_themes', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('merchant_id')->unsigned()->index('application_themes_merchant_id_foreign');
			//			$table->string('primary_color_user', 191)->nullable();
			//			$table->string('primary_color_driver', 191)->nullable();
			//			$table->string('chat_button_color', 191)->nullable();
			//			$table->string('chat_button_color_driver', 191)->nullable();
			//			$table->string('share_button_color', 191)->nullable();
			//			$table->string('share_button_color_driver', 191)->nullable();
			//			$table->string('cancel_button_color', 191)->nullable();
			//			$table->string('cancel_button_color_driver', 191)->nullable();
			//			$table->string('call_button_color', 191)->nullable();
			//			$table->string('call_button_color_driver', 191)->nullable();
			//			$table->string('navigation_colour', 191)->nullable();
			//			$table->string('navigation_style', 191)->nullable();
			//			$table->string('default_config', 11)->nullable();

			// login page background image
			$table->string('login_background_image')->nullable();

			/***
			 * New Fields for driver and user App
			 * used in priview as well
			 */
			// user theme color
			$table->string('primary_color_user', 25)->nullable();
			$table->text('user_app_logo')->nullable();
			$table->text('user_intro_screen')->nullable(); // array of objects

			$table->string('primary_color_driver', 25)->nullable();
			$table->text('driver_app_logo')->nullable();
			$table->text('driver_intro_screen')->nullable(); // array of objects

			$table->string('primary_color_store', 25)->nullable();
			$table->text('store_app_logo')->nullable();

			$table->tinyInteger('font_config')->nullable();
            $table->longText('font_size')->nullable();
            $table->string('font_family')->nullable();

			// ALTER TABLE `multi-service-empty`.`application_themes` ADD COLUMN `primary_color_user` VARCHAR(25) NULL AFTER `login_background_image`, ADD COLUMN `user_app_logo` VARCHAR(191) NULL AFTER `primary_color_user`, ADD COLUMN `user_intro_screen` TEXT NULL AFTER `user_app_logo`, ADD COLUMN `primary_color_driver` VARCHAR(25) NULL AFTER `user_intro_screen`, ADD COLUMN `driver_app_logo` VARCHAR(191) NULL AFTER `primary_color_driver`, ADD COLUMN `driver_intro_screen` TEXT NULL AFTER `driver_app_logo`;

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
		Schema::drop('application_themes');
	}
}
