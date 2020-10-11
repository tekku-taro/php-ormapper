<?php
namespace ORM\Model\DB;

use \ORM\Model\Adapter\RDBAdapter;
use ORM\Model\ActiveRecord\QueryBuilder;
use PDO;
use ReflectionClass;

class RDB
{
    protected $dbh;
    protected $dbName;

    public function __construct(PDO $dbh, $dbName)
    {
        $this->dbh = $dbh;
        $this->dbName = $dbName;
    }

    public function beginTrans()
    {
        $this->dbh->beginTransaction();
    }
    public function commit()
    {
        $this->dbh->commit();
    }
    public function rollBack()
    {
        $this->dbh->rollBack();
    }

    public function table($tableName)
    {
        $query = new QueryBuilder($tableName, $this->getModelName($tableName), $this->dbName);

        return $query;
    }

    protected function getModelName($tableName)
    {
        $table = preg_replace('/_([a-z])/', strtoupper('$1'), $tableName);
        return rtrim(ucfirst($table), 's');
    }

    public static function database($dbName)
    {
        $dbh = RDBAdapter::getInstance($dbName);

        $class = get_called_class();
        $reflection = new ReflectionClass($class);
        $rdb = $reflection->newInstanceArgs([$dbh, $dbName]);

        return $rdb;
    }
}
