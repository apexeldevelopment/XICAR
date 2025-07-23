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
        Schema::create('handyman_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger("merchant_id");
            $table->foreign("merchant_id")->references("id")->on("merchants")->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->unsignedInteger("segment_id");
            $table->foreign("segment_id")->references("id")->on("segments")->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->text('icon')->nullable();
            $table->tinyInteger('status')->comment("1 Yes, 2 No");
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
        Schema::dropIfExists('handyman_categories');
    }
};
