<?php
namespace ORM\Model\Adapter;

interface DBAdapter
{
    public static function connect($config);

    public static function init();

    public static function getInstance();

    public static function disconnect();


    public static function insert($table, $query);
    public static function select($table, $query);
    public static function update($table, $query);
    public static function delete($table, $query);

    public static function raw($sql);
}
