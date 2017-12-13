<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogsMtnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs_mtn', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('name')->nullable();
            $table->string('info')->nullable();
            $table->string('mobile')->nullable();
            $table->string('thirdpartyID')->nullable();
            $table->string('billprompt')->nullable();
            $table->string('mesg')->nullable();
            $table->string('expiry')->nullable();
            $table->string('invoiceNo')->nullable();
            $table->string('responseCode')->nullable();
            $table->string('responseMessage')->nullable();
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
        Schema::dropIfExists('logs_mtn');
    }
}
