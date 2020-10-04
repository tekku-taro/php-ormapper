<?php

use PHPUnit\Framework\TestCase;
use ORM\Model\Adapter\RDBAdapter;
use ORM\Model\DB\RDB;
use ORM\config\DbConfig;
use ORM\Model\ActiveRecord\QueryBuilder;

class RDBTest extends TestCase
{
    public $dbName = 'mysql';

    public function setUp():void
    {
        $this->setupConnection();
        $this->config = DbConfig::getDbInfo();
        RDBAdapter::changeDefault($this->dbName);
        RDBAdapter::delete('posts', []);
    }

    public function tearDown():void
    {
    }

    protected function setupConnection()
    {
        RDBAdapter::init($this->dbName);
    }

    protected function disconnectAfterTest()
    {
        RDBAdapter::disconnect();
    }

    protected function seeInDatabase($table, $data)
    {
        $sql = 'SELECT count(*) FROM ' . $table . ' WHERE ';
        foreach ($data as $key => $value) {
            $whereClause[] = $key . ' = "' . $value . '"';
        }

        $sql .= implode(' AND ', $whereClause);

        $dbh = RDBAdapter::getInstance();

        $stmt = $dbh->query($sql);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        return false;
    }

    protected function fillTable($table)
    {
        RDBAdapter::delete('posts', []);
        $data = [
            ['title'=>'test1', 'views'=>'1', 'finished'=>0, 'hidden'=>'secret' ],
            ['title'=>'test2', 'views'=>'2', 'finished'=>1, 'hidden'=>'secret' ],
            ['title'=>'test3', 'views'=>'3', 'finished'=>1, 'hidden'=>'public' ],
            [ 'title'=>'test4', 'views'=>'4', 'finished'=>1, 'hidden'=>'public' ],
            [ 'title'=>'test5', 'views'=>'5', 'finished'=>0, 'hidden'=>'public' ],
        ];
        foreach ($data as $row) {
            $query = ['data'=>$row];
            $id = RDBAdapter::insert($table, $query);
        }
    }

    public function testDatabase()
    {
        $rdb = RDB::database($this->dbName);
        $expected = RDB::class;
        $this->assertInstanceOf($expected, $rdb);
    }

    public function testTable()
    {
        $table = 'posts';
        $query = RDB::database($this->dbName)->table($table);
        $expected = QueryBuilder::class;
        $this->assertInstanceOf($expected, $query);
    }

    public function testCommit()
    {
        RDB::database($this->dbName)->beginTrans();

        $record = ['title'=>'test31', 'views'=>'31'];

        $query = ['data'=>$record];
        $id = RDBAdapter::insert('posts', $query, $this->dbName);


        RDB::database('mysql')->commit();

        $this->assertTrue($this->seeInDatabase('posts', $record));
    }

    public function testRollback()
    {
        RDB::database($this->dbName)->beginTrans();

        $record = ['title'=>'test31', 'views'=>'31'];

        $query = ['data'=>$record];
        $id = RDBAdapter::insert('posts', $query, $this->dbName);


        RDB::database('mysql')->rollBack();

        $this->assertFalse($this->seeInDatabase('posts', $record));
    }
}
