<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserFavouriteProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_favourite_product', function (Blueprint $table) {
            $table->unsignedInteger("user_id");
            $table->foreign("user_id")->on("users")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
            $table->unsignedInteger("product_variant_id")->nullable();
            $table->foreign("product_variant_id")->on("product_variants")->references("id")->onUpdate("RESTRICT")->onDelete("CASCADE");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_favourite_product');
    }
}
