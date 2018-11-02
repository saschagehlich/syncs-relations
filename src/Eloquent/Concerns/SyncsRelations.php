<?php

namespace SyncsRelations\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SyncsRelations\Tests\Models\Vehicle;
use SyncsRelations\Tests\Models\Wheel;

/**
 * This trait enables models to be filled with nested relationship data.
 * Usage:
 *      class Cart extends Model {
 *          use SyncsRelations;
 *          protected $synced_relations = ['products'];
 *
 *          public function products() {
 *              return $this->hasMany(Product::class);
 *          }
 *      }
 *
 * A `Cart` instance can now be filled with one or multiple products.
 *
 * Example 1: Create relation to existing `Product` instances, referenced by their ID
 *      $cart = new Cart([
 *          'products' => [
 *              Product::first()->id
 *          ]
 *      ]);
 *
 * Example 2: Create relation to new `Product` instance using a named array
 *      $cart = new Cart([
 *          'products' => [
 *              'new123' => ['name' => 'Product Name']
 *          ]
 *      ]);
 *
 * Example 3: Update data for existing related `Product` instance
 *      $cart = new Cart([
 *          'products' => [
 *              '3' => ['name' => 'Updated Product Name']
 *          ]
 *      ]);
 *
 * Example 4: Combine examples 2 and 3, this will result in a new product instance as well
 *            as a reference to an existing product instance
 *      $cart = new Cart([
 *          'products' => [
 *              'new123' => ['name' => 'New Product Name'],
 *              '3' => ['name' => 'Updated Product Name']
 *          ]
 *      ]);
 *
 * A couple of things to note:
 *
 *      * When given an indexed array, the array should include IDs of existing entries
 *        in the related table.
 *      * When given a named array, if the key is the ID of an existing entry in
 *        the related table, the trait will fill the entry with the associated values.
 *
 * Relationships are not only filled, but synced, meaning that:
 *
 *      * IDs that do not exist in the database will result in new instances of the related model
 *      * If there are existing related instances but they're not present in the passed data,
 *        they will be removed from the database.
 *
 * @mixin Model
 * @property string[] $synced_relations
 */
trait SyncsRelations {
    // protected $syncedRelations = [];
    protected $relationshipAttributes = [];
    protected $relationshipData = [];

    /**
     * @return string[]
     */
    public function getSyncedRelations(): array {
        return isset($this->syncedRelations) ? $this->syncedRelations : [];
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes) {
        parent::fill($attributes);

        $syncedRelations = $this->getSyncedRelations();
        foreach ($syncedRelations as $relationName) {
            $data = array_pull($attributes, $relationName);
            $delete = array_pull($attributes, 'delete_' . $relationName);
            $new = array_pull($attributes, 'new_' . $relationName);

            if ($new) {
                $data = $new;
            }

            if ($data || $delete) {
                $this->fillRelation($relationName, $data, !!$delete, !!$new);
            }
        }

        return $this;
    }

    public function save(array $options = [])
    {
        $this->removeTemporaryAttributes();
        $self = parent::save($options);

        $syncedRelations = $this->getSyncedRelations();
        foreach ($syncedRelations as $relationName) {
            if (!array_key_exists($relationName, $this->relationshipAttributes)) continue;
            $this->saveRelation($relationName);
        }

        return $self;
    }

    /**
     * Fill the given relation with the given data
     *
     * @param string $relationName
     * @param mixed $data
     * @param bool $delete
     * @param bool $new
     * @return $this;
     */
    protected function fillRelation (string $relationName, $data, bool $delete, bool $new) {
        $methodName = camel_case($relationName);
        if (!method_exists($this, $methodName)) return $this;

        /** @var Relation $relation */
        $relation = $this->$methodName();
        if ($relation instanceof HasMany || $relation instanceof BelongsToMany) {
            $this->fillManyRelation($relationName, $relation, $data);
        } else if ($relation instanceof BelongsTo) {
            $this->fillBelongsToRelation($relationName, $relation, $data, $delete, $new);
        } else if ($relation instanceof HasOne) {
            $this->fillHasOneRelation($relation, $data, $delete, $new);
        } else {
            throw new RuntimeException("Could not fill relation {$relationName}");
        }

        return $this;
    }

    protected function saveRelation (string $relationName) {
        $methodName = camel_case($relationName);
        if (!method_exists($this, $methodName)) return $this;

        /** @var Relation $relation */
        $relation = $this->$methodName();
        if ($relation instanceof HasMany || $relation instanceof BelongsToMany) {
            $this->saveManyRelation($relationName, $relation);
        } else if ($relation instanceof BelongsTo) {
            $this->saveBelongsToRelation($relationName, $relation);
        } else if ($relation instanceof HasOne) {
            $this->saveHasOneRelation($relation);
        }

        return $this;
    }

    protected function fillBelongsToRelation (string $relationName, BelongsTo $relation, $data, bool $delete, bool $new) {
        $relatedModel = $relation->getModel();

        if ($delete) {
            $instance = null;
        } else if ($new) {
            $instance = new $relatedModel($data);
        } else if ($data instanceof Model) {
            $instance = $data;
        } else {
            $instance = $relation->getResults();
            $instance->fill($data);
        }

        // This is set temporarily so that we can access the related instance
        // even though it is not saved yet.
        $this->$relationName = $instance;
        $this->relationshipAttributes[$relationName] = $instance;
    }

    protected function saveBelongsToRelation (string $relationName, BelongsTo $relation) {
        $this->removeAttribute($relationName);

        /** @var Model $model */
        $model = $this->relationshipAttributes[$relationName];
        if ($model != null) {
            $model->save();
        }
        $relation->associate($model);
        parent::save();
    }

    protected function fillHasOneRelation (HasOne $relation, $data, bool $delete, bool $new) {
        $relatedModel = $relation->getRelated();
        if ($delete) {
            $relation->delete();
        } else if ($new) {
            $instance = $relatedModel::create($data);
            $instance->setAttribute($relation->getForeignKeyName(), $relation->getParentKey());
        } else if ($data instanceof Model) {
            $data->setAttribute($relation->getForeignKeyName(), $relation->getParentKey());
        } else {
            $instance = $relation->getResults();
            $instance->fill($data);
        }
    }

    protected function saveHasOneRelation (HasOne $relation) {

    }

    /**
     * @param HasMany|BelongsToMany $relation
     * @param array $data
     */
    protected function fillManyRelation (string $relationName, $relation, array $data) {
        $changes = [
            'detached' => [],
            'attached' => [],
            'updated' => []
        ];

        $children = $relation->getResults();
        $relatedModel = $relation->getModel();

        $dataContainsArrays = count(array_filter($data, 'is_array')) == count($data);
        $dataContainsInstances = !$dataContainsArrays && count($data) > 0 && $data[0] instanceof Model;

        /** @var Collection $newChildren */
        if ($dataContainsInstances) {
            $newChildren = collect($data);
        } else if ($dataContainsArrays) {
            $newChildren = [];
            foreach ($data as $id => $childData) {
                $child = $relatedModel::find($id) ?: new $relatedModel;
                $child->fill($childData);
                $newChildIds[] = $id;
                $newChildren[] = $child;
            }
            $newChildren = collect($newChildren);
        } else {
            $newChildIds = $data;
            $newChildren = $relatedModel::whereIn('id', $newChildIds)->get();
        }

        foreach ($children as $child) {
            if (!$newChildren->contains('id', null, $child->id)) {
                $changes['detached'][] = $child;
            } else {
                $changes['updated'][] = $newChildren->firstWhere('id', $child->id);
            }
        }

        foreach ($newChildren as $child) {
            if (!$children->contains('id', null, $child->id)) {
                $changes['attached'][] = $child;
            }
        }

        // This is set temporarily so that we can access the related instance
        // even though it is not saved yet.
        $this->$relationName = $newChildren;
        $this->relationshipData[$relationName] = $changes;
        $this->relationshipAttributes[$relationName] = $newChildren;
    }

    /**
     * @param string $relationName
     * @param HasMany|BelongsToMany $relation
     */
    protected function saveManyRelation (string $relationName, $relation) {
        $data = $this->relationshipData[$relationName];

        foreach ($data['detached'] as $model) {
            if ($relation instanceof BelongsToMany) {
                $relation->detach($model->id);
            } else {
                $model->delete();
            }
        }
        foreach ($data['attached'] as $model) {
            $relation->save($model);
        }

        foreach ($data['updated'] as $model) {
            $model->save();
        }

        $this->removeAttribute($relationName);
    }

    protected function removeTemporaryAttributes () {
        $syncedRelations = $this->getSyncedRelations();
        foreach ($syncedRelations as $relationName) {
            $this->removeAttribute($relationName);
        }
    }

    protected function removeAttribute (string $attribute) {
        // Modify attributes so that the temporarily set (see `fillBelongsToRelation`)
        // attribute is no longer there and is Eloquent does not try to write it
        // to the database.
        $attributes = $this->getAttributes();
        unset($attributes[$attribute]);
        $this->setRawAttributes($attributes);
    }
}