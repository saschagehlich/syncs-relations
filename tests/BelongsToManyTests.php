<?php

namespace SyncsRelations\Tests;

use Illuminate\Support\Facades\DB;
use SyncsRelations\Tests\Models\Manufacturer;
use SyncsRelations\Tests\Models\Vehicle;
use SyncsRelations\Tests\Models\Wheel;

class BelongsToManyTests extends TestCase
{
    public function testManufacturerAttachVehiclesById() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->fill([
            'vehicles' => array_pluck($vehicles, 'id')
        ]);
        $manufacturer->save();
        $manufacturer = $manufacturer->fresh();

        $this->assertCount(3, $manufacturer->vehicles);
    }

    public function testManufacturerAttachVehiclesByIdNoSave() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->fill([
            'vehicles' => array_pluck($vehicles, 'id')
        ]);
        $manufacturer = $manufacturer->fresh();

        $this->assertCount(0, $manufacturer->vehicles);
    }

    public function testManufacturerDetachVehiclesById() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->vehicles()->saveMany($vehicles);

        $this->assertCount(3, $manufacturer->vehicles);

        echo "-------------------------------------------\n";
        $manufacturer->fill([
            'vehicles' => [
                $vehicles[1]->id
            ]
        ]);
        $manufacturer->save();
        echo "-------------------------------------------\n";

        $manufacturer = $manufacturer->fresh();

        $this->assertCount(1, $manufacturer->vehicles);
        $this->assertEquals($manufacturer->vehicles[0]->name, 'Car 2');
    }

    public function testManufacturerDetachVehiclesByIdNoSave() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->vehicles()->saveMany($vehicles);

        $this->assertCount(3, $manufacturer->vehicles);

        $manufacturer->fill([
            'vehicles' => [
                $vehicles[1]->id
            ]
        ]);
        $manufacturer = $manufacturer->fresh();

        $this->assertCount(3, $manufacturer->vehicles);
    }

    public function testManufacturerAttachVehiclesByModel() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->fill([
            'vehicles' => $vehicles
        ]);
        $manufacturer->save();
        $manufacturer = $manufacturer->fresh();
        $this->assertCount(3, $manufacturer->vehicles);
    }

    public function testManufacturerAttachVehiclesByModelNoSave() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->fill([
            'vehicles' => $vehicles
        ]);
        $manufacturer = $manufacturer->fresh();
        $this->assertCount(0, $manufacturer->vehicles);
    }

    public function testManufacturerDetachVehiclesByModel() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->vehicles()->saveMany($vehicles);

        $manufacturer->fill([
            'vehicles' => [
                $vehicles[0]
            ]
        ]);
        $manufacturer->save();

        $manufacturer = $manufacturer->fresh();
        $this->assertCount(1, $manufacturer->vehicles);
    }

    public function testManufacturerDetachVehiclesByModelNoSave() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->vehicles()->saveMany($vehicles);

        $manufacturer->fill([
            'vehicles' => [
                $vehicles[0]
            ]
        ]);
        $manufacturer = $manufacturer->fresh();
        $this->assertCount(3, $manufacturer->vehicles);
    }

    public function testManufacturerCreateVehicles() {
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->fill([
            'vehicles' => [
                'new1' => ['name' => 'Car 1'],
                'new2' => ['name' => 'Car 2'],
                'new3' => ['name' => 'Car 3']
            ]
        ]);
        $manufacturer->save();
        $manufacturer = $manufacturer->fresh();
        $this->assertCount(3, $manufacturer->vehicles);
    }

    public function testManufacturerCreateVehiclesNoSave() {
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->fill([
            'vehicles' => [
                'new1' => ['name' => 'Car 1'],
                'new2' => ['name' => 'Car 2'],
                'new3' => ['name' => 'Car 3']
            ]
        ]);
        $manufacturer = $manufacturer->fresh();
        $this->assertCount(0, $manufacturer->vehicles);
    }

    public function testManufacturerUpdateVehicles() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->vehicles()->saveMany($vehicles);
        $manufacturer->save();

        $manufacturer->fill([
            'vehicles' => [
                $vehicles[0]->id => [ 'name' => 'Car 4' ],
                $vehicles[1]->id => [ 'name' => 'Car 5' ],
                $vehicles[2]->id => [ 'name' => 'Car 6' ]
            ]
        ]);
        $manufacturer->save();
        $manufacturer = $manufacturer->fresh();

        $this->assertCount(3, $manufacturer->vehicles);
        $this->assertEquals('Car 4', $manufacturer->vehicles[0]->name);
        $this->assertEquals('Car 5', $manufacturer->vehicles[1]->name);
        $this->assertEquals('Car 6', $manufacturer->vehicles[2]->name);
    }

    public function testManufacturerUpdateVehiclesNoSave() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->vehicles()->saveMany($vehicles);
        $manufacturer->save();

        $manufacturer->fill([
            'vehicles' => [
                $vehicles[0]->id => [ 'name' => 'Car 4' ],
                $vehicles[1]->id => [ 'name' => 'Car 5' ],
                $vehicles[2]->id => [ 'name' => 'Car 6' ]
            ]
        ]);
        $manufacturer = $manufacturer->fresh();

        $this->assertEquals('Car 1', $manufacturer->vehicles[0]->name);
        $this->assertEquals('Car 2', $manufacturer->vehicles[1]->name);
        $this->assertEquals('Car 3', $manufacturer->vehicles[2]->name);
    }

    public function testManufacturerAddUpdateDetachVehicles() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->vehicles()->saveMany($vehicles);
        $manufacturer->save();

        $manufacturer->fill([
            'vehicles' => [
                'new' => [ 'name' => 'Car 4' ],
                $vehicles[1]->id => [ 'name' => 'Car 5' ]
            ]
        ]);
        $manufacturer->save();
        $manufacturer = $manufacturer->fresh();
        $this->assertCount(2, $manufacturer->vehicles);

        $this->assertEquals('Car 4', $manufacturer->vehicles[0]->name);
        $this->assertEquals('Car 5', $manufacturer->vehicles[1]->name);
    }

    public function testManufacturerAddUpdateDetachVehiclesNoSave() {
        $vehicles = [
            Vehicle::create([ 'name' => 'Car 1' ]),
            Vehicle::create([ 'name' => 'Car 2' ]),
            Vehicle::create([ 'name' => 'Car 3' ])
        ];
        $manufacturer = Manufacturer::create([
            'name' => 'Sash Cars'
        ]);
        $manufacturer->vehicles()->saveMany($vehicles);
        $manufacturer->save();

        $manufacturer->fill([
            'vehicles' => [
                'new' => [ 'name' => 'Car 4' ],
                $vehicles[1]->id => [ 'name' => 'Car 5' ]
            ]
        ]);
        $manufacturer = $manufacturer->fresh();

        $this->assertCount(3, $manufacturer->vehicles);
        $this->assertEquals('Car 1', $manufacturer->vehicles[0]->name);
        $this->assertEquals('Car 2', $manufacturer->vehicles[1]->name);
        $this->assertEquals('Car 3', $manufacturer->vehicles[2]->name);
    }
}