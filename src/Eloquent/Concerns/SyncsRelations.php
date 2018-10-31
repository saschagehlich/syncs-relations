<?php

namespace SyncsRelations\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use RuntimeException;

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
            $this->fillManyRelation($relation, $data);
        } else if ($relation instanceof BelongsTo) {
            $this->fillBelongsToRelation($relation, $data, $delete, $new);
        } else if ($relation instanceof HasOne) {
            $this->fillHasOneRelation($relation, $data, $delete, $new);
        } else {
            throw new RuntimeException("Could not fill relation {$relationName}");
        }

        return $this;
    }

    /**
     * @param BelongsTo $relation
     * @param $data
     * @param bool $delete
     * @param bool $new
     */
    protected function fillBelongsToRelation (BelongsTo $relation, $data, bool $delete, bool $new) {
        $relatedModel = $relation->getModel();

        if ($delete) {
            $instance = null;
        } else if ($new) {
            $instance = $relatedModel::create($data);
        } else if ($data instanceof Model) {
            $instance = $data;
        } else {
            $instance = $relation->getResults();
            $instance->fill($data);
            $instance->save();
        }

        $relation->associate($instance);
    }

    protected function fillHasOneRelation (HasOne $relation, $data, bool $delete, bool $new) {
        $relatedModel = $relation->getRelated();
        if ($delete) {
            $relation->delete();
        } else if ($new) {
            $instance = $relatedModel::create($data);
            $relation->save($instance);
        } else if ($data instanceof Model) {
            $relation->save($data);
        } else {
            $instance = $relation->getResults();
            $instance->fill($data);
            $instance->save();
        }
    }

    /**
     * @param HasMany|BelongsToMany $relation
     * @param array $data
     */
    protected function fillManyRelation ($relation, array $data) {
        $children = $relation->getResults();
        $relatedModel = $relation->getModel();
        $childIds = $children->map(function ($child) { return $child->id; })->toArray();
        $dataContainsArrays = count(array_filter($data, 'is_array')) == count($data);
        $dataContainsInstances = false;
        if (!$dataContainsArrays && count($data) > 0) {
            $dataContainsInstances = $data[0] instanceof Model;
        }

        if (!$dataContainsArrays) {
            $ids = $data;
        } else {
            $ids = array_keys($data);
        }

        if ($dataContainsInstances) {
            $ids = array_pluck($data, 'id');
        }

        $detachedIds = array_diff($childIds, $ids);
        foreach ($detachedIds as $id) {
            $children->where('id', $id)->first()->delete();
        }

        if (!$dataContainsArrays) {
            $attachedIds = array_diff($ids, $childIds);
            foreach ($attachedIds as $index => $id) {
                if ($dataContainsInstances) {
                    $instance = array_first($data, function ($instance) use ($id) {
                        return $instance->id == $id;
                    });
                } else {
                    $instance = $relatedModel->where('id', $id)->first();
                }
                $relation->save($instance);
            }
        } else {
            foreach ($data as $id => $childData) {
                /** @var Model $instance */
                $instance = $relatedModel::find($id) ?: new $relatedModel;
                $instance->fill($childData);
                $relation->save($instance);
            }
        }
    }
}