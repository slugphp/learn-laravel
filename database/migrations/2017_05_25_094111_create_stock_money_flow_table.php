<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockMoneyFlowTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_money_flow', function (Blueprint $table) {
            $table->string('stock_symbol')->unique();
            $table->integer('stock_r1_in');
            $table->integer('stock_r1_out');
            $table->string('stock_sina_node');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_money_flow', function (Blueprint $table) {
            //
        });
    }
}
