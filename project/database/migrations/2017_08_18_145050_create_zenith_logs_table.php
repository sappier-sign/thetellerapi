<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateZenithLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs_zenith', function (Blueprint $table) {
            $table->increments('id');
            $table->string('card_number', 20)->index();
            $table->string('cvv', 4);
            $table->string('expiry_month', 2);
            $table->string('expiry_year', 2);
            $table->string('description', 100)->nullable();
            $table->string('reference_id');
            $table->string('order_id', 32)->nullable();
            $table->string('amount', 9);
            $table->string('mode', 15);
            $table->string('response_url')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('reason')->nullable();
            $table->string('response_code')->nullable();
            $table->boolean('status')->nullable();
            $table->string('ref_id', 12)->nullable();
            $table->string('auth_id')->nullable();
            $table->integer('ret_code', false)->nullable();
            $table->string('date_time')->nullable();
            $table->string('product_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('type')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('t_id')->nullable();
            $table->string('refund_date')->nullable();
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
        Schema::dropIfExists('vodafone_logs');
    }
}
