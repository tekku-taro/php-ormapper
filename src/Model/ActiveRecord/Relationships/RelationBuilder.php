<?php
namespace ORM\Model\ActiveRecord\Relationships;

use ORM\Model\ActiveRecord\QueryBuilder;

trait RelationBuilder
{
    /**
     * 中間テーブルのキー配列
     *
     * @var array
     */
    public $pivot;


    protected $relations = [
        // 'リレーション名' => [モデル::class,'タイプ' ,'外部キー'],
        // 'リレーション名' => [モデル::class,'belongsToMany' , '中間テーブル', '自モデルのID', '関連モデルのID']
    ];
    
    protected $relationModels = [];
    
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
        $this->relationModels[$relationName] = $data['parentModel'];

        return $this;
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
        $tableName = static::guessTableName(substr(strrchr($className, '\\'), 1));
        $relation = new Relation($tableName, $className);

        $data = [
            'parentModelId'=>$this->id,
            'parentModel'=>$this,
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
