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
    protected $model;
    /**
     * relation()を呼び出したモデルのID
     *
     * @var int
     */
    protected $modelId;
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

    public function setRelationdata($data)
    {
        $this->model = isset($data['model']) ? $data['model'] : null;
        $this->modelId = isset($data['modelId']) ? $data['modelId'] : null;
        $this->type = isset($data['type']) ? $data['type'] : null;
        $this->foreignKey = isset($data['foreignKey']) ? $data['foreignKey'] : null;
        $this->pivotTable = isset($data['pivotTable']) ? $data['pivotTable'] : null;
        $this->relatedKey = isset($data['relatedKey']) ? $data['relatedKey'] : null;
    }

    public function buildWhere()
    {
        switch ($this->type) {
            case 'hasMany':
                return $this->where($this->foreignKey, $this->modelId);
                break;
            case 'belongsTo':
                return $this->where('id', $this->model->{$this->foreignKey});
                break;
            case 'hasOne':
                return $this->where($this->foreignKey, $this->modelId);
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
             ->where($this->pivotTable . '.' . $this->foreignKey, $this->modelId);
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
