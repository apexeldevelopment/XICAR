<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserOtpChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_otp_checks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('check_for');
            $table->string('check_value');
            $table->boolean('is_register');
            $table->string('otp');
            $table->boolean('auto_fill');
            $table->tinyInteger('status')->default(0)->comment('0-pending,1-varify');
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
        Schema::dropIfExists('user_otp_checks');
    }
}
