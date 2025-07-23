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
        Schema::create('language_bus_services', function (Blueprint $table) {
            $table->id();
            $table->integer('merchant_id')->unsigned()->index('merchant_id');
            $table->foreign("merchant_id")->on("merchants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->bigInteger('bus_service_id')->unsigned()->index('language_bus_service');
            $table->foreign("bus_service_id")->on("bus_services")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");

            $table->string('locale', 10)->index();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('language_bus_services');
    }
};
