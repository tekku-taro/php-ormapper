<?php
namespace ORM\Model\Adapter;

class RDBAdapter implements DbAdapter
{
    protected static $dbh;
    protected static $config;


    public static function insert($table, $query)
    {
        $fields = array_keys($query['data']);
        $values = array_values($query['data']);
        $placeholders = array_map(function () {
            return '?';
        }, $values);
        $sql = 'INSERT INTO ' . $table . '(' . implode(",", $fields) .
        ') VALUES(' . implode(',', $placeholders) . ') ';

        $stmt = static::$dbh->prepare($sql);
        if ($stmt->execute($values)) {
            return static::$dbh->lastInsertId();
        }

        static::checkError($stmt);

        return false;
    }

    public static function bulkInsert($table, $query)
    {
        $fields = array_keys($query['data'][0]);

        foreach ($query['data'] as $row) {
            $arrayValues = array_values($row);
            $VALUES[] = '("' . implode('","', $arrayValues) . '") ';
        }

        $sql = 'INSERT INTO ' . $table . '(' . implode(",", $fields) .
        ') VALUES' . implode(',', $VALUES);

        $stmt = static::$dbh->prepare($sql);
        $result = $stmt->execute();
        static::checkError($stmt);

        return $stmt->rowCount();
    }


    public static function select($table, $query, $toSql = false)
    {
        if (empty($query['select'])) {
            $select = '*';
        } else {
            $select = implode(',', $query['select']) ;
        }
        $sql = 'SELECT '. $select .' FROM ' . $table .
        static::buildQuery($query) ;
        print $sql . PHP_EOL;

        if ($toSql) {
            return $sql;
        }

        $stmt = static::$dbh->prepare($sql);
        $result = $stmt->execute();
        if ($result) {
            static::checkError($stmt);
        }

        return $stmt;
    }


    public static function update($table, $query)
    {
        $fieldValues = [];
        $values = [];
        foreach ($query['data'] as $field => $value) {
            $fieldValues[] = $field . ' = ' . '?';
            $values[] = $value;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(",", $fieldValues)  .
        static::buildQuery($query) ;

        $stmt = static::$dbh->prepare($sql);
        $result = $stmt->execute($values);
        static::checkError($stmt);

        return $result;
    }
    
    public static function delete($table, $query)
    {
        $sql = 'DELETE FROM ' . $table .
        static::buildQuery($query) ;

        $stmt = static::$dbh->prepare($sql);
        $result = $stmt->execute();
        static::checkError($stmt);

        return $stmt->rowCount();
    }

    protected static function checkError($stmt)
    {
        if (!empty($stmt->errorInfo()[2])) {
            print $stmt->errorInfo()[2];
        }
    }

    protected static function buildWhere($where)
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

    protected static function buildQuery($query)
    {
        $sql = '';
        if (!empty($query['where'])) {
            $sql .= ' WHERE ';
            $sql .= static::buildWhere($query['where']) ;
        }
        if (!empty($query['orderBy'])) {
            $sql .= ' ORDER BY ' . $query['orderBy'];
        }
        if (!empty($query['groupBy'])) {
            $sql .= ' GROUP BY ' . $query['groupBy'];
            if (!empty($query['having'])) {
                $sql .= ' HAVING ' . $query['having'];
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

        static::$config = $config;
        
        return $db;
    }

    public static function init($config)
    {
        if (static::$dbh == null) {
            static::$dbh = static::connect($config);
        }

        return static::$dbh;
    }

    public static function getInstance()
    {
        if (static::$dbh == null) {
            static::$dbh = static::connect(static::$config);
        }

        return static::$dbh;
    }

    public static function disconnect()
    {
        static::$dbh = null;
    }

    public static function raw($sql)
    {
        return function ($before = '', $after = '') use ($sql) {
            return $before . $sql . $after;
        };
    }
}
