<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToApplicationConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('application_configurations', function (Blueprint $table) {
            $table->integer('add_wallet_money_signup')->nullable()->after('gender')->default(2)->comment('1 : Enable,2 : Disable');
            $table->string('tip_short_amount')->nullable();
            $table->tinyInteger('instant_grocery_delivery')->nullable();
            $table->tinyInteger('default_otp')->nullable();
            $table->tinyInteger('mileage_reward')->nullable();
            $table->tinyInteger('app_auto_cashout')->nullable();
            $table->tinyInteger('show_horizontal_services')->nullable();
            $table->tinyInteger('show_single_segment_home_screen')->nullable();
            $table->string('app_loading_bar')->nullable();
            $table->tinyInteger('rate_us_user_driver')->nullable();
            $table->tinyInteger('business_name_on_signup')->nullable();
            $table->tinyInteger('cell_layout')->default(1)->nullable();
            $table->tinyInteger('time_slot_unavail_popup')->nullable();
            $table->tinyInteger('driver_cashout_dynamic')->nullable()->default(1)->comment('1 : Enable,2 : Disable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('application_configurations', function (Blueprint $table) {
            //
        });
    }
}
