<?php

namespace SyncsRelations\Tests;

use SyncsRelations\Tests\Models\Vehicle;
use SyncsRelations\Tests\Models\Wheel;

class BelongsToTests extends TestCase
{
    public function testWheelCreateVehicle () {
        $wheel = Wheel::create([ 'size' => 1, 'new_car' => [ 'name' => 'Car' ] ]);
        $wheel = $wheel->fresh();

        $this->assertEquals($wheel->car->name, 'Car');
    }

    public function testWheelAssignVehicleByModel () {
        $vehicle = Vehicle::create([ 'name' => 'Car' ]);
        $wheel = Wheel::create([ 'size' => 1, 'car' => $vehicle ]);
        $wheel = $wheel->fresh();

        $this->assertEquals($wheel->car->id, $vehicle->id);
        $this->assertEquals($wheel->car->name, 'Car');
    }

    public function testWheelDeleteVehicle () {
        $vehicle = Vehicle::create([ 'name' => 'Car' ]);
        $wheel = Wheel::create([ 'size' => 1, 'vehicle_id' => $vehicle->id ]);
        $wheel = $wheel->fresh();

        $wheel->fill([ 'delete_car' => true ]);
        $wheel->save();
        $wheel = $wheel->fresh();

        $this->assertEquals($wheel->vehicle_id, null);
        $this->assertEquals($wheel->car, null);
    }

    public function testWheelUpdateVehicle () {
        $vehicle = Vehicle::create([ 'name' => 'Car' ]);
        $wheel = Wheel::create([ 'size' => 1, 'vehicle_id' => $vehicle->id ]);
        $wheel = $wheel->fresh();

        $wheel->fill([ 'car' => [ 'name' => 'New Car' ] ]);
        $wheel->save();
        $wheel = $wheel->fresh();

        $this->assertEquals($wheel->car->id, $vehicle->id);
        $this->assertEquals($wheel->car->name, 'New Car');
    }
}