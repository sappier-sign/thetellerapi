<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ttm_wallets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('merchant_id', 12)->index();
            $table->string('user_id')->index();
            $table->string('holder_name', 100);
            $table->string('account_issuer', 3);
            $table->string('account_number');
            $table->string('expiration', 5)->nullable();
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
        Schema::dropIfExists('wallets');
    }
}
