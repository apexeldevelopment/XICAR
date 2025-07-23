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
        Schema::create('default_onesignals', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('merchant_id')->nullable();
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");

            $table->string('package_name', 191)->nullable();

            $table->string('user_application_key', 191)->nullable();
            $table->string('user_rest_key', 191)->nullable();
            $table->string('user_channel_id', 191)->nullable();

            $table->string('driver_application_key', 191)->nullable();
            $table->string('driver_rest_key', 191)->nullable();
            $table->string('driver_channel_id', 191)->nullable();

            $table->string('business_segment_application_key', 191)->nullable();
            $table->string('business_segment_rest_key', 191)->nullable();
            $table->string('business_segment_channel_id', 191)->nullable();

            $table->string('web_application_key')->nullable();
            $table->string('web_rest_key')->nullable();
            $table->string('firebase_api_key_android')->nullable();
            $table->string('firebase_ios_pem_user',25)->nullable();
            $table->string('firebase_ios_pem_driver',25)->nullable();
            $table->string('pem_password_user',50)->nullable();
            $table->string('pem_password_driver',50)->nullable();
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
        Schema::dropIfExists('default_onesignals');
    }
};
