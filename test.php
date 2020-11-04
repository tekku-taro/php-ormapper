<?php
require_once 'vendor/autoload.php';

use ORM\Model\Post;
use ORM\Model\User;
use ORM\Model\Adapter\RDBAdapter;

$dbName = 'mysql';
RDBAdapter::init($dbName);

$post = new Post();
$today = new DateTime();
$post->title = 'How to cook pizza';
$post->date = $today->format("Y-m-d");
$post->finished = false;

$post->saveNew();

var_dump($post);
// $injection = '; select * from users where id = 2;  --';
// // $post = Post::findFirst();
// $user = User::where('id', 1)->findFirst();
// $user = User::where('id', $injection)->findFirst();
