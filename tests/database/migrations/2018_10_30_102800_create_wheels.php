<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWheels extends Migration {
    public function up () {
        Schema::create('wheels', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('size');
            $table->integer('vehicle_id')->unsigned()->nullable();
            $table->foreign('vehicle_id')->references('id')->on('vehicles');
        });
    }

    public function down () {
        Schema::drop('wheels');
    }
}