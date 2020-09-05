<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPointIdToDeliveryWaste extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('delivery_waste', function (Blueprint $table) {
            $table->integer('point_id')->after('user_id')->comment('Ссылка на пункт приема');
            $table->index('point_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_waste', function (Blueprint $table) {
            $table->dropColumn('point_id');
        });
    }
}
