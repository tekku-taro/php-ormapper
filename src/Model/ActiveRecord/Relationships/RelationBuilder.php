<?php
namespace ORM\Model\ActiveRecord\Relationships;

use ORM\Model\ActiveRecord\QueryBuilder;

trait RelationBuilder
{
    protected $related;

    public function hasMany($className, $foreignKey = null)
    {
        $relation = $this->initRelation($className, $foreignKey, 'hasMany');
        return $relation->where($relation->foreignKey, $this->id);
    }

    public function belongsTo($className, $foreignKey = null)
    {
        $relation = $this->initRelation($className, $foreignKey, 'hasMany');
        return $relation->where('id', $this->{$relation->foreignKey});
    }

    public function hasOne($className, $foreignKey = null)
    {
        $relation = $this->initRelation($className, $foreignKey, 'hasMany');
        return $relation->where($relation->foreignKey, $this->id);
    }

    public function belongsToMany($className, $pivotTable, $foreignKey, $relatedKey)
    {
        $relation = $this->initRelationWithPivot($className, $pivotTable, $foreignKey, $relatedKey, 'belongsToMany');

        $ids = $this->getRelatedIds($pivotTable, $foreignKey, $relatedKey);
        
        return $relation->where($relatedKey, 'IN', $ids);
    }

    protected function initRelationWithPivot($className, $pivotTable, $foreignKey, $relatedKey, $typeName)
    {
        $tableName = static::guessTableName($className);
        $relation = new Relation($tableName, $className);

        $relation->type = $typeName;
        $relation->pivot = $pivotTable;

        $relation->foreignKey = $this->checkForeign($tableName, $foreignKey);
        $relation->relatedKey = $this->checkForeign($tableName, $relatedKey);

        return $relation;
    }

    protected function getRelatedIds($pivot, $foreignKey, $relatedKey)
    {
        $query = new QueryBuilder($pivot, Object::class);
        $relatedModels = $query->where($foreignKey, $this->id)->select([$relatedKey])->findMany();
        
        $relatedIds = array_map(function ($model) {
            return $model->id;
        }, $relatedModels);

        return $relatedIds;
    }

    protected function initRelation($className, $foreignKey, $typeName)
    {
        $tableName = static::guessTableName($className);
        $relation = new Relation($tableName, $className);

        $relation->type = $typeName;

        $relation->foreignKey = $this->checkForeign($tableName, $foreignKey);

        return $relation;
    }

    protected function checkForeign($tableName, $foreignKey)
    {
        if (empty($foreignKey)) {
            return rtrim($tableName, 's') . '_id';
        }

        return $foreignKey;
    }
}
