<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table)
        {
            $table->increments('id');

            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');

            $table->integer('end_user_id')->unsigned()->index()->nullable();
            $table->foreign('end_user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');

            $table->integer('currency_id')->unsigned()->index()->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies')->onUpdate('cascade')->onDelete('cascade');

            $table->integer('payment_method_id')->unsigned()->index()->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onUpdate('cascade')->onDelete('cascade');

            $table->integer('merchant_id')->unsigned()->index()->nullable();
            $table->foreign('merchant_id')->references('id')->on('merchants')->onUpdate('cascade')->onDelete('cascade');

            $table->integer('bank_id')->unsigned()->index()->nullable(); //new
            $table->foreign('bank_id')->references('id')->on('banks')->onUpdate('cascade')->onDelete('cascade');

            $table->integer('file_id')->unsigned()->index()->nullable(); //new
            $table->foreign('file_id')->references('id')->on('files')->onUpdate('cascade')->onDelete('cascade');

            $table->string('uuid', 13)->nullable()->comment = "Unique ID";

            $table->string('refund_reference', 13)->nullable()->comment = "Refund Reference";

            $table->integer('transaction_reference_id')->default(0);

            $table->integer('transaction_type_id')->unsigned()->nullable();
            $table->foreign('transaction_type_id')->references('id')->on('transaction_types')->onUpdate('cascade')->onDelete('cascade');

            $table->enum('user_type', ['registered', 'unregistered'])->default('registered');

            $table->string('email', 191)->nullable();
            $table->string('phone', 20)->nullable(); //fixed - pm_v2.1

            $table->decimal('subtotal', 20, 8)->nullable()->default(0.00000000);//pm_v2.3

            $table->decimal('percentage', 20, 8)->nullable()->default(0.00000000);//pm_v2.3

            $table->decimal('charge_percentage', 20, 8)->nullable()->default(0.00000000);//pm_v2.3

            $table->decimal('charge_fixed', 20, 8)->nullable()->default(0.00000000);//pm_v2.3

            $table->decimal('total', 20, 8)->nullable()->default(0.00000000);//pm_v2.3

            $table->text('note')->nullable();

            $table->enum('status', ['Pending', 'Success', 'Refund', 'Blocked']);

            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
