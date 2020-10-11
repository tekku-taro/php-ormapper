<?php
namespace ORM\Model\ActiveRecord\Relationships;

use ORM\Model\ActiveRecord\QueryBuilder;

trait RelationBuilder
{
    protected $related;


    public function relation($relationName)
    {//Post::class,'hasMany' ,'user_id'
        $data  = $this->relations[$relationName];
        $className = $data[0];
        $type = $data[1];
        if ($type == 'belongsToMany') {
            [,,$pivotTable, $foreignKey, $relatedKey] = $data;
            return $this->{$type}($className, $pivotTable, $foreignKey, $relatedKey);
        } else {
            $foreignKey = isset($data[2]) ? $data[2] : null ;
            return $this->{$type}($className, $foreignKey);
        }
    }

    public function hasMany($className, $foreignKey = null)
    {
        $relation = $this->initRelation($className, $foreignKey, 'hasMany');
        return $relation->buildWhere();
    }

    public function belongsTo($className, $foreignKey = null)
    {
        $relation = $this->initRelation($className, $foreignKey, 'belongsTo');
        return $relation->buildWhere();
    }

    public function hasOne($className, $foreignKey = null)
    {
        $relation = $this->initRelation($className, $foreignKey, 'hasOne');
        return $relation->buildWhere();
    }

    public function belongsToMany($className, $pivotTable, $foreignKey, $relatedKey)
    {
        $relation = $this->initRelationWithPivot($className, $pivotTable, $foreignKey, $relatedKey, 'belongsToMany');

        return $relation->buildWhere();
    }

    protected function initRelationWithPivot($className, $pivotTable, $foreignKey, $relatedKey, $typeName)
    {
        $tableName = static::guessTableName(substr(strrchr($className, '\\'), 1));
        $relation = new Relation($tableName, $className);

        $data = [
            'modelId'=>$this->id,
            'model'=>$this,
            'type'=>$typeName,
            'foreignKey'=>$this->checkForeign($tableName, $foreignKey),
            'pivotTable'=> $pivotTable,
            'relatedKey'=>$this->checkForeign($tableName, $relatedKey)
        ];

        $relation->setRelationdata($data);

        return $relation;
    }

    protected function initRelation($className, $foreignKey, $typeName)
    {
        $tableName = static::guessTableName(substr(strrchr($className, '\\'), 1));
        $relation = new Relation($tableName, $className);

        $data = [
            'modelId'=>$this->id,
            'model'=>$this,
            'type'=>$typeName,
            'foreignKey'=>$this->checkForeign($tableName, $foreignKey)
        ];

        $relation->setRelationdata($data);

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
