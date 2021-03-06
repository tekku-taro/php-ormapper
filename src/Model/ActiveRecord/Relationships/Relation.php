<?php
namespace ORM\Model\ActiveRecord\Relationships;

use ORM\Model\ActiveRecord\Model;
use ORM\Model\ActiveRecord\QueryBuilder;
use ORM\Model\Adapter\RDBAdapter;
use phpDocumentor\Reflection\Types\Collection;

class Relation extends QueryBuilder
{
    // protected $tableName;
    // protected $modelClass;
    // protected $dbName;
    /**
     * リレーションのタイプ
     *
     * @var string
     */
    protected $type;
    /**
     * relation()を呼び出したモデル
     *
     * @var ORM\Model\ActiveRecord\Model
     */
    protected $parentModel;
    /**
     * relation()を呼び出したモデルのID
     *
     * @var int
     */
    protected $parentModelId;
    /**
     * 外部キー
     *
     * @var int
     */
    protected $foreignKey;
    /**
     * 多対多の関連モデルのキー
     *
     * @var int
     */
    protected $relatedKey;
    /**
     * 中間テーブル名
     *
     * @var string
     */
    public $pivotTable;
    /**
     * Eagerloading 対象のリレーション名配列
     *
     * @var array
     */
    public $eagerLoadings = [];

    public $counts = [];

    public function getType()
    {
        return $this->type;
    }


    public function hasRelations($relationNames)
    {
        $relationCounts = [];
        foreach ($relationNames as $relationName) {
            $relationData = $this->modelClass::getRelationData($relationName);
            $relationClass = $relationData[0];
            $type = $relationData[1];
            $foreignKeyName = $relationData[2];

            switch ($type) {
                case 'hasMany':
                    // SELECT * FROM tasksdb.users
                    // where id IN (select Distinct user_id FROM posts)                    ;
                    
                    $this->where('id', 'IN', function () use ($foreignKeyName,$relationClass) {
                        return $relationClass::select([$foreignKeyName])->toSql();
                    });
                    break;
                case 'belongsTo':
                    // SELECT * FROM tasksdb.posts
                    // where user_id is not null;
                    $this->where($foreignKeyName, 'IS NOT', null);
                    break;
                case 'belongsToMany':
                    // SELECT * FROM tasksdb.users
                    // where id IN (select user_id FROM favorites)
                    $pivotTable = $foreignKeyName;
                    $foreignKeyName = $relationData[3];
                    $relatedKeyName = $relationData[4];

                    $this->where('id', 'IN', function () use ($foreignKeyName,$pivotTable) {
                        return ' SELECT '. $foreignKeyName .' FROM '. $pivotTable ;
                    });
                    // $subquery = '( SELECT '. $foreignKeyName .' FROM '. $pivotTable .' )';
                    // $this->where('id', 'IN', $subquery);
                    break;
            }
        }

        return $this;
    }

    public function newPivot($relatedKeyVal, $data = [])
    {
        $insertData = [
            $this->foreignKey => $this->parentModelId,
            $this->relatedKey => $relatedKeyVal,
            
        ] + $data;
        $query = [
            'data' => $insertData
        ];

        $id = $this->adapter->insert($this->pivotTable, $query, $this->dbName);
        if ($id) {
            $this->parentModel->pivot = $insertData;
            return $this->parentModel;
        } else {
            throw new \ErrorException('pivot record can not be saved!');
        }
    }

    public function removePivot($relatedKeyVal)
    {
        if (empty($relatedKeyVal)) {
            throw new \ErrorException('Provided related key is empty;');
        }
        $whereClauses[] = ['AND', $this->foreignKey . ' = ?' ];
        $whereClauses[] = ['AND', $this->relatedKey . ' = ?' ];

        $binds = [$this->parentModelId, $relatedKeyVal];
        $query = [
            'where'=>$whereClauses,
            'binds'=>[
                'where'=>$binds
            ]
        ];
        return $this->adapter->delete($this->pivotTable, $query);
    }

    public function updatePivot($relatedKeyVal, $data = [])
    {
        if (empty($data)) {
            return false;
        }
        $whereClauses[] = ['AND', $this->foreignKey . ' = ?'];
        $whereClauses[] = ['AND', $this->relatedKey . ' = ?'];
        $binds = [$this->parentModelId, $relatedKeyVal];
        $query = [
            'data' => $data,
            'where'=>$whereClauses,
            'binds'=>[
                'where'=>$binds
            ]
        ];

        $result = $this->adapter->update($this->pivotTable, $query);
        if ($result) {
            $data = [
                $this->foreignKey => $this->parentModelId,
                $this->relatedKey => $relatedKeyVal,
                
            ] + $data;
            $this->parentModel->pivot = $data;
            return $this->parentModel;
        } else {
            throw new \ErrorException('pivot record can not be updated!');
        }
    }

    public function with($relationNames)
    {
        $this->eagerLoadings += $relationNames;

        return $this;
    }


    protected function morphToModel($stmt, $useCollect = false)
    {
        $result = parent::morphToModel($stmt, $useCollect);
        if (empty($result)) {
            return $result;
        }
        if ($this->type == 'belongsToMany' && isset($this->parentModel)) {
            $data = [
                'foreignKey'=>$this->foreignKey,
                'relatedKey'=>$this->relatedKey ,
                'parentModel'=>$this->parentModel,
                'parentModelId'=>$this->parentModelId,
            ];
        } elseif (empty($this->type) || empty($this->parentModel)) {
            if (!$useCollect) {
                $this->loadModels($result);
            } else {
                $this->loadModelsToCollection($result);
            }

            return $result;
        } else {
            $data = [
                'parentModel'=>$this->parentModel,
            ];
        }

        if (!$useCollect) {
            $parentRelationName = $this->getParentRelationName($result);
            return $result->setPivotAndRelationModel($data, $parentRelationName);
        } else {
            $parentRelationName = $this->getParentRelationName($result[0]);
            return  array_map(function ($entity) use ($parentRelationName,$data) {
                return $entity->setPivotAndRelationModel($data, $parentRelationName);
            }, $result);
        }
    }
    
    protected function loadModelsToCollection(array &$collection)
    {
        if (!empty($this->eagerLoadings)) {
            foreach ($this->eagerLoadings as $relationName) {
                $this->addModelToCollection($collection, $relationName);
            }
        }
    }

    protected function setRelationModelToCollection(array  &$collection, $foreignKeyIdxPairs, $relationName, $type, $models, $foreignKeyName)
    {
        foreach ($models as $model) {
            switch ($type) {
                case 'hasMany':
                    $foreignKey = $model->{$foreignKeyName};
                    
                break;
                case 'belongsTo':
                    $foreignKey = $model->id;
                    
                    break;
                    case 'belongsToMany':
                        $foreignKey = $model->{$foreignKeyName};
                        
                    break;
            }
            $idx = $foreignKeyIdxPairs[$foreignKey];
            $collection[$idx]->setRelationModels([$relationName => [$model]]);
        }
    }

    protected function addModelToCollection(array &$collection, $relationName)
    {
        //各リレーションの情報（外部キー名）を取得
        $entity = $collection[0];
        $relationData = $entity::getRelationData($relationName);
        $relationClass = $relationData[0];
        $type = $relationData[1];
        $foreignKeyName = $relationData[2];

        // モデル配列からリレーションの外部キーの値を取得
        // ['リレーション名'=>['外部キー'=>collectionの添え字],]
        // リレーションについて、それぞれの外部キー配列からモデル配列を取得
        switch ($type) {
            case 'hasMany':
                [$foreignKeys,$foreignKeyIdxPairs] = $this->getForeignKeysFromCollection($collection, 'id');
                $relationModels = $relationClass::where($foreignKeyName, 'IN', $foreignKeys)->findMany();
                break;
            case 'belongsTo':
                [$foreignKeys,$foreignKeyIdxPairs] = $this->getForeignKeysFromCollection($collection, $foreignKeyName);
                $relationModels = $relationClass::where('id', 'IN', $foreignKeys)->findMany();
                break;
            case 'belongsToMany':
                $pivotTable = $foreignKeyName;
                $foreignKeyName = $relationData[3];
                $relatedKeyName = $relationData[4];
                [$foreignKeys,$foreignKeyIdxPairs] = $this->getForeignKeysFromCollection($collection, 'id');
                $query = $this->buildBelongsToManyQuery($foreignKeys, $relationClass, $pivotTable, $foreignKeyName, $relatedKeyName);
                $relationModels = $query->appendPivot([$foreignKeyName])->findMany();
                break;
        }
        // 各モデルの外部キーから添え字を取得し、元のcollectionのモデルにrelationModelとして付加
        $this->setRelationModelToCollection($collection, $foreignKeyIdxPairs, $relationName, $type, $relationModels, $foreignKeyName);
    }


    protected function getForeignKeysFromCollection(array $collection, $foreignKeyName)
    {
        $foreignKeyIdxPairs = [];
        foreach ($collection as $idx => $entity) {
            $foreignKey = $entity->{$foreignKeyName};
            $foreignKeyIdxPairs[$foreignKey] = $idx;
            $foreignKeys[] = $foreignKey;
        }
        return [$foreignKeys,$foreignKeyIdxPairs];
    }

    protected function loadModels(Model &$entity)
    {
        if (!empty($this->eagerLoadings)) {
            $relationModels = [];
            foreach ($this->eagerLoadings as $relationName) {
                $relation = $entity->relation($relationName);
    
                if (in_array($relation->getType(), ['belongsTo','hasOne'])) {
                    $relationModels[$relationName] = $relation->findFirst();
                } else {
                    $relationModels[$relationName] = $relation->findMany();
                }
            }
            $entity->setRelationModels($relationModels);
        }
    }

    protected function getParentRelationName($entity)
    {
        //リレーション名を調べる　$relationName
        $relationName = $this->getOppositeRelationName($entity);
        if (empty($relationName)) {
            $relationName = $this->parentModel::getTableName();
        }

        return $relationName;
    }


    protected function getOppositeRelationName($entity)
    {
        $oppositeType = $this->getOpossiteType();

        return $entity->getRelationName(get_class($this->parentModel), $oppositeType);
    }


    protected function getOpossiteType()
    {
        switch ($this->type) {
            case 'hasMany':
                return 'belongsTo';
                break;
            case 'belongsTo':
                return 'hasMany';
                break;
            case 'hasOne':
                return 'belongsTo';
                break;
            case 'belongsToMany':
                return 'belongsToMany';
                break;
            
            default:
                return null;
                break;
        }
    }

    public function setRelationdata($data)
    {
        $this->parentModel = isset($data['parentModel']) ? $data['parentModel'] : null;
        $this->parentModelId = isset($data['parentModelId']) ? $data['parentModelId'] : null;
        $this->parentModelIds = isset($data['parentModelIds']) ? $data['parentModelIds'] : null;
        $this->type = isset($data['type']) ? $data['type'] : null;
        $this->foreignKey = isset($data['foreignKey']) ? $data['foreignKey'] : null;
        $this->pivotTable = isset($data['pivotTable']) ? $data['pivotTable'] : null;
        $this->relatedKey = isset($data['relatedKey']) ? $data['relatedKey'] : null;
    }

    protected function buildBelongsToManyQuery($parentIds, $className, $pivotTable, $foreignKey, $relatedKey)
    {
        [$relation,$tableName] = $className::getRelationAndTable($className);

        $data = [
            'parentModelIds'=>$parentIds,
            'parentModel'=>null,
            'type'=>'belongsToMany',
            'foreignKey'=>$foreignKey,
            'pivotTable'=> $pivotTable,
            'relatedKey'=>$relatedKey
        ];

        $relation->setRelationdata($data);
        return $relation->setBelongsToMany();
    }

    public function buildWhere()
    {
        switch ($this->type) {
            case 'hasMany':
                return $this->where($this->foreignKey, $this->parentModelId);
                break;
            case 'belongsTo':
                return $this->where('id', $this->parentModel->{$this->foreignKey});
                break;
            case 'hasOne':
                return $this->where($this->foreignKey, $this->parentModelId);
                break;
            case 'belongsToMany':
                return $this->setBelongsToMany();
                break;
            
            default:
                return $this;
                break;
        }
    }

    protected function setBelongsToMany()
    {
        // left join favorites on(posts.id = favorites.post_id)
        // WHERE favorites.user_id = 1
        if (!empty($this->parentModelIds)) {
            return $this->join($this->pivotTable, 'id', $this->relatedKey)
                 ->where($this->pivotTable . '.' . $this->foreignKey, 'IN', $this->parentModelIds);
        } else {
            return $this->join($this->pivotTable, 'id', $this->relatedKey)
                 ->where($this->pivotTable . '.' . $this->foreignKey, $this->parentModelId);
        }
    }

    public function execSelect($tableName, $query, $toSql = null)
    {
        return parent::execSelect($tableName, $query, $toSql);
    }

    
    public function join($joinedTable, $tableKey, $foreignKey)
    {
        $this->query['join'][] = ' LEFT JOIN ' .$joinedTable . ' ON('. $this->tableName  . '.' . $tableKey. '=' . $joinedTable . '.' .$foreignKey . ') ';
        
        return $this;
    }

    public function appendPivot(array $fields)
    {
        foreach ($fields as $field) {
            if (empty($this->pivotTable)) {
                $this->query['pivot'] = $field;
            } else {
                $this->query['pivot'] = $this->pivotTable . '.' . $field;
            }
        }

        return $this;
    }
}
