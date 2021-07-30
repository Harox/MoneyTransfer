<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCryptoCurrenciesSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('crypto_currencies_settings'))
        {
            Schema::create('crypto_currencies_settings', function (Blueprint $table)
            {
                $table->increments('id');
                $table->integer('payment_method_id')->unsigned()->index();
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onUpdate('cascade')->onDelete('cascade');
                $table->string('network', 10)->comment('Networks/Cryto Curencies - BTC,LTC,DT etc.');
                $table->string('network_credentials', 255)->comment('Network/Cryto Curency Details');
                $table->string('status', 10)->comment('Active/Inactive');
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
        if (!Schema::hasTable('crypto_currencies_settings'))
        {
            Schema::dropIfExists('crypto_currencies_settings');
        }
    }
}
