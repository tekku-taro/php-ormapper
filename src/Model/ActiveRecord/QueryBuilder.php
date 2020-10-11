<?php
namespace ORM\Model\ActiveRecord;

use ORM\Model\Adapter\RDBAdapter;
use ORM\Model\ActiveRecord\Paginator;

class QueryBuilder
{
    protected $tableName;
    protected $modelClass;
    protected $dbName;

    protected $query = [];

    protected $queryStringKeys = ['curPage'=>0, 'max'=>0];

    public function __construct($tableName, $modelClass, $dbName = null)
    {
        $this->tableName = $tableName;
        $this->modelClass = $modelClass;
        $this->dbName = $dbName;
    }

    public function execSelect($tableName, $query, $toSql = null)
    {
        return RDBAdapter::select($tableName, $query, $toSql, $this->dbName);
    }

    public function clearQuery()
    {
        $this->query = [];
    }

    public function findFirst()
    {
        $this->query['limit'] = 1;
        $stmt = $this->execSelect($this->tableName, $this->query);
        if ($stmt) {
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($result)) {
                return $this->modelClass::morph($result[0]);
            }
        }
        return false;
    }
    public function findMany()
    {
        $stmt = $this->execSelect($this->tableName, $this->query);
        if ($stmt) {
            return $this->modelClass::getCollection($stmt->fetchAll(\PDO::FETCH_ASSOC));
        }
        return false;
    }

    public function toSql()
    {
        return $this->execSelect($this->tableName, $this->query, true);
    }

    public function count($field = null)
    {
        return $this->aggregate('COUNT', $field);
    }


    public function sum($field)
    {
        return $this->aggregate('SUM', $field);
    }


    public function max($field)
    {
        return $this->aggregate('MAX', $field);
    }

    public function min($field)
    {
        return $this->aggregate('MIN', $field);
    }

    protected function aggregate($method, $field = null)
    {
        if (empty($field)) {
            $field = '*' . $field;
            $alius = $method;
        } else {
            $alius = $field;
        }
        $this->query['select'][] = ' ' . $method . '(' . $field . ') AS ' . $alius;

        $result = $this->execSelect($this->tableName, $this->query)->fetchAll(\PDO::FETCH_ASSOC);
        print_r($result);
        return $this->sortAggregtated($result, $alius);
    }

    protected function sortAggregtated($data, $alius)
    {
        if (count($data) == 1 && count($data[0]) == 1) {
            return $data[0][$alius];
        }
        $sorted = [];
        foreach ($data as $idx => $row) {
            if (array_key_exists($this->query['groupBy'], $row)) {
                $label = $row[$this->query['groupBy'] ];
            } else {
                $label = $idx;
            }
            foreach ($row as $field => $value) {
                $sorted[$label][$field] = $value;
            }
        }

        return $sorted;
    }

    public function exists(): bool
    {
        $count = $this->count();
        if (is_numeric($count) && $count > 0) {
            return true;
        }

        return false;
    }

    public function paginate($limit)
    {
        $pageInfo = $this->loadPageInfoFromQueryString($limit);
        $this->query['limit'] = $limit;
        $this->query['select'] = null;
        $this->query['offset'] = $pageInfo['curPage'] * $limit;
        $stmt = $this->execSelect($this->tableName, $this->query);
        if ($stmt) {
            $collection = $this->modelClass::getCollection($stmt->fetchAll(\PDO::FETCH_ASSOC));

            return new Paginator($collection, $pageInfo);
        }
        return false;
    }

    protected function loadPageInfoFromQueryString($limit)
    {
        $queries = [];
        $pageInfo = [
            'pageSize' => $limit,
            'curPage' => 0,
            'max' => 0
        ];
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $queries);
            if (isset($queries['curPage'])) {
                $pageInfo['curPage'] = $queries['curPage'];
            }
            if (isset($queries['max'])) {
                $pageInfo['max'] = $queries['max'];
            }
        } else {
            $pageInfo['max'] = floor($this->count() / $limit)  - 1;
        }

        
        $pageInfo['url'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        return $pageInfo;
    }

    public function select(array $fields)
    {
        foreach ($fields as $field) {
            $this->query['select'][] = $this->tableName . '.' . $field;
        }

        return $this;
    }

    public function where(...$args)
    {
        $whereClause = $this->createWhere(...$args);

        $this->query['where'][] = ['AND',$whereClause];
        
        return $this;
    }

    public function orWhere(...$args)
    {
        $whereClause = $this->createWhere(...$args);

        $this->query['where'][] = ['OR',$whereClause];
        
        return $this;
    }

    public function whereRaw($sql, $conjunct = 'AND')
    {
        $this->query['where'][] = [$conjunct, $sql];
        
        return $this;
    }

    protected function createWhere(...$args)
    {
        $whereClause = '';
        if (count($args) == 2) {
            $whereClause = $args[0] . ' = "' . $args[1] . '"';
        } elseif (count($args) > 2) {
            if (is_string($args[2]) || is_null($args[2])) {
                if (!is_null($args[2])) {
                    $args[2] = '"' . $args[2] . '"';
                } else {
                    $args[2] = 'NULL';
                }
                $whereClause = $args[0] . ' ' . $args[1] . ' ' . $args[2];
            } else {
                $whereClause = $args[0] . ' ' . $args[1] . ' ("' . implode('","', $args[2]) . '")';
            }
        }
        return $whereClause;
    }

    public function orderBy($field, $order = 'ASC')
    {
        $this->query['orderBy'] = $field . ' ' . $order;

        return $this;
    }

    public function limit($limit)
    {
        $this->query['limit'] = $limit;
        
        return $this;
    }

    public function groupBy($group)
    {
        $this->query['groupBy'] = $group;
        $this->query['select'][] = $group;
        return $this;
    }

    public function having($condition)
    {
        $this->query['having'] = $condition;
        
        return $this;
    }
}
