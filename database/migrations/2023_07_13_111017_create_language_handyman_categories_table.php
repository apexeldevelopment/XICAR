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
        Schema::create('language_handyman_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('handyman_category_id');
            $table->foreign('handyman_category_id')->references('id')->on('handyman_categories')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->string('locale')->index();
            $table->string('category');
            $table->text('description');
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
        Schema::dropIfExists('language_handyman_categories');
    }
};
