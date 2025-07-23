<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_option_translations', function (Blueprint $table) {
            $table->integer('id', true);
			$table->integer('merchant_id')->nullable();
			$table->integer('payment_option_id')->nullable();
			$table->string('name')->nullable();
			$table->string('locale')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_option_translations');
    }
};
