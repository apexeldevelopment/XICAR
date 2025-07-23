<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 16/5/23
 * Time: 6:05 PM
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePriceCardSlabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_card_slabs', function(Blueprint $table)
        {
            $types = get_price_card_slab_types(null, true);
            $table->increments('id');
            $table->string('name');
            $table->integer('merchant_id');
            $table->integer('country_area_id');
            $table->enum('type',$types)->nullable();
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
        Schema::drop('price_card_slabs');
    }
}
