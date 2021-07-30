<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('type', 8)->default('fiat')->comment('fiat or crypto');
            $table->string('name', 100)->nullable()->default('USD');
            $table->char('symbol', 50)->default('$');
            $table->string('code', 100)->nullable()->default('101');
            $table->decimal('rate', 20,8)->default(0.00000000);
            $table->string('logo', 100)->nullable();
            $table->enum('default', ['1','0']);
            $table->enum('exchange_from', ['local','api'])->default('local');
            $table->string('allow_address_creation', 4)->default('No')->comment('For Crypto - Yes/No');//new
            $table->enum('status', ['Active','Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('currencies');
    }
}
