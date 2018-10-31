<?php

namespace SyncsRelations\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use SyncsRelations\Eloquent\Concerns\SyncsRelations;

class Manufacturer extends Model {
    use SyncsRelations;
    protected $syncedRelations = ['vehicles'];

    protected $fillable = ['name'];
    public $timestamps = false;

    public function vehicles () {
        return $this->belongsToMany(Vehicle::class);
    }
}