<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCountryCarpoolingDocument extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_carpooling_document', function (Blueprint $table) {
            $table->integer('country_id')->unsigned()->index('country_carpooling_document_country_id_foreign');
            $table->foreign('country_id')->references('id')->on('countries')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->integer('document_id')->unsigned();
            $table->foreign('document_id')->references('id')->on('documents')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->tinyInteger('check_user')->default(2)->comment('1:checked,2:unchecked ');
            $table->tinyInteger('check_offer_user')->default(2)->comment('1:checked,2:unchecked ');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('country_carpooling_document', function (Blueprint $table) {
            //
        });
    }
}
