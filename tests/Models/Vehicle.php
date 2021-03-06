<?php

namespace SyncsRelations\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use SyncsRelations\Eloquent\Concerns\SyncsRelations;

class Vehicle extends Model {
    use SyncsRelations;
    protected $syncedRelations = ['wheels', 'manufacturers', 'driver'];

    protected $fillable = ['name'];
    public $timestamps = false;

    public function driver () {
        return $this->hasOne(Driver::class);
    }

    public function wheels () {
        return $this->hasMany(Wheel::class);
    }

    public function manufacturers () {
        return $this->belongsToMany(Manufacturer::class);
    }
}