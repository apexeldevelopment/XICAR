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
        Schema::create('language_merchant_navigation_drawers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('merchant_id');
            $table->foreign('merchant_id','language_mvd_merchant_id_foreign')->on("merchants")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");

            $table->unsignedBigInteger('merchant_navigation_drawer_id')->nullable();
            $table->foreign('merchant_navigation_drawer_id','mvd_foreign')->on("merchant_navigation_drawers")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");

            $table->unsignedBigInteger('merchant_navigation_drawer_config_id')->nullable();
            $table->foreign('merchant_navigation_drawer_config_id','mvdc_foreign')->on("merchant_navigation_drawer_configs")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");

            $table->string('locale', 191)->index();
            $table->string('name', 200);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('language_merchant_navigation_drawers');
    }
};
