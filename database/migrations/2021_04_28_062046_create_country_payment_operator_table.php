<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryPaymentOperatorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_payment_operator', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('country_id')->unsigned()->index('country_carpooling_document_country_id_foreign');
            $table->foreign('country_id')->references('id')->on('countries')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->integer('payment_options_configuration_id')->unsigned();
            $table->foreign('payment_options_configuration_id','poc_cpo_INDEX_Foreign')->references('id')->on('payment_options_configurations')->onUpdate('RESTRICT')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('country_payment_operator');
    }
}
