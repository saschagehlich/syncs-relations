<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManufacturers extends Migration {
    public function up () {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('name');
        });
    }

    public function down () {
        Schema::drop('manufacturers');
    }
}