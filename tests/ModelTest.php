<?php

use PHPUnit\Framework\TestCase;
use ORM\Model\Adapter\RDBAdapter;
use ORM\Model\Post;

class ModelTest extends TestCase
{
    public static function setUpBeforeClass():void
    {
        $config = [
            'CONNECTION'=>'mysql',
            'HOST'=>'localhost',
            'DB_NAME'=>'tasksdb',
            'USERNAME'=>'root',
            'PASSWORD'=>null,
        ];
        RDBAdapter::init($config);
        RDBAdapter::delete('posts', []);
    }

    public function setUp():void
    {
        $today = new DateTime();
        $data = [
            ['id'=>1,'title'=>'title1', 'body'=>'bad','author_id'=>1,'date'=>$today->format('Y-m-d'),'views'=>2,'finished'=>0,'hidden'=>'hidden1'],
            ['id'=>2,'title'=>'title2', 'body'=>'good','author_id'=>1,'date'=>$today->format('Y-m-d'),'views'=>3,'finished'=>1,'hidden'=>null],
            ['id'=>3,'title'=>'title3', 'body'=>'good','author_id'=>2,'date'=>$today->format('Y-m-d'),'views'=>6,'finished'=>1,'hidden'=>'hidden3'],
        ];

        foreach ($data as $row) {
            $query = [
                'data'=>$row
            ];
            RDBAdapter::insert('posts', $query);
        }
    }

    public function tearDown():void
    {
        RDBAdapter::delete('posts', []);
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

    public static function tearDownBeforeClass():void
    {
        RDBAdapter::disconnect();
    }

    public function testFindFirst()
    {
        $post = Post::findFirst();
        $expected = Post::class;
        $this->assertInstanceOf($expected, $post);

        $expected = 'title1';

        $this->assertEquals($expected, $post->title);
    }

    public function testFindMany()
    {
        $posts = Post::findMany();

        $expected = Post::class;
        $this->assertInstanceOf($expected, $posts[0]);

        $expected = (new DateTime())->format('Y-m-d 00:00:00');

        $this->assertEquals($expected, $posts[0]->date);
    }

    public function testWhere()
    {
        $posts = Post::where('id', '1')->findMany();

        $expected = 'title1';

        $this->assertEquals($expected, $posts[0]->title);

        $posts = Post::where('id', 'IN', [1,2])->findMany();

        $expected = [1,2];

        $this->assertEquals($expected, [$posts[0]->id,$posts[1]->id]);

        $posts = Post::where('id', '>', '2')->findMany();

        $expected = 'title3';

        $this->assertEquals($expected, $posts[0]->title);
    }

    public function testOrderBy()
    {
        $posts = Post::orderBy('id', 'DESC')->findMany();

        $expected = [3,2,1];

        $this->assertEquals($expected, [$posts[0]->id,$posts[1]->id,$posts[2]->id]);
    }

    public function testLimit()
    {
        $posts = Post::limit(2)->findMany();

        $expected = 2;

        $this->assertEquals($expected, count($posts));
    }

    public function testGroupBy()
    {
        $result = Post::groupBy('author_id')->having('views > 4')->sum('views');

        $expected = [
            '1'=>[
                'author_id'=>1,
                'views'=>5
            ],
            '2'=>[
                'author_id'=>2,
                'views'=>6
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testCount()
    {
        $result = Post::count('views');

        $expected = 3;

        $this->assertEquals($expected, $result);
    }
    
    public function testMax()
    {
        $result = Post::max('views');

        $expected = 6;

        $this->assertEquals($expected, $result);
    }

    public function testExists()
    {
        $exists = Post::where('author_id', 1)->Where('hidden', 'IS', null)->exists();

        $expected = 1;

        $this->assertEquals($expected, $exists);
    }

    public function testOrWhere()
    {
        $result = Post::where('body', 'good')->orWhere('finished', 1)->min('views');
        
        $expected = 3;

        $this->assertEquals($expected, $result);
    }


    public function testWhereRaw()
    {
        $sql = 'body = "good" OR finished = "1"';
        $result = Post::whereRaw($sql, 'OR')->toSql();

        $expected = Post::where('body', 'good')->orWhere('finished', 1)->toSql();


        $this->assertEquals($expected, $result);
    }

    public function testToSql()
    {
        $sql = Post::where('body', 'good')->orWhere('finished', 1)->toSql();
        
        $expected = 'SELECT * FROM posts WHERE body = "good" OR finished = "1"';

        $this->assertEquals($expected, $sql);
    }

    public function testSaveNew()
    {
        $post = new Post();
        $today = new DateTime();
        $post->title = 'How to cook pizza';
        $post->date = $today->format("Y-m-d");
        $post->finished = false;

        $post->saveNew();

        $data = [
            'title'=>'How to cook pizza',
            'date'=>$today->format("Y-m-d") . " 00:00:00",
            'finished'=>false
        ];
        $this->assertTrue($this->seeInDatabase('posts', $data));
    }

    public function testToArray()
    {
        $post = Post::findFirst();
        $array = $post->toArray();
        $today = new DateTime();
        $expected =  ['id'=>1,'title'=>'title1', 'body'=>'bad','author_id'=>1,'date'=>$today->format('Y-m-d') . " 00:00:00",'views'=>2,'finished'=>0,'hidden'=>'hidden1'];

        $this->assertEquals($expected, $array);
    }

    public function testSaveUpdate()
    {
        $post = Post::findFirst();
        print_r($post->toArray());
        $post->finished = true;
        
        $post->saveUpdate();
        print_r($post->toArray());

        $this->assertTrue($this->seeInDatabase('posts', $post->toArray()));
    }

    public function testEditWith()
    {
        $post = Post::findFirst();
        print_r($post->toArray());
        $data = [
            'finished' => true
        ];
        $post->editWith($data)->saveUpdate();

        print_r($post->toArray());

        $this->assertTrue($this->seeInDatabase('posts', $post->toArray()));
    }


    public function testCreateFromArray()
    {
        $post = new Post();
        $data = [
            'title'=>'How to cook pizza2',
            'hidden'=>'test create from array',
            'finished'=>true
        ];

        $post->createFromArray($data);

        $this->assertTrue($this->seeInDatabase('posts', $data));
    }

    public function testDelete()
    {
        $post = Post::findFirst();
        print_r($post);
        $id = $post->id;

        $post->delete();

        $this->assertFalse($this->seeInDatabase('posts', ['id'=>$id]));
    }
}
