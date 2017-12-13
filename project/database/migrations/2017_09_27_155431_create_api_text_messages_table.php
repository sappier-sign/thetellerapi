<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApiTextMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_text_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 32)->nullable();
            $table->string('bulk_id', 20)->nullable();
            $table->string('message_id')->nullable();
            $table->string('recipient', 20)->index();
            $table->integer('code')->nullable();
            $table->string('status', 100)->nullable();
            $table->string('reason')->nullable();
            $table->integer('pages')->nullable();
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
        Schema::dropIfExists('api_text_messages');
    }
}
