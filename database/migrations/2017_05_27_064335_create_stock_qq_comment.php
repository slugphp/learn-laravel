<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockQqComment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_qq_comment', function (Blueprint $table) {
            $table->increments('stock_qc_id');
            $table->string('stock_symbol')->unique();
            $table->text('stock_comment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_qq_comment', function (Blueprint $table) {
            //
        });
    }
}
