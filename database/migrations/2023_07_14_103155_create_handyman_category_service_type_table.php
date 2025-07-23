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
        Schema::create('handyman_category_service_type', function (Blueprint $table) {
            $table->unsignedBigInteger('handyman_category_id');
            $table->unsignedInteger('service_type_id');

            // Define foreign key constraints
            $table->foreign('handyman_category_id')->references('id')->on('handyman_categories')->onDelete('cascade');
            $table->foreign('service_type_id')->references('id')->on('service_types')->onDelete('cascade');

            // Create a composite primary key
            $table->primary(['handyman_category_id', 'service_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('handyman_category_service_type');
    }
};
