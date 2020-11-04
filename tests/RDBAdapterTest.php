<?php

use PHPUnit\Framework\TestCase;
use ORM\Model\Adapter\RDBAdapter;

class RDBAdapterTest extends TestCase
{
    protected $adapter;

    public function setUp():void
    {
        $this->config = [
            'CONNECTION'=>'mysql',
            'HOST'=>'localhost',
            'DB_NAME'=>'tasksdb',
            'USERNAME'=>'root',
            'PASSWORD'=>null,
        ];
        $this->dbName = 'mysql';
    }

    public function tearDown():void
    {
    }

    protected function setupConnection()
    {
        $this->adapter = RDBAdapter::init($this->dbName);
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

    protected function getQuery()
    {
        return [
            'data'=>[
                'title'=>'test2',
                'body'=>'body2',
                'user_id'=>'2',
                'date'=>(new DateTime())->format('Y-m-d'),
                'views'=>23,
                'finished'=>0,
                'hidden'=>'secret data',
            ]
        ];
    }

    protected function fillTable($table)
    {
        $this->adapter->delete('posts', []);
        $data = [
            ['title'=>'test1', 'views'=>'1', 'finished'=>0, 'hidden'=>'secret' ],
            ['title'=>'test2', 'views'=>'2', 'finished'=>1, 'hidden'=>'secret' ],
            ['title'=>'test3', 'views'=>'3', 'finished'=>1, 'hidden'=>'public' ],
            [ 'title'=>'test4', 'views'=>'4', 'finished'=>1, 'hidden'=>'public' ],
            [ 'title'=>'test5', 'views'=>'5', 'finished'=>0, 'hidden'=>'public' ],
        ];
        foreach ($data as $row) {
            $query = ['data'=>$row];
            $id = $this->adapter->insert($table, $query);
        }
    }

    public function testConnect()
    {
        $dbh = RDBAdapter::connect($this->config);
        $expected = \PDO::class;
        $this->assertInstanceOf($expected, $dbh);
    }

    public function testInit()
    {
        $adapter = RDBAdapter::init($this->dbName);
        $expected = RDBAdapter::class;
        $this->assertInstanceOf($expected, $adapter);
    }

    public function testGetInstance()
    {
        $dbh = RDBAdapter::getInstance();
        $expected = \PDO::class;
        $this->assertInstanceOf($expected, $dbh);
    }

    public function testDisconnect()
    {
        $dbh = RDBAdapter::disconnect();
        $class = new \ReflectionClass(RDBAdapter::class);
        $property = $class->getProperty('dbHandlers');
        $property2 = $class->getProperty('defaultDb');
        $property->setAccessible(true);
        $property2->setAccessible(true);
        $dbhs = $property->getValue();

        $dbName = $property2->getValue();
        $this->assertNull($dbhs[$dbName]);
    }


    public function testRaw()
    {
        $this->setupConnection();
        $sql = 'test sql';
        $callback = $this->adapter->raw($sql);
        $expected = \Closure::class;
        $this->assertInstanceOf($expected, $callback);

        $result = $callback('before ', ' after');

        $expected = 'before test sql after';

        $this->assertEquals($expected, $result);
    }

    public function testInsert()
    {
        $this->setupConnection();

        $table = 'posts';
        $query = $this->getQuery();

        $id = $this->adapter->insert($table, $query);

        $this->assertTrue(is_numeric($id));
        
        $this->assertTrue($this->seeInDatabase($table, $query['data']));
        
        $this->disconnectAfterTest();
    }

    public function testUpdate()
    {
        $this->setupConnection();

        $table = 'posts';
        $query = $this->getQuery();

        $id = $this->adapter->insert($table, $query);
        print $id;
        $query = [
            'data'=>[
                'title'=>'test22',
                'body'=>'body22',
                'finished'=>1,
                'hidden'=>'secret updated data',
            ],
            'where'=>[
                ['AND','id = ?']
            ],
            'binds'=>[
                'where'=>[$id]
            ]
            ];
        print_r($query['data']);
        $result = $this->adapter->update($table, $query);

        
        $this->assertTrue($this->seeInDatabase($table, $query['data']));
        
        $this->disconnectAfterTest();
    }


    public function testSelect()
    {
        $this->setupConnection();

        $table = 'posts';
        $this->fillTable($table);

        $query = [
            'where'=>[
                ['AND','finished = ?']
            ],
            'select'=>['sum(views) AS sum'],
            'groupBy'=>'hidden',
            'having'=>[
                ['AND','sum > ?']
            ],
            'binds'=>[
                'where'=>[1],
                'having'=>[2]
            ]
        ];
        $expected = [7];

        $result = $this->adapter->select($table, $query)->fetchAll(PDO::FETCH_ASSOC);
        print_r($result);
        $result = array_map(function ($item) {
            return $item['sum'];
        }, $result);

        $this->assertEquals($expected, array_values($result));

        $query = [
            'limit'=>'2',
            'offset'=>'2',
            'orderBy'=>'title ASC',
            'select'=>['title']
        ];
        $expected = ['test3','test4'];

        $result = $this->adapter->select($table, $query)->fetchAll(PDO::FETCH_ASSOC);
        $result = array_map(function ($item) {
            return $item['title'];
        }, $result);

        // print_r($result);
        $this->assertEquals($expected, array_values($result));

        $query = [
            'where'=>[
                ['AND','finished = ?']
            ],
            'binds'=>[
                'where'=>[1]
            ],
            'orderBy'=>'title ASC',
            'select'=>['title']
        ];
        $expected = ['test2','test3','test4',];

        $result = $this->adapter->select($table, $query)->fetchAll(PDO::FETCH_ASSOC);
        $result = array_map(function ($item) {
            return $item['title'];
        }, $result);

        // print_r($result);
        $this->assertEquals($expected, array_values($result));
        
        $this->disconnectAfterTest();
    }

    public function testDelete()
    {
        $this->setupConnection();
        $this->adapter->delete('posts', []);

        $table = 'posts';
        $query = $this->getQuery();

        $id = $this->adapter->insert($table, $query);
       
        $this->assertTrue($this->seeInDatabase($table, $query['data']));
        $query = [
            'where'=>[
                ['AND','id = ?']
            ],
            'binds'=>[
                'where'=>[$id]
            ]
            ];
        $result = $this->adapter->delete($table, $query);
        
        $this->assertIsInt($result);
        
        $query = $this->getQuery();
        $this->assertFalse($this->seeInDatabase($table, $query['data']));

        $this->disconnectAfterTest();
    }
}
