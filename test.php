<?php
require_once 'vendor/autoload.php';

use ORM\Model\Post;
use ORM\Model\User;
use ORM\Model\Adapter\RDBAdapter;

$dbName = 'mysql';
RDBAdapter::init($dbName);

// $post = Post::findFirst();
$user = User::where('id', 1)->findFirst();
// appendPivot()
$posts= $user->relation('favorites')->appendPivot(['star'])->findMany();
var_dump($posts) ;

// eagerloading N+1
$user = User::with(['posts'])->where('id', 1)->findFirst();
var_dump($user);
$user = User::with(['posts'])->where('id', 2)->findFirst();
var_dump($user);
// 複数の場合
$users = User::with(['posts'])->findMany();
var_dump($users);
// 複数の場合（belongsToMany）
$users = User::with(['favorites'])->findMany();
var_dump($users);


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
