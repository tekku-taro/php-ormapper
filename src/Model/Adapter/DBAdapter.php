<?php
namespace ORM\Model\Adapter;

interface DBAdapter
{
    public static function connect($config);

    public static function init();

    public static function getInstance();
    
    public static function getAdapter();

    public static function disconnect();
    
    public static function truncate($table, $dbName = null);

    public function insert($table, $query);
    public function select($table, $query);
    public function update($table, $query);
    public function delete($table, $query);

    public function raw($sql);
}
