<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVodafonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs_vodafone', function (Blueprint $table) {
            $table->increments('id');
            $table->string('transaction_id',12)->unique()->index();
            $table->string('phone_number', 10)->index();
            $table->string('amount', 11);
            $table->string('voucher_code', 6)->nullable();
            $table->string('result_code')->nullable();
            $table->string('result_message')->nullable();
            $table->string('response_code')->nullable();
            $table->string('response_message')->nullable();
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
        Schema::dropIfExists('vodafones');
    }
}
