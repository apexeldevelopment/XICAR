<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToBookingConfigurationsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('booking_configurations', function (Blueprint $table) {
      $table->unsignedTinyInteger('ride_later_cancel_enable_in_cancel_hour')->nullable();
      $table->unsignedInteger('ride_later_cancel_charge_in_cancel_hour')->nullable();
      $table->unsignedTinyInteger('ride_later_payment_types_enable')->nullable();
      $table->string('ride_later_payment_types')->nullable();
      $table->unsignedInteger('ride_later_max_num_days')->nullable();
      $table->string('driver_cancel_after_time')->nullable();
      $table->string('android_user_key')->nullable();
      $table->string('android_driver_key')->nullable();
      $table->string('ios_user_key')->nullable();
      $table->string('ios_driver_key')->nullable();
      $table->tinyInteger('ios_map_load_from')->nullable();
      $table->tinyInteger('driver_booking_amount')->nullable();
      $table->tinyInteger('driver_arriving_reminder')->nullable();
      $table->tinyInteger('distance_from_app')->nullable();
      $table->tinyInteger('ride_later_ride_allocation')->default(1)->nullable();
      $table->tinyInteger('manual_dispatch')->default(1)->nullable();
      $table->tinyInteger('multiple_destination_price')->nullable();
      $table->tinyInteger('corporate_price_card')->nullable();
      $table->tinyInteger('handyman_booking_dispute')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('booking_configurations', function (Blueprint $table) {
      $table->dropColumn('ride_later_cancel_enable_in_cancel_hour');
      $table->dropColumn('ride_later_cancel_charge_in_cancel_hour');
      $table->dropColumn('ride_later_payment_types_enable');
      $table->dropColumn('ride_later_payment_types');
      $table->dropColumn('ride_later_max_num_days');
    });
  }
}
