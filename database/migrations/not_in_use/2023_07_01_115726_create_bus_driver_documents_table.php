<?php
//
//use Illuminate\Database\Migrations\Migration;
//use Illuminate\Database\Schema\Blueprint;
//
//class CreateBusDriverDocumentsTable extends Migration {
//
//	/**
//	 * Run the migrations.
//	 *
//	 * @return void
//	 */
//	public function up()
//	{
//		Schema::create('bus_driver_documents', function(Blueprint $table)
//		{
//			$table->increments('id');
//			$table->integer('bus_driver_id')->unsigned();
//            $table->foreign('bus_driver_id')->references('id')->on('bus_drivers')->onUpdate('RESTRICT')->onDelete('CASCADE');
//
//
//			$table->integer('document_id')->unsigned();
//			$table->foreign('document_id')->references('id')->on('documents')->onUpdate('RESTRICT')->onDelete('CASCADE');
//
//			$table->string('document_file', 191)->nullable();
//			$table->date('expire_date')->nullable();
//			$table->tinyInteger('document_verification_status');
//			$table->integer('reject_reason_id')->nullable();
//			$table->tinyInteger('status')->default(1)->comment('1:Document Active, 2: Document Removed from area');
//			$table->string('document_number',191)->nullable();
//			$table->timestamps();
//		});
//	}
//
//
//	/**
//	 * Reverse the migrations.
//	 *
//	 * @return void
//	 */
//	public function down()
//	{
//		Schema::drop('driver_documents');
//	}
//
//}
