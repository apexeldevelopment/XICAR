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
        Schema::create('map_markers', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->unsignedInteger('merchant_id');
            $table->foreign('merchant_id')->on("merchants")->references("id")->onUpdate('RESTRICT')->onDelete("CASCADE");
            $table->string('pickup_map_marker')->nullable();
            $table->string('drop_map_marker')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1:Active, 2:Inactive')->nullable();
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
        Schema::dropIfExists('map_markers');
    }
};
