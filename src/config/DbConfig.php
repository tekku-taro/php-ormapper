<?php
namespace ORM\config;

class DbConfig
{
    public static $data = [
        'default'=>'mysql',
        'databases'=>[
            'mysql'=>[
                'CONNECTION'=>'mysql',
                'HOST'=>'localhost',
                'DB_NAME'=>'tasksdb',
                'USERNAME'=>'root',
                'PASSWORD'=>null,
            ],
        
            'mysql2'=>[
                'CONNECTION'=>'mysql',
                'HOST'=>'localhost',
                'DB_NAME'=>'mydb',
                'USERNAME'=>'root',
                'PASSWORD'=>null,
            ],
        ]
        
    ];

    public static function getDbInfo($dbName = null)
    {
        if (empty($dbName)) {
            $dbName = static::$data['default'];
        }
        return static::$data['databases'][$dbName];
    }

    public static function getDefault()
    {
        return static::$data['default'];
    }
}
