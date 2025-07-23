<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdAndBusinessSegmentIdToChatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chats', function (Blueprint $table) {
            //
            $table->unsignedInteger('user_id')->nullable()->after("id");
            $table->unsignedInteger('order_id')->nullable()->after("booking_id");
            $table->unsignedInteger('handyman_order_id')->nullable()->after("order_id");
            $table->unsignedInteger('business_segment_id')->nullable()->after("handyman_order_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chats', function (Blueprint $table) {
            //
        });
    }
}
