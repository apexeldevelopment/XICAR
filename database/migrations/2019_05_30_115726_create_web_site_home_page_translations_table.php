<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWebSiteHomePageTranslationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('web_site_home_page_translations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('web_site_home_page_id');
			$table->string('locale', 191);
			$table->string('start_address_hint', 191);
			$table->string('end_address_hint', 191);
			$table->string('book_btn_title', 191);
			$table->string('estimate_btn_title', 191);
			$table->string('estimate_description', 191);
			$table->string('driver_heading', 191)->nullable();
			$table->text('driver_sub_heading')->nullable();
			$table->string('driver_buttonText', 191)->nullable();
			$table->string('footer_heading', 191)->nullable();
			$table->text('footer_sub_heading')->nullable();

			$table->string('home_page_icon_heading', 255)->nullable();
			$table->string('home_page_advert_header', 255)->nullable();
			$table->string('home_page_advert_content', 500)->nullable();


			$table->string('home_page_icon_content_1', 500)->nullable();
			$table->string('home_page_icon_content_2', 500)->nullable();
			$table->string('home_page_icon_content_3', 500)->nullable();
			$table->string('home_page_icon_content_4 ',500)->nullable();

			$table->string('android_user_link_text ',255)->nullable();
			$table->string('android_driver_link_text ',255)->nullable();
			$table->string('ios_user_link_text ',255)->nullable();
			$table->string('ios_driver_link_text ',255)->nullable();

			$table->string('additional_header_1 ',255)->nullable();
			$table->string('additional_header_content_1 ',255)->nullable();
			$table->string('additional_header_2 ',255)->nullable();
			$table->string('additional_header_content_2 ',255)->nullable();

			$table->string('login_text ',255)->nullable();
			$table->string('signup_text ',255)->nullable();
			
			$table->string('bottom_about_us_heading',255)->nullable();
			$table->string('bottom_terms_and_ser_heading',255)->nullable();
			$table->string('bottom_services_heading',255)->nullable();
			$table->string('bottom_privacy_policy_heading ',255)->nullable();
			$table->string('bottom_contact_us_heading',255)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('web_site_home_page_translations');
	}

}
