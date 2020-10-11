<?php
namespace ORM\Model\ActiveRecord\Relationships;

use ORM\Model\ActiveRecord\QueryBuilder;
use ORM\Model\Adapter\RDBAdapter;

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

    public function getType()
    {
        return $this->type;
    }

    protected function morphToModel($stmt, $useCollect = false)
    {
        $result = parent::morphToModel($stmt, $useCollect);
        if ($this->type == 'belongsToMany') {
            $data = [
                'foreignKey'=>$this->foreignKey,
                'relatedKey'=>$this->relatedKey ,
                'parentModel'=>$this->parentModel,
                'parentModelId'=>$this->parentModelId,
            ];
        } else {
            $data = [
                'parentModel'=>$this->parentModel,
            ];
        }

        if (!$useCollect) {
            $relationName = $this->getRelationName($result);
            return $result->setPivotAndRelationModel($data, $relationName);
        // return $this->addRelatedModels($result, $data, $relationName);
        } else {
            $relationName = $this->getRelationName($result[0]);
            return  array_map(function ($entity) use ($relationName,$data) {
                return $entity->setPivotAndRelationModel($data, $relationName);
            // return $this->addRelatedModels($entity, $data, $relationName);
            }, $result);
        }
    }

    protected function getRelationName($entity)
    {
        //リレーション名を調べる　$relationName
        $relationName = $this->getOppositeRelationName($entity);
        if (empty($relationName)) {
            $relationName = $this->parentModel::getTableName();
        }

        return $relationName;
    }

    // protected function addRelatedModels($entity, $data, $relationName)
    // {
    //     // 中間テーブルのキー配列を格納
    //     $this->pivotTable . '.' . $this->foreignKey, $this->modelId
    //     $entity->pivot = [
    //         $this->foreignKey => $this->parentModelId,
    //         $this->relatedKey => $entity->{$this->relatedKey}
    //     ];

    //     $entity->setPivotAndRelationModel($data, $relationName);
    //     // 呼び出し元のモデルを格納
    //     $entity->relationModels[$relationName] = $this->parentModel;

    //     return $entity;
    // }

    protected function getOppositeRelationName($entity)
    {
        $oppositeType = $this->getOpossiteType();

        return $entity->getRelationName(get_class($this->parentModel), $oppositeType);
        // foreach ($entity->relations as $relationName => $relationData) {
        //     // 'user'=>[User::class,'belongsTo' ,'user_id'],
        //     [$className,$type] = $relationData;
        //     if (get_class($entity) == $className && $type == $oppositeType) {
        //         return $relationName;
        //     }
        // }

        // return null;
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
        $this->type = isset($data['type']) ? $data['type'] : null;
        $this->foreignKey = isset($data['foreignKey']) ? $data['foreignKey'] : null;
        $this->pivotTable = isset($data['pivotTable']) ? $data['pivotTable'] : null;
        $this->relatedKey = isset($data['relatedKey']) ? $data['relatedKey'] : null;
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
        return $this->join($this->pivotTable, 'id', $this->relatedKey)
             ->where($this->pivotTable . '.' . $this->foreignKey, $this->parentModelId);
    }

    public function execSelect($tableName, $query, $toSql = null)
    {
        return parent::execSelect($tableName, $query, $toSql = null);
    }

    
    public function join($joinedTable, $tableKey, $foreignKey)
    {
        $this->query['join'][] = ' LEFT JOIN ' .$joinedTable . ' ON('. $this->tableName  . '.' . $tableKey. '=' . $joinedTable . '.' .$foreignKey . ') ';
        
        return $this;
    }
}
