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
            $present = array_key_exists($relationName, $attributes) ||
                array_key_exists('new_' . $relationName, $attributes);
            $data = array_pull($attributes, $relationName);
            $delete = array_pull($attributes, 'delete_' . $relationName);
            $new = array_pull($attributes, 'new_' . $relationName);

            if ($new) {
                $data = $new;
            }

            if ($present || $delete) {
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
            $this->fillHasOneRelation($relationName, $relation, $data, $new);
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
            $this->saveHasOneRelation($relationName, $relation);
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

    protected function fillHasOneRelation (string $relationName, HasOne $relation, $data, bool $new) {
        $relatedModel = $relation->getRelated();

        if ($data == null) {
            $instance = null;
        } else if ($new) {
            $instance = new $relatedModel($data);
        } else if ($data instanceof Model) {
            $instance = $data;
        } else {
            $instance = $relation->getResults();
            $instance->fill($data);
        }

        $this->$relationName = $instance;
        $this->relationshipAttributes[$relationName] = $instance;
    }

    protected function saveHasOneRelation (string $relationName, HasOne $relation) {
        $this->removeAttribute($relationName);

        /** @var Model $model */
        $model = $this->relationshipAttributes[$relationName];
        if ($model != null) {
            $model->save();
            $relation->save($model);
        } else {
            $relation->delete();
        }
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