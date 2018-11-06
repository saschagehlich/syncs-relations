<?php

namespace SyncsRelations\Tests;

use SyncsRelations\Tests\Models\Driver;
use SyncsRelations\Tests\Models\Vehicle;

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

    public function testVehicleCreateDriverNoSave () {
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle = $vehicle->fresh();

        $vehicle->fill([
            'new_driver' => [
                'name' => 'The Sash'
            ]
        ]);
        $this->assertEquals($vehicle->driver->name, 'The Sash');

        $vehicle = $vehicle->fresh();
        $this->assertNull($vehicle->driver);
    }

    public function testVehicleAttachDriver () {
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $driver = Driver::create([
            'name' => 'The Sash'
        ]);

        $vehicle->fill([
            'driver' => $driver
        ]);
        $vehicle->save();

        $vehicle = $vehicle->fresh();

        $this->assertEquals($vehicle->driver->name, 'The Sash');
    }

    public function testVehicleAttachDriverNoSave () {
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $driver = Driver::create([
            'name' => 'The Sash'
        ]);

        $vehicle->fill([
            'driver' => $driver
        ]);
        $vehicle = $vehicle->fresh();

        $this->assertNull($vehicle->driver);
    }

    public function testVehicleDetachDriver () {
        $vehicle = Vehicle::create([
            'name' => 'Car',
            'driver' => Driver::create([
                'name' => 'The Sash'
            ])
        ]);

        $vehicle->fill([
            'driver' => null
        ]);
        $vehicle->save();
        $vehicle = $vehicle->fresh();

        $this->assertNull($vehicle->driver);
    }

    public function testVehicleDetachDriverNoSave () {
        $vehicle = Vehicle::create([
            'name' => 'Car',
            'driver' => Driver::create([
                'name' => 'The Sash'
            ])
        ]);

        $vehicle->fill([
            'driver' => null
        ]);
        $vehicle = $vehicle->fresh();

        $this->assertEquals($vehicle->driver->name, 'The Sash');
    }

    public function testVehicleUpdateDriver () {
        $vehicle = Vehicle::create([
            'name' => 'Car',
            'driver' => Driver::create([
                'name' => 'The Sash'
            ])
        ]);

        $vehicle->fill([
            'driver' => [
                'name' => 'The New Sash'
            ]
        ]);
        $vehicle->save();
        $vehicle = $vehicle->fresh();

        $this->assertEquals($vehicle->driver->name, 'The New Sash');
    }

    public function testVehicleUpdateDriverNoSave () {
        $vehicle = Vehicle::create([
            'name' => 'Car',
            'driver' => Driver::create([
                'name' => 'The Sash'
            ])
        ]);

        $vehicle->fill([
            'driver' => [
                'name' => 'The New Sash'
            ]
        ]);
        $vehicle = $vehicle->fresh();

        $this->assertEquals($vehicle->driver->name, 'The Sash');
    }
}