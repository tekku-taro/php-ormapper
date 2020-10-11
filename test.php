<?php
require_once 'vendor/autoload.php';

use ORM\Model\Post;
use ORM\Model\User;
use ORM\Model\Adapter\RDBAdapter;

$dbName = 'mysql';
RDBAdapter::init($dbName);

$post = Post::findFirst();

$user = $post->relation('user')->findFirst();
echo $user->name;

$posts = $user->relation('posts')->findMany();
var_dump($posts) ;


$user = User::where('id', 1)->findFirst();
$posts= $user->relation('favorites')->findMany();
var_dump($posts) ;
