<?php
namespace ORM\Model\ActiveRecord;

interface Entity
{
    public function saveNew();
    public function saveUpdate();
    public static function createFromArray($data);
    public function editWith($data);
    public function delete();
    public function toArray();
    // public static function findFirst();
    // public static function findMany();
    // public static function paginate($limit);
    // public static function select($fields);
    // public static function max();
    // public static function min();
    // public static function count();
    // public static function sum();
    // public static function exists();
    // public static function where(...$args);
    // public static function orderBy($field, $order);
    // public static function limit($limit);
    // public static function groupBy(...$args);
    // public static function having(...$args);

    public function __set($name, $value);
    public function __get($name);

    public static function insertAll(array $recArray);
    public static function updateAll($search = [], $setData, $updateAllRecord = false);
    public static function deleteAll($search = [], $deleteAllRecord = false);
}
