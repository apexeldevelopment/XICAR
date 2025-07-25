<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToCorporatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('corporates', function (Blueprint $table) {
            $table->string('wallet_balance')->default('0.0')->after('remember_token');
            $table->tinyInteger('price_type')->default(1)->nullable();
            $table->string('price_card_amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('corporates', function (Blueprint $table) {
            //
        });
    }
}
