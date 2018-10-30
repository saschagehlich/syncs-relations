<?php

namespace SyncsRelations\Tests;

use SyncsRelations\Tests\Models\Vehicle;
use SyncsRelations\Tests\Models\Wheel;

class HasManyTests extends TestCase
{
    public function testVehicleAttachWheels() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->fill([
            'wheels' => array_map(function ($wheel) { return $wheel->id; }, $wheels)
        ]);
        $vehicle = $vehicle->fresh();
        $this->assertCount(3, $vehicle->wheels);
    }

    public function testVehicleDetachWheels() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels->add($wheels);
        $vehicle->save();

        $vehicle->fill([
            'wheels' => [
                $wheels[1]->id
            ]
        ]);
        $vehicle = $vehicle->fresh();
        $this->assertCount(1, $vehicle->wheels);
        $this->assertEquals(2, $vehicle->wheels[0]->size);
    }

    public function testVehicleAddWheels() {
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->fill([
            'wheels' => [
                'new1' => ['size' => 1],
                'new2' => ['size' => 2],
                'new3' => ['size' => 3]
            ]
        ]);
        $vehicle = $vehicle->fresh();
        $this->assertCount(3, $vehicle->wheels);
    }

    public function testVehicleUpdateWheels() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels->add($wheels);
        $vehicle->save();

        $vehicle->fill([
            'wheels' => [
                $wheels[0]->id => [ 'size' => 4 ],
                $wheels[1]->id => [ 'size' => 5 ],
                $wheels[2]->id => [ 'size' => 6 ]
            ]
        ]);
        $vehicle = $vehicle->fresh();
        $this->assertCount(3, $vehicle->wheels);
        $this->assertEquals(4, $vehicle->wheels[0]->size);
        $this->assertEquals(5, $vehicle->wheels[1]->size);
        $this->assertEquals(6, $vehicle->wheels[2]->size);
    }

    public function testVehicleAddUpdateDetachWheels() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels->add($wheels);
        $vehicle->save();

        $vehicle->fill([
            'wheels' => [
                'new' => [ 'size' => 4 ],
                $wheels[1]->id => [ 'size' => 5 ]
            ]
        ]);
        $vehicle = $vehicle->fresh();
        $this->assertCount(2, $vehicle->wheels);

        // Note that the order is (of course) wrong, because we can't insert
        // a new instance before an existing instance
        $this->assertEquals(5, $vehicle->wheels[0]->size);
        $this->assertEquals(4, $vehicle->wheels[1]->size);
    }
}