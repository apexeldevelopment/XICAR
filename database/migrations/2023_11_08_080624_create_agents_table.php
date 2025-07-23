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
        Schema::create('agents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('merchant_id')->unsigned();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string('name');
            $table->string('alias_name');
            $table->string('email', 191)->unique()->index();
            $table->integer('country_id')->unsigned()->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string('password');
            $table->string('agent_image');
            $table->string('phone');
            $table->string('contact_person');
            $table->text('address');
            $table->integer('status')->default(1);
            $table->string('remember_token', 100)->nullable();
            $table->string('bank_name', 191)->nullable();
            $table->string('account_holder_name', 191)->nullable();
            $table->string('account_number', 191)->nullable();
            $table->unsignedInteger('account_type_id')->nullable()->comment('1:Saving 2:Current 3:Recurring Deposit Account 4:basic checking accounts');
            $table->foreign('account_type_id')->references('id')->on('account_types')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string('online_transaction')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('agents');
    }
};
