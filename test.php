<?php
require_once 'vendor/autoload.php';

use ORM\Model\Post;
use ORM\Model\User;
use ORM\Model\Adapter\RDBAdapter;

$dbName = 'mysql';
RDBAdapter::init($dbName);

// $post = Post::findFirst();
$user = User::where('id', 1)->findFirst();
// 関連モデルのrelationModels追加テスト

// 動的プロパティのテスト
$posts = $user->posts;
var_dump($posts);

$posts = $user->favorites;
$user = $posts[0]->favorites;
var_dump($user);

// $user = $post->relation('user')->findFirst();
// echo $user->name;

// $posts = $user->relation('posts')->findMany();
// var_dump($posts) ;



$posts= $user->relation('favorites')->findMany();
var_dump($posts) ;
