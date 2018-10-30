<?php

namespace SyncsRelations\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use SyncsRelations\Eloquent\Concerns\SyncsRelations;

class Wheel extends Model {
    use SyncsRelations;
    protected $syncedRelations = ['car'];

    protected $fillable = ['size'];
    public $timestamps = false;

    public function car () {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}