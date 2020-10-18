<?php
require_once 'vendor/autoload.php';

use ORM\Model\Post;
use ORM\Model\User;
use ORM\Model\Adapter\RDBAdapter;

$dbName = 'mysql';
RDBAdapter::init($dbName);
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


// $post = Post::findFirst();
$user = User::where('id', 1)->findFirst();

// pivot操作のメソッドが必要
// $user->relation('favorites')->couple($post_id,$data);
// $user->relation('favorites')->deCouple($post_id);
