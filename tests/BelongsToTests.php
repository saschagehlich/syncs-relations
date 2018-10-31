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

    // Since a previous version of this library caused relations of unsaved entities
    // to be saved, I'd like to make sure this is not happening anymore.
    public function testWheelCreateVehicleNoSave () {
        $wheel = new Wheel([ 'size' => 1, 'new_car' => [ 'name' => 'Car' ] ]);

        $this->assertInstanceOf(Vehicle::class, $wheel->car);
        $this->assertFalse($wheel->exists());
        $this->assertFalse($wheel->car->exists());
    }

    public function testWheelAssignVehicleByModel () {
        $vehicle = Vehicle::create([ 'name' => 'Car' ]);
        /** @var Wheel $wheel */
        $wheel = Wheel::create([ 'size' => 1, 'car' => $vehicle ]);
        $wheel = $wheel->fresh();

        $this->assertEquals($wheel->car->id, $vehicle->id);
        $this->assertEquals($wheel->car->name, 'Car');
    }

    public function testWheelAssignVehicleByModelNoSave () {
        $vehicle = new Vehicle([ 'name' => 'Car' ]);
        $wheel = new Wheel([ 'size' => 1, 'car' => $vehicle ]);

        $this->assertInstanceOf(Vehicle::class, $wheel->car);
        $this->assertFalse($wheel->car->exists());
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

    public function testWheelDeleteNoSave () {
        $vehicle = Vehicle::create([ 'name' => 'Car' ]);
        $wheel = Wheel::create([ 'size' => 1, 'vehicle_id' => $vehicle->id ]);

        $wheel->fill([ 'delete_car' => true ]);

        $this->assertNull($wheel->car);

        $wheel = $wheel->fresh();
        $this->assertNotNull($wheel->car);
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

    public function testWheelUpdateVehicleNoSave () {
        $vehicle = Vehicle::create([ 'name' => 'Car' ]);
        $wheel = Wheel::create([ 'size' => 1, 'vehicle_id' => $vehicle->id ]);
        $wheel = $wheel->fresh();

        $wheel->fill([ 'car' => [ 'name' => 'New Car' ] ]);
        $wheel = $wheel->fresh();

        $this->assertEquals($wheel->car->name, 'Car');
    }
}