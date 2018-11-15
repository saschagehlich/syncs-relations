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

    /**
     * Stores the attribute values that the attributes will have after saving
     * @var array
     */
    protected $relationshipAttributes = [];

    /**
     * Stores additional data for the relationship that need to persist between
     * filling and saving of the model
     * @var array
     */
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
                $this->fillRelation($relationName, $delete ?: $data, !!$delete, !!$new);
            }
        }

        parent::fill($attributes);

        return $this;
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
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
            $this->fillManyRelation($relationName, $relation, $data, $delete);
        } else if ($relation instanceof BelongsTo) {
            $this->fillBelongsToRelation($relationName, $relation, $data, $delete, $new);
        } else if ($relation instanceof HasOne) {
            $this->fillHasOneRelation($relationName, $relation, $data, $new);
        } else {
            throw new RuntimeException("Could not fill relation {$relationName}");
        }

        return $this;
    }

    /**
     * Persist the given relation and its filled data
     *
     * @param string $relationName
     * @return $this
     */
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


    /**
     * Fill the given BelongsTo relation with the given data
     *
     * @param string $relationName
     * @param BelongsTo $relation
     * @param $data
     * @param bool $delete
     * @param bool $new
     */
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

    /**
     * Persist the given BelongsTo relation with its filled data
     *
     * @param string $relationName
     * @param HasOne $relation
     */
    protected function saveBelongsToRelation (string $relationName, BelongsTo $relation) {
        $this->removeAttribute($relationName);

        /** @var Model $entity */
        $entity = $this->relationshipAttributes[$relationName];
        if ($entity != null) {
            $entity->save();
        }
        $relation->associate($entity);
        parent::save();
    }

    /**
     * Fill the given HasOne relation with the given data
     *
     * @param string $relationName
     * @param HasOne $relation
     * @param $data
     * @param bool $new
     */
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

    /**
     * Persist the given HasOne relation with its filled data
     *
     * @param string $relationName
     * @param HasOne $relation
     */
    protected function saveHasOneRelation (string $relationName, HasOne $relation) {
        $this->removeAttribute($relationName);

        /** @var Model $entity */
        $entity = $this->relationshipAttributes[$relationName];
        if ($entity != null) {
            $entity->save();
            $relation->save($entity);
        } else {
            $relation->delete();
        }
    }

    /**
     * Fill the given HasMany or BelongsToMany relation with the given data
     *
     * @param string $relationName
     * @param HasMany|BelongsToMany $relation
     * @param array $data
     * @param boolean $delete
     */
    protected function fillManyRelation (string $relationName, $relation, array $data, bool $delete) {
        // TODO: This is a pretty long method - we need to refactor this
        $changes = [
            'detached' => [],
            'attached' => [],
            'updated' => []
        ];

        $children = $relation->getResults();
        $relatedModel = $relation->getModel();

        $dataContainsArrays = count(array_filter($data, 'is_array')) == count($data);
        $dataContainsInstances = !$dataContainsArrays && count($data) > 0 && $data[0] instanceof Model;

        if ($delete) {
            if ($dataContainsInstances) {
                $deletedChildren = $data;
            } else if (!$dataContainsArrays) {
                $deletedChildren = $relatedModel::whereIn('id', $data)->get();
            }

            foreach ($deletedChildren as $child) {
                $changes['detached'][] = $child;
            }
            $deletedIds = array_pluck($deletedChildren, 'id');
            $newChildren = $relatedModel::whereNotIn('id', $deletedIds)->get();
        } else {
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
        }

        // This is set temporarily so that we can access the related instance
        // even though it is not saved yet.
        $this->$relationName = $newChildren;
        $this->relationshipData[$relationName] = $changes;
        $this->relationshipAttributes[$relationName] = $newChildren;
    }

    /**
     * Persist the given HasMany or BelongsToMany relation with its filled data
     *
     * @param string $relationName
     * @param HasMany|BelongsToMany $relation
     */
    protected function saveManyRelation (string $relationName, $relation) {
        $data = $this->relationshipData[$relationName];

        foreach ($data['detached'] as $entity) {
            if ($relation instanceof BelongsToMany) {
                $relation->detach($entity->id);
            } else {
                $entity->delete();
            }
        }
        foreach ($data['attached'] as $entity) {
            $relation->save($entity);
        }

        foreach ($data['updated'] as $entity) {
            $entity->save();
        }

        $this->removeAttribute($relationName);
    }

    /**
     * Removes all temporary attributes from the model
     */
    protected function removeTemporaryAttributes () {
        $syncedRelations = $this->getSyncedRelations();
        foreach ($syncedRelations as $relationName) {
            $this->removeAttribute($relationName);
        }
    }

    /**
     * Removes the given (temporary) attribute
     * @param string $attribute
     */
    protected function removeAttribute (string $attribute) {
        // Modify attributes so that the temporarily set (see `fillBelongsToRelation`)
        // attribute is no longer there and is Eloquent does not try to write it
        // to the database.
        $attributes = $this->getAttributes();
        unset($attributes[$attribute]);
        $this->setRawAttributes($attributes);
    }

    /**
     * Determine if the model or given attribute(s) have been modified.
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        if ($attributes != null) {
            return parent::isDirty($attributes);
        }

        $syncedRelations = $this->getSyncedRelations();
        $attributes = array_keys(array_except($this->getAttributes(), $syncedRelations));
        return parent::isDirty($attributes);
    }

    public function areRelationsDirty()
    {
        $syncedRelations = $this->getSyncedRelations();

        foreach ($syncedRelations as $relationName) {
            $relationDirty = $this->isRelationDirty($relationName);
            if ($relationDirty) return $relationDirty;
        }

        return false;
    }

    protected function isRelationDirty (string $relationName) {
        $methodName = camel_case($relationName);
        if (!method_exists($this, $methodName)) return false;

        /** @var Relation $relation */
        $relation = $this->$methodName();
        if ($relation instanceof HasMany || $relation instanceof BelongsToMany) {
            return $this->isManyRelationDirty($relationName, $relation);
        } else if ($relation instanceof BelongsTo || $relation instanceof HasOne) {
            return $this->isSingleRelationDirty($relationName, $relation);
        } else {
            throw new RuntimeException("Could not check dirtiness for relation {$relationName}");
        }
    }

    protected function isManyRelationDirty (string $relationName, Relation $relation) {
        $entities = $this->$relationName;
        if (!array_key_exists($relationName, $this->relationshipData)) return false;

        $changes = $this->relationshipData[$relationName];
        if (count($changes['attached']) > 0 || count($changes['detached']) > 0) {
            return true;
        }

        foreach ($entities as $entity) {
            if ($entity->isDirty()) return true;
        }
        return false;
    }

    protected function isSingleRelationDirty (string $relationName, Relation $relation) {
        $entity = $relation->getResults();
        if ($entity == null) return $this->getOriginal($relationName) != null;
        return $entity->isDirty();
    }

    public function syncRelationChanges () {
        $syncedRelations = $this->getSyncedRelations();

        foreach ($syncedRelations as $relationName) {
            $methodName = camel_case($relationName);
            if (!method_exists($this, $methodName)) continue;

            /** @var Relation $relation */
            $relation = $this->$methodName();

            if ($relation instanceof HasMany || $relation instanceof BelongsToMany) {
                $this->syncManyRelationChanges($relationName, $relation);
            } else if ($relation instanceof BelongsTo || $relation instanceof HasOne) {
                $this->syncSingleRelationChanges($relationName, $relation);
            } else {
                throw new RuntimeException("Could not sync changes for relation {$relationName}");
            }
        }

        return false;
    }

    protected function syncManyRelationChanges (string $relationName, Relation $relation) {
        $entities = $this->$relationName;
        foreach ($entities as $entity) {
            $entity->syncChanges();
        }
    }

    protected function syncSingleRelationChanges (string $relationName, Relation $relation) {
        $entity = $this->$relationName;
        if ($entity == null) return;
        $entity->syncChanges();
    }
}
