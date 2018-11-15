<?php

namespace SyncsRelations\Tests;

use Illuminate\Support\Facades\DB;
use SyncsRelations\Tests\Models\Vehicle;
use SyncsRelations\Tests\Models\Wheel;

class HasManyTests extends TestCase
{
    public function testVehicleAttachWheelsById() {
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
        $vehicle->save();

        $vehicle = $vehicle->fresh();
        $this->assertCount(3, $vehicle->wheels);
    }

    public function testVehicleAttachWheelsByIdNoSave() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);

        $vehicle->fill([
            'wheels' => array_pluck($wheels, 'id')
        ]);

        $this->assertInstanceOf(Wheel::class, $vehicle->wheels[0]);
        $this->assertCount(3, $vehicle->wheels);
        $vehicle = $vehicle->fresh();
        $this->assertCount(0, $vehicle->wheels);
    }

    public function testVehicleDetachWheelsById() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels()->saveMany($wheels);
        $vehicle->save();

        $vehicle->fill([
            'wheels' => [
                $wheels[1]->id
            ]
        ]);
        $vehicle->save();

        $vehicle = $vehicle->fresh();
        $this->assertCount(1, $vehicle->wheels);
        $this->assertEquals(2, $vehicle->wheels[0]->size);
    }

    public function testVehicleDetachWheelsByIdSeparateObject() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels()->saveMany($wheels);
        $vehicle->save();

        $vehicle->fill([
            'delete_wheels' => [
                $wheels[1]->id
            ]
        ]);
        $vehicle->save();

        $vehicle = $vehicle->fresh();
        $this->assertCount(2, $vehicle->wheels);
        $this->assertEquals(1, $vehicle->wheels[0]->size);
        $this->assertEquals(3, $vehicle->wheels[1]->size);
    }

    public function testVehicleDetachWheelsByIdNoSave() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car',
            'wheels' => $wheels
        ]);

        $vehicle->save();
        $vehicle = $vehicle->fresh();

        $vehicle->fill([
            'wheels' => [
                $wheels[1]->id
            ]
        ]);
        $this->assertCount(1, $vehicle->wheels);
        $vehicle = $vehicle->fresh();
        $this->assertCount(3, $vehicle->wheels);
    }

    public function testVehicleAttachWheelsByModel() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car',
            'wheels' => $wheels
        ]);
        $vehicle = $vehicle->fresh();
        $this->assertCount(3, $vehicle->wheels);
    }

    public function testVehicleAttachWheelsByModelNoSave() {
        $wheels = [
            new Wheel([ 'size' => 1 ]),
            new Wheel([ 'size' => 2 ]),
            new Wheel([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->fill([
            'wheels' => $wheels
        ]);

        $this->assertCount(3, $vehicle->wheels);
        $vehicle = $vehicle->fresh();
        $this->assertCount(0, $vehicle->wheels);
    }

    public function testVehicleDetachWheelsByModel() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car',
            'wheels' => $wheels
        ]);

        $vehicle->fill([
            'wheels' => [
                $wheels[1]
            ]
        ]);
        $vehicle->save();
        $vehicle = $vehicle->fresh();

        $this->assertCount(1, $vehicle->wheels);
    }

    public function testVehicleDetachWheelsByModelNoSave() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car',
            'wheels' => $wheels
        ]);

        $vehicle->fill([
            'wheels' => [
                $wheels[1]
            ]
        ]);
        $this->assertCount(1, $vehicle->wheels);
        $vehicle = $vehicle->fresh();
        $this->assertCount(3, $vehicle->wheels);
    }

    public function testVehicleCreateWheels() {
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
        $vehicle->save();
        $vehicle = $vehicle->fresh();

        $this->assertCount(3, $vehicle->wheels);
        $this->assertEquals($vehicle->wheels[0]->size, 1);
        $this->assertEquals($vehicle->wheels[1]->size, 2);
        $this->assertEquals($vehicle->wheels[2]->size, 3);
    }

    public function testVehicleCreateWheelsNoSave() {
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
        $this->assertCount(3, $vehicle->wheels);
        $this->assertEquals($vehicle->wheels[0]->size, 1);
        $this->assertEquals($vehicle->wheels[1]->size, 2);
        $this->assertEquals($vehicle->wheels[2]->size, 3);

        $vehicle = $vehicle->fresh();
        $this->assertCount(0, $vehicle->wheels);
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
        $vehicle->wheels()->saveMany($wheels);
        $vehicle->save();

        $vehicle->fill([
            'wheels' => [
                $wheels[0]->id => [ 'size' => 4 ],
                $wheels[1]->id => [ 'size' => 5 ],
                $wheels[2]->id => [ 'size' => 6 ]
            ]
        ]);

        $vehicle->save();
        $vehicle = $vehicle->fresh();

        $this->assertCount(3, $vehicle->wheels);
        $this->assertEquals(4, $vehicle->wheels[0]->size);
        $this->assertEquals(5, $vehicle->wheels[1]->size);
        $this->assertEquals(6, $vehicle->wheels[2]->size);
    }

    public function testVehicleUpdateWheelsNoSave() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels()->saveMany($wheels);

        $vehicle->fill([
            'wheels' => [
                $wheels[0]->id => [ 'size' => 4 ],
                $wheels[1]->id => [ 'size' => 5 ],
                $wheels[2]->id => [ 'size' => 6 ]
            ]
        ]);

        $this->assertCount(3, $vehicle->wheels);
        $this->assertEquals(4, $vehicle->wheels[0]->size);
        $this->assertEquals(5, $vehicle->wheels[1]->size);
        $this->assertEquals(6, $vehicle->wheels[2]->size);

        $vehicle = $vehicle->fresh();

        $this->assertCount(3, $vehicle->wheels);
        $this->assertEquals(1, $vehicle->wheels[0]->size);
        $this->assertEquals(2, $vehicle->wheels[1]->size);
        $this->assertEquals(3, $vehicle->wheels[2]->size);
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
        $vehicle->wheels()->saveMany($wheels);
        $vehicle->save();

        $vehicle->fill([
            'wheels' => [
                'new' => [ 'size' => 4 ],
                $wheels[1]->id => [ 'size' => 5 ]
            ]
        ]);
        $vehicle->save();

        $vehicle = $vehicle->fresh();
        $this->assertCount(2, $vehicle->wheels);

        // Note that the order is (of course) wrong, because we can't insert
        // a new instance before an existing instance
        $this->assertEquals(5, $vehicle->wheels[0]->size);
        $this->assertEquals(4, $vehicle->wheels[1]->size);
    }

    public function testVehicleAddUpdateDetachWheelsNoSave() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels()->saveMany($wheels);
        $vehicle->save();

        $vehicle->fill([
            'wheels' => [
                'new' => [ 'size' => 4 ],
                $wheels[1]->id => [ 'size' => 5 ]
            ]
        ]);
        $vehicle = $vehicle->fresh();

        $this->assertCount(3, $vehicle->wheels);

        $this->assertEquals($vehicle->wheels[0]->size, 1);
        $this->assertEquals($vehicle->wheels[1]->size, 2);
        $this->assertEquals($vehicle->wheels[2]->size, 3);
    }

    public function testHasManyDirtinessAttributeChangePositive() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels()->saveMany($wheels);
        $vehicle->save();

        $vehicle->fill([
            'wheels' => [
                $wheels[0]->id => [ 'size' => 3 ],
                $wheels[1]->id => [ 'size' => 2 ],
                $wheels[2]->id => [ 'size' => 3 ]
            ]
        ]);

        $vehicle->syncChanges();
        $vehicle->syncRelationChanges();
        $this->assertFalse($vehicle->isDirty());
        $this->assertTrue($vehicle->areRelationsDirty());
    }

    public function testHasManyDirtinessAttributeChangeNegative() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels()->saveMany($wheels);
        $vehicle->save();
        $vehicle = $vehicle->fresh();

        $vehicle->fill([
            'wheels' => [
                $wheels[0]->id => [ 'size' => 1 ],
                $wheels[1]->id => [ 'size' => 2 ],
                $wheels[2]->id => [ 'size' => 3 ]
            ]
        ]);

        $vehicle->syncChanges();
        $vehicle->syncRelationChanges();
        $this->assertFalse($vehicle->isDirty());
        $this->assertFalse($vehicle->areRelationsDirty());
    }

    public function testHasManyDirtinessDetach() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels()->saveMany($wheels);
        $vehicle->save();
        $vehicle = $vehicle->fresh();

        $vehicle->fill([
            'wheels' => [
                $wheels[0]->id => [ 'size' => 1 ],
                $wheels[1]->id => [ 'size' => 2 ]
            ]
        ]);

        $vehicle->syncChanges();
        $vehicle->syncRelationChanges();
        $this->assertFalse($vehicle->isDirty());
        $this->assertTrue($vehicle->areRelationsDirty());
    }

    public function testHasManyDirtinessAttach() {
        $wheels = [
            Wheel::create([ 'size' => 1 ]),
            Wheel::create([ 'size' => 2 ]),
            Wheel::create([ 'size' => 3 ])
        ];
        $wheel = Wheel::create([ 'size' => 4 ]);
        $vehicle = Vehicle::create([
            'name' => 'Car'
        ]);
        $vehicle->wheels()->saveMany($wheels);
        $vehicle->save();
        $vehicle = $vehicle->fresh();

        $vehicle->fill([
            'wheels' => [
                $wheels[0]->id => [ 'size' => 1 ],
                $wheels[1]->id => [ 'size' => 2 ],
                $wheel->id => [ 'size' => $wheel->size ]
            ]
        ]);

        $vehicle->syncChanges();
        $vehicle->syncRelationChanges();
        $this->assertFalse($vehicle->isDirty());
        $this->assertTrue($vehicle->areRelationsDirty());
    }
}
