<?php
namespace ORM\Model\Adapter;

use ORM\config\DbConfig;

class RDBAdapter implements DbAdapter
{
    protected static $defaultDb;
    protected static $dbHandlers = [];
    protected $binds = [];


    public function insert($table, $query, $dbName = null)
    {
        $fields = array_keys($query['data']);
        $placeholders = $this->getPlaceHolders(count($fields));
        $this->binds = array_values($query['data']);

        $sql = 'INSERT INTO ' . $table . '(' . implode(",", $fields) .
        ') VALUES(' . implode(',', $placeholders) . ') ';

        $stmt = static::getInstance($dbName)->prepare($sql);
        if ($stmt->execute($this->binds)) {
            $this->clearBind();
            return static::getInstance($dbName)->lastInsertId();
        }
        $this->clearBind();
        $this->checkError($stmt);

        return false;
    }

    public function getPlaceHolders($size)
    {
        $placeholders = [];
        for ($i=0; $i < $size; $i++) {
            $placeholders[] = "?";
        }
        return $placeholders;
    }

    public function bulkInsert($table, $query, $dbName = null)
    {
        $this->binds = [];
        $fields = array_keys($query['data'][0]);
        $qMarks = $this->getPlaceHolders(count($fields));
        foreach ($query['data'] as $row) {
            $arrayValues = array_values($row);
            array_push($this->binds, ...$arrayValues);
            $placeholders[] = '(' . implode(',', $qMarks) . ') ';
        }

        $sql = 'INSERT INTO ' . $table . '(' . implode(",", $fields) .
        ') VALUES' . implode(',', $placeholders);

        $stmt = static::getInstance($dbName)->prepare($sql);
        $result = $stmt->execute($this->binds);
        $this->clearBind();
        $this->checkError($stmt);

        return $stmt->rowCount();
    }


    public function select($table, $query, $toSql = false, $dbName = null)
    {
        if (empty($query['select'])) {
            $select = $table . '.' . '*';
        } else {
            $select = implode(',', $query['select']) ;
        }
        if (!empty($query['pivot'])) {
            $select .= ','. $query['pivot'];
        }

        $sql = 'SELECT '. $select .' FROM ' . $table .
        $this->buildQuery($query) ;
        print $sql . PHP_EOL;
        print_r($this->binds);
        // exit;
        if ($toSql) {
            return $sql;
        }

        $stmt = static::getInstance($dbName)->prepare($sql);
        $result = $stmt->execute($this->binds);
        $this->clearBind();
        if ($result) {
            $this->checkError($stmt);
        }

        return $stmt;
    }


    public function update($table, $query, $dbName = null)
    {
        $fieldValues = [];
        $values = [];
        foreach ($query['data'] as $field => $value) {
            $fieldValues[] = $field . ' = ' . '?';
            $this->binds[] = $value;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(",", $fieldValues)  .
        $this->buildQuery($query) ;

        $stmt = static::getInstance($dbName)->prepare($sql);
        $result = $stmt->execute($this->binds);
        $this->clearBind();
        $this->checkError($stmt);

        return $result;
    }
    
    public function delete($table, $query, $dbName = null)
    {
        $sql = 'DELETE FROM ' . $table .
        $this->buildQuery($query) ;

        $stmt = static::getInstance($dbName)->prepare($sql);
        $result = $stmt->execute($this->binds);
        $this->clearBind();
        $this->checkError($stmt);

        return $stmt->rowCount();
    }

    public static function truncate($table, $dbName = null)
    {
        $sql = 'TRUNCATE ' . $table;

        $stmt = static::getInstance($dbName)->prepare($sql);
        $result = $stmt->execute();

        if (!empty($stmt->errorInfo()[2])) {
            print $stmt->errorInfo()[2];
        }

        return $result;
    }

    protected function checkError($stmt)
    {
        if (!empty($stmt->errorInfo()[2])) {
            print $stmt->errorInfo()[2];
        }
    }

    protected function buildWhere($where)
    {
        $sql  = '';
        foreach ($where as $condition) {
            if (empty($sql)) {
                $sql = $condition[1];
            } elseif ($condition[0] == 'AND') {
                $sql .= ' AND ' . $condition[1];
            } elseif ($condition[0] == 'OR') {
                $sql .= ' OR ' . $condition[1];
            }
        }
        return $sql;
    }

    protected function buildQuery($query)
    {
        $sql = '';
        if (!empty($query['join'])) {
            $sql .= implode(' ', $query['join']) ;
        }
        if (!empty($query['where'])) {
            $sql .= ' WHERE ';
            $sql .= $this->buildWhere($query['where']) ;
            if (isset($query['binds']['where'])) {
                array_push($this->binds, ...$query['binds']['where']);
            }
        }
        if (!empty($query['orderBy'])) {
            $sql .= ' ORDER BY ' . $query['orderBy'];
        }
        if (!empty($query['groupBy'])) {
            $sql .= ' GROUP BY ' . $query['groupBy'];
            if (!empty($query['having'])) {
                $sql .= ' HAVING ' . $this->buildWhere($query['having']);
                if (isset($query['binds']['having'])) {
                    array_push($this->binds, ...$query['binds']['having']);
                }
            }
        }
        if (!empty($query['limit'])) {
            $sql .= ' LIMIT ' . $query['limit'];
            if (!empty($query['offset'])) {
                $sql .= ' OFFSET ' . $query['offset'];
            }
        }

        return $sql;
    }

    protected function setBind($value)
    {
        $this->binds[] = $value;
        return '?';
    }

    protected function clearBind()
    {
        $this->binds = [];
    }

    public static function connect($config)
    {
        $host =  $config['CONNECTION'] . ':host=' . $config['HOST'] . ';dbname=' . $config['DB_NAME'];
        $username = $config['USERNAME'];
        $password = $config['PASSWORD'];

        try {
            $db = new \PDO($host, $username, $password);
        } catch (\Exception $e) {
            throw new \Exception('Error creating a database connection. ');
        }
        
        return $db;
    }

    public static function getAdapter()
    {
        return new static;
    }
    public static function init($dbName = null)
    {
        if (empty($dbName)) {
            $dbName = static::$defaultDb;
        } else {
            static::$defaultDb = DbConfig::getDefault();
        }
        $config = DbConfig::getDbInfo();
        static::setInstance(static::connect($config), static::$defaultDb);

        return static::getAdapter();
    }

    public static function getInstance($dbName = null)
    {
        if (empty($dbName)) {
            $dbName = static::$defaultDb;
        }
        if (!isset(static::$dbHandlers[$dbName])) {
            $config = DbConfig::getDbInfo($dbName);
            $dbh = static::connect($config);
            static::setInstance($dbh, $dbName);
        }

        return static::$dbHandlers[$dbName];
    }

    protected static function setInstance($dbh, $dbName)
    {
        static::$dbHandlers[$dbName] = $dbh;
    }

    public static function disconnect($dbName = null)
    {
        if (empty($dbName)) {
            $dbName = static::$defaultDb;
        }
        static::setInstance(null, $dbName);
    }

    public static function changeDefault($dbName)
    {
        static::$defaultDb = $dbName;
    }


    public function raw($sql)
    {
        return function ($before = '', $after = '') use ($sql) {
            return $before . $sql . $after;
        };
    }
}
