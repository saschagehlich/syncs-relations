<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManufacturerVehicle extends Migration {
    public function up () {
        Schema::create('manufacturer_vehicle', function (Blueprint $table) {
            $table->unsignedInteger('manufacturer_id');
            $table->unsignedInteger('vehicle_id');
            $table->foreign('manufacturer_id')->references('id')->on('manufactures');
            $table->foreign('vehicle_id')->references('id')->on('vehicles');
        });
    }

    public function down () {
        Schema::drop('manufacturer_vehicle');
    }
}