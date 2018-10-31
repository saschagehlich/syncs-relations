<?php

namespace SyncsRelations\Tests;

use SyncsRelations\Tests\Models\Driver;
use SyncsRelations\Tests\Models\Vehicle;
use SyncsRelations\Tests\Models\Wheel;

class HasOneTests extends TestCase
{
    public function testVehicleCreateDriver () {
        $vehicle = Vehicle::create([
            'name' => 'Car',
            'new_driver' => [
                'name' => 'The Sash'
            ]
        ]);
        $vehicle = $vehicle->fresh();

        $this->assertEquals($vehicle->driver->name, 'The Sash');
    }
}