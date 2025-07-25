<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBusDocumentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('bus_documents', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('bus_id')->unsigned();
            $table->foreign('bus_id')->references('id')->on('buses')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->integer('document_id')->unsigned();
			$table->foreign('document_id')->references('id')->on('documents')->onUpdate('RESTRICT')->onDelete('CASCADE');

			$table->string('document', 191)->nullable();
			$table->date('expire_date')->nullable();
			$table->tinyInteger('document_verification_status');
			$table->integer('reject_reason_id')->nullable();
			$table->tinyInteger('status')->default(1)->comment('1:Document Active, 2: Document Removed from area');
			$table->string('document_number',191)->nullable();
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
		Schema::drop('driver_documents');
	}

}
