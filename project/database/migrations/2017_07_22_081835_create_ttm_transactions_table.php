<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTtmTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ttm_transactions', function (Blueprint $table) {
            $table->string('fld_002')->comment('Wallet Number');
            $table->string('fld_003')->comment('Transaction Type');
            $table->string('fld_004')->comment('Transaction Amount');
            $table->string('fld_009')->comment('Device Type');
            $table->string('fld_011')->comment('System Trace Audit Number');
            $table->string('fld_012')->comment('Transaction Date');
            $table->string('fld_035')->comment('Merchant ID');
            $table->string('fld_037')->comment('Reference');
            $table->string('fld_038')->nullable()->comment('Approved Code');
            $table->string('fld_039')->nullable()->comment('Response Code');
            $table->string('fld_057')->comment('R - Switch');
            $table->string('fld_116')->comment('Description');
            $table->index(['fld_012', 'fld_037', 'fld_018']);
            $table->primary('fld_011');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ttm_transactions');
    }
}
