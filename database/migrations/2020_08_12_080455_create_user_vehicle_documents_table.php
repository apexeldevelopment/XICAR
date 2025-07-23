<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserVehicleDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_vehicle_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_vehicle_id')->unsigned()->nullable();
            $table->foreign('user_vehicle_id')->references('id')->on('user_vehicles')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->integer('document_id');
            $table->string('document', 191)->nullable();
            $table->date('expire_date')->nullable();
            $table->tinyInteger('document_verification_status');
            $table->integer('reject_reason_id')->unsigned()->nullable();
            $table->foreign('reject_reason_id')->references('id')->on('reject_reasons')->onUpdate('RESTRICT')->onDelete('CASCADE');
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
        Schema::dropIfExists('user_vehicle_documents');
    }
}
