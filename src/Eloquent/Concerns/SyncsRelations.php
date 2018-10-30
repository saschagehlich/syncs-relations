<?php

namespace SyncsRelations\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

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
            if ($data) {
                $this->fillRelation($relationName, $data);
            }
        }
        return $this;
    }

    /**
     * Fill the given relation with the given data
     *
     * @param string $relationName
     * @param array $data
     * @return $this;
     */
    protected function fillRelation (string $relationName, array $data) {
        $methodName = camel_case($relationName);
        if (!method_exists($this, $methodName)) return $this;

        /** @var Relation $relation */
        $relation = $this->$methodName();
        if ($relation instanceof HasMany) {
            $this->fillHasManyRelation($relation, $data);
        }

        return $this;
    }

    protected function fillHasManyRelation (HasMany $relation, array $data) {
        $children = $relation->getResults();
        $relatedModel = $relation->getModel();
        $childIds = $children->map(function ($child) { return $child->id; })->toArray();
        $dataContainsArrays = count(array_filter($data, 'is_array')) == count($data);

        if (!$dataContainsArrays) {
            $ids = $data;
        } else {
            $ids = array_keys($data);
        }

        $detachedIds = array_diff($childIds, $ids);
        foreach ($detachedIds as $id) {
            $children->where('id', $id)->first()->delete();
        }

        if (!$dataContainsArrays) {
            $attachedIds = array_diff($ids, $childIds);
            foreach ($attachedIds as $id) {
                $instance = $relatedModel->where('id', $id)->first();
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