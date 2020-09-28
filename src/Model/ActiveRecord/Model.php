<?php
namespace ORM\Model\ActiveRecord;

use \ORM\Model\Adapter\RDBAdapter;
use \ORM\Model\ActiveRecord\QueryBuilder;

class Model implements Entity
{
    protected static $tableName;

    protected static $insertable = [];

    protected static $hiddenField = [];
    
    protected $originals = [];
    protected $dirties = [];
    

    protected function getPorperties()
    {
        // $reflectionClass = new \ReflectionClass($this);

        // $properties = [];

        // foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
        //     $propertyName = $property->getName();
        //     $properties[$propertyName] = $this->{$propertyName};
        // }
        return $this->dirties;
    }

    protected function initProperties($data)
    {
        $this->originals = $data;
    }

    public function __set($name, $value)
    {
        if (isset($this->originals[$name]) && $this->originals[$name] != $value) {
            $this->dirties[$name] = $value;
        } elseif (!isset($this->originals[$name])) {
            $this->dirties[$name] = $value;
        }
    }

    public function __get($name)
    {
        if (isset($this->dirties[$name])) {
            return $this->dirties[$name];
        } elseif (isset($this->originals[$name])) {
            return $this->originals[$name];
        }
    }

    protected function clearDirties()
    {
        $this->originals = array_merge($this->originals, $this->dirties);
        $this->dirties = [];
    }

    public function saveNew()
    {
        $properties = $this->getPorperties();

        $query = [
            'data' => $properties
        ];

        $id = RDBAdapter::insert(static::$tableName, $query);
        if ($id) {
            $this->clearDirties();
            $this->originals['id'] = $id;
        } else {
            throw new \ErrorException('model cannot save new record!');
        }
    }

    
    public function saveUpdate()
    {
        $properties = $this->getPorperties();

        if (empty($this->originals['id'])) {
            throw new \ErrorException('there\'s no id property set in this model');
        }
        if (empty($properties)) {
            return true;
        }

        $query = [
            'data' => $properties,
            'where'=>[
                ['AND','id = ' . $this->originals['id']]
            ]
        ];
        $result = RDBAdapter::update(static::$tableName, $query);
        if ($result) {
            $this->clearDirties();
        } else {
            throw new \ErrorException('model cannot update record!');
        }
    }

    public static function createFromArray($data)
    {
        $insertData =  static::pickInsertable($data);

        $query = [
            'data' => $insertData
        ];

        $id = RDBAdapter::insert(static::$tableName, $query);
        if ($id) {
            $insertData['id'] = $id;
            return static::morph($insertData);
        } else {
            throw new \ErrorException('model cannot save new record!');
        }
    }

    protected static function getCollection($recordSet)
    {
        $collection = [];
        foreach ($recordSet as  $record) {
            $collection[] = static::morph($record);
        }

        return $collection;
    }

    protected static function morph(array $data)
    {
        $class = new \ReflectionClass(get_called_class());

        $entity = $class->newInstance();
    
        $entity->originals = $data;

        return $entity;
    }

    protected static function pickInsertable($data)
    {
        $insertData = [];
        foreach ($data as $key => $value) {
            if (array_search($key, static::$insertable) !== false) {
                $insertData[$key] = $value;
            }
        }
        return $insertData;
    }

    public function editWith($data)
    {
        $insertData =  static::pickInsertable($data);
        $this->checkDirty($insertData);

        return $this;
    }
    
    protected function checkDirty($new)
    {
        foreach ($new as $key => $oldValue) {
            if (!isset($this->originals[$key]) || $this->originals[$key] != $oldValue) {
                $this->dirties[$key] = $new[$key];
            }
        }
    }
    
    public function delete()
    {
        if (empty($this->originals['id'])) {
            throw new \ErrorException('there\'s no id property set in this model');
        }
        $whereClause = ['AND','id = ' . $this->originals['id']];
        $query['where'][] = $whereClause;
        return RDBAdapter::delete(static::$tableName, $query);
    }

    public static function __callStatic($method, $args)
    {
        $class = QueryBuilder::class;
        if (is_callable([$class, $method])) {
            return static::buildQuery($method, ...$args);
        }
        $class = get_called_class();
        if (is_callable([$class, $method])) {
            return static::$method(...$args);
        }
    }

    public static function buildQuery($method, ...$args)
    {
        $query = new QueryBuilder(static::$tableName, get_called_class());

        return $query->{$method}(...$args);
    }

    public static function insertAll(array $recArray)
    {
        foreach ($recArray as $data) {
            $insertArray[] =  static::pickInsertable($data);
        }

        $query = [
            'data' => $insertArray,
            'bulk'=>true,
        ];

        return RDBAdapter::bulkInsert(static::$tableName, $query);
    }

    public static function updateAll($search = [], $setData, $updateAllRecord = false)
    {
        $query = [];
        foreach ($search as $field => $value) {
            if ($value === true) {
                $value = 1;
            } elseif ($value === false) {
                $value = 0;
            }
            $query['where'][] = ['AND', $field . ' = ' . (string) $value];
        }
        if (empty($query) && $updateAllRecord == false) {
            return 0;
        }

        $query['data'] = $setData;

        return RDBAdapter::update(static::$tableName, $query);
    }

    public static function deleteAll($search = [], $deleteAllRecord = false)
    {
        $query = [];
        foreach ($search as $field => $value) {
            $query['where'][] = ['AND', $field . ' = ' . $value];
        }
        if (empty($query) && $deleteAllRecord == false) {
            return 0;
        }

        return RDBAdapter::delete(static::$tableName, $query);
    }

    public function toArray()
    {
        $currentProps = array_merge($this->originals, $this->dirties);

        return $currentProps;
    }
}
