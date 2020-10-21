<?php

use PHPUnit\Framework\TestCase;
use ORM\Model\Adapter\RDBAdapter;
use ORM\Model\DB\RDB;
use ORM\Model\Post;
use ORM\Model\User;

class RelationBuilderTest extends TestCase
{
    public static function setUpBeforeClass():void
    {
        $dbName = 'mysql';
        RDBAdapter::init($dbName);
    }

    public function setUp():void
    {
        $today = new DateTime();
        $data = [
            ['id'=>1,'title'=>'title1', 'body'=>'bad','user_id'=>1,'date'=>$today->format('Y-m-d'),'views'=>2,'finished'=>0,'hidden'=>'hidden1'],
            ['id'=>2,'title'=>'title2', 'body'=>'good','user_id'=>1,'date'=>$today->format('Y-m-d'),'views'=>3,'finished'=>1,'hidden'=>null],
            ['id'=>3,'title'=>'title3', 'body'=>'good','user_id'=>2,'date'=>$today->format('Y-m-d'),'views'=>6,'finished'=>1,'hidden'=>'hidden3'],
        ];
        Post::insertAll($data);
        $data = [
            ['id'=>1,'name'=>'taro', 'email'=>'taro@post.com','password'=>'pass'],
            ['id'=>2,'name'=>'hanako', 'email'=>'hanako@post.com','password'=>'pass'],
        ];
        User::insertAll($data);

        $data = [
            ['id'=>1,'post_id'=>1, 'user_id'=>1,'star'=>3],
            ['id'=>2,'post_id'=>2, 'user_id'=>1,'star'=>5],
            ['id'=>3,'post_id'=>1, 'user_id'=>2,'star'=>2],
        ];

        foreach ($data as $row) {
            $query = [
                'data'=>$row
            ];
            RDBAdapter::insert('favorites', $query);
        }
    }

    public function tearDown():void
    {
        $rdb = new RDB(RDBAdapter::getInstance(), 'mysql');
        $rdb->table('posts')->truncate();
        $rdb->table('users')->truncate();
        RDBAdapter::truncate('favorites', 'mysql');
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

    public function testRelation()
    {
        $post = Post::findFirst();
        $user = $post->relation('user')->findFirst();
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($post->user_id, $user->id);

        $posts = $user->relation('posts')->findMany();

        $this->assertInstanceOf(Post::class, $posts[0]);
        $this->assertEquals($posts[0]->user_id, $user->id);
        $this->assertEquals($posts[1]->user_id, $user->id);

        $posts= $user->relation('favorites')->findMany();

        $this->assertInstanceOf(Post::class, $posts[0]);
        $this->assertEquals($posts[0]->user_id, $user->id);
        $this->assertEquals($posts[1]->user_id, $user->id);
    }

    public function testDynamicProperties()
    {
        // 動的プロパティのテスト
        $user = User::findFirst();
        $posts = $user->posts;
        $this->assertInstanceOf(Post::class, $posts[0]);
        $this->assertEquals($posts[0]->user_id, $user->id);
        $this->assertEquals($posts[1]->user_id, $user->id);
        
        $posts = $user->favorites;
        $user = $posts[0]->favorites;
        $this->assertInstanceOf(Post::class, $posts[0]);
        $this->assertEquals($posts[0]->user_id, $user->id);
        $this->assertEquals($posts[1]->user_id, $user->id);
    }

    
    public function testCountRelations()
    {
        $user = User::where('id', 1)->findFirst();
        $user = $user->countRelations(['posts','favorites']);
        $this->assertEquals(2, $user->counts['posts']);
        $this->assertEquals(2, $user->counts['favorites']);
    }
}
