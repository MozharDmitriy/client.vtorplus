<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeliveryWaste extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_waste', function (Blueprint $table) {
            $table->increments('delivery_waste_id');
            $table->integer('user_id');
            $table->integer('waste_id');
            $table->float('bulk', 10, 3)->comment('объем')->default(0);
            $table->float('price', 10, 2)->comment('цена за единицу')->default(0);
            $table->float('summa', 10, 2)->comment('полная сумма')->default(0);
            $table->boolean('paid')->comment('заплатили ли за этот отход')->default(0);
            $table->dateTime('date_delivery')->comment('дата сдачи отхода');
            $table->dateTime('date_paid')->comment('дата оплаты')->nullable()->default(null);
            $table->timestamps();
            $table->index('user_id');
            $table->index('waste_id');
            $table->index('paid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_waste');
    }
}
