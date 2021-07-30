<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCryptoapiLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('cryptoapi_logs'))
        {
            Schema::create('cryptoapi_logs', function (Blueprint $table)
            {
                $table->increments('id');
                $table->integer('payment_method_id')->unsigned()->index();
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onUpdate('cascade')->onDelete('cascade');

                $table->integer('object_id')->index()->nullable();
                $table->string('object_type', 20)->index()->nullable();
                $table->string('network', 10)->comment('Networks/Cryto Curencies - BTC,LTC,DT etc.');
                $table->text('payload')->comment('Crypto Api\'s Payloads (e.g - get_new_address(), get_balance(), withdraw(),etc.');
                $table->integer('confirmations')->index()->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('cryptoapi_logs'))
        {
            Schema::dropIfExists('cryptoapi_logs');
        }
    }
}
