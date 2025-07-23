<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToTaxiCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('taxi_companies', function (Blueprint $table) {
            $table->string('wallet_money')->nullable()->after('remember_token');
            $table->unsignedInteger('segment_id')->after('country_id')->nullable();
            $table->foreign('segment_id')->references('id')->on('segments')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('taxi_companies', function (Blueprint $table) {
            //
        });
    }
}
