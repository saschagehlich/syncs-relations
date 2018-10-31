<?php

namespace SyncsRelations\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use SyncsRelations\Eloquent\Concerns\SyncsRelations;

class Driver extends Model {
    use SyncsRelations;
    protected $syncedRelations = ['car'];

    protected $fillable = ['name', 'vehicle_id'];
    public $timestamps = false;

    public function car () {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}