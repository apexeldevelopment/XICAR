<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserDocumentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_documents', function(Blueprint $table)
		{
            $table->increments('id');
			$table->integer('user_id');
			$table->integer('document_id');
			$table->string('document_file', 191);
			$table->date('expire_date')->nullable();
			$table->integer('document_verification_status');
			$table->integer('reject_reason_id')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1:Document Active, 2: Document Removed from country');
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
		Schema::drop('user_documents');
	}

}
