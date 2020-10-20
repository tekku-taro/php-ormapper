<?php
namespace ORM\Model\ActiveRecord\Relationships;

trait RelationBuilder
{
    /**
     * 中間テーブルのキー配列
     *
     * @var array
     */
    public $pivot;


    protected static $relations = [
        // 'リレーション名' => [モデル::class,'タイプ' ,'外部キー'],
        // 'リレーション名' => [モデル::class,'belongsToMany' , '中間テーブル', '自モデルのID', '関連モデルのID']
    ];
    
    protected $relationModels = [];

    public $counts;
    
    public function countRelations($relationNames)
    {
        $relationCounts = [];
        foreach ($relationNames as $relationName) {
            $relation = $this->relation($relationName);
            $relationCounts[$relationName] = $relation->count();
        }
        return $this->setRelationCounts($relationCounts);
    }

    public function setPivotAndRelationModel($data, $relationName)
    {
        // 中間テーブルのキー配列を格納
        // $this->pivotTable . '.' . $this->foreignKey, $this->modelId
        if (isset($data['relatedKey'])) {
            $this->pivot = [
                $data['foreignKey'] => $data['parentModelId'],
                $data['relatedKey'] => $this->id
            ];

            $data['parentModel']->pivot = $this->pivot;
        }


        // 呼び出し元のモデルを格納
        if (isset($data['parentModel'])) {
            $this->relationModels[$relationName] = $data['parentModel'];
        }
        return $this;
    }
    
    public function setRelationModels($relationModels)
    {
        // 関連モデルを格納
        foreach ($relationModels as $relationName => $modelArray) {
            if (isset($this->relationModels[$relationName])) {
                array_push($this->relationModels[$relationName], ...$modelArray);
            } else {
                $this->relationModels[$relationName] = $modelArray;
            }
        }
        return $this;
    }
    
    public function setRelationCounts($relationCounts)
    {
        // 関連モデルを格納
        foreach ($relationCounts as $relationName => $count) {
            $this->counts[$relationName] = $count;
        }
        return $this;
    }

    public static function getRelationData($relationName)
    {
        // 'user'=>[User::class,'belongsTo' ,'user_id'],
        if (isset(static::$relations[$relationName])) {
            return static::$relations[$relationName];
        }
        return null;
    }

    public function getRelationName($relationClass, $type)
    {
        foreach ($this->relations as $relationName => $relationData) {
            // 'user'=>[User::class,'belongsTo' ,'user_id'],
            [$className,$relationType] = $relationData;
            if ($relationClass == $className && $type == $relationType) {
                return $relationName;
            }
        }

        return null;
    }

    public static function getRelationAndTable($className)
    {
        $tableName = static::guessTableName(substr(strrchr($className, '\\'), 1));
        return [new Relation($tableName, $className),$tableName];
    }

    //動的プロパティ
    public function checkRelations($name)
    {
        if (array_key_exists($name, $this->relations)) {
            if (empty($this->relationModels[$name])) {
                // インスタンスがまだできていなければ、relations情報からクエリビルダで作成して返す
                $relation = $this->relation($name);

                if (in_array($relation->getType(), ['belongsTo','hasOne'])) {
                    $this->relationModels[$name] = $relation->findFirst();
                } else {
                    $this->relationModels[$name] = $relation->findMany();
                }
            }
            
            // あれば、relationModelsからインスタンスを取得して返す
            return $this->relationModels[$name];
        }
        return null;
    }

    public function relation($relationName)
    {//Post::class,'hasMany' ,'user_id'
        $data  = static::$relations[$relationName];
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

    protected function hasMany($className, $foreignKey = null)
    {
        $relation = $this->initRelation($className, $foreignKey, 'hasMany');
        return $relation->buildWhere();
    }

    protected function belongsTo($className, $foreignKey = null)
    {
        $relation = $this->initRelation($className, $foreignKey, 'belongsTo');
        return $relation->buildWhere();
    }

    protected function hasOne($className, $foreignKey = null)
    {
        $relation = $this->initRelation($className, $foreignKey, 'hasOne');
        return $relation->buildWhere();
    }

    protected function belongsToMany($className, $pivotTable, $foreignKey, $relatedKey)
    {
        $relation = $this->initRelationWithPivot($className, $pivotTable, $foreignKey, $relatedKey, 'belongsToMany');

        return $relation->buildWhere();
    }

    protected function initRelationWithPivot($className, $pivotTable, $foreignKey, $relatedKey, $typeName)
    {
        [$relation,$tableName] = static::getRelationAndTable($className);

        $data = [
            'parentModelId'=>$this->id,
            'parentModel'=>$this,
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
        [$relation,$tableName] = static::getRelationAndTable($className);

        $data = [
            'parentModelId'=>$this->id,
            'parentModel'=>$this,
            'type'=>$typeName,
            'foreignKey'=>$this->checkForeign($tableName, $foreignKey)
        ];

        $relation->setRelationdata($data);

        return $relation;
    }

    protected function checkForeign($tableName, $foreignKey = null)
    {
        if (empty($foreignKey)) {
            return rtrim($tableName, 's') . '_id';
        }

        return $foreignKey;
    }
}
