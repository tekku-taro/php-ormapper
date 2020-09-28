<?php
require_once 'vendor/autoload.php';

use ORM\Model\Post;
use ORM\Model\Adapter\RDBAdapter;

$config = [
    'CONNECTION'=>'mysql',
    'HOST'=>'localhost',
    'DB_NAME'=>'tasksdb',
    'USERNAME'=>'root',
    'PASSWORD'=>null,
];
RDBAdapter::init($config);

$post = new Post();
$data = [
    'title'=>'How to cook pizza553',
    'hidden'=>'test create from array',
    'finished'=>false
];

$post = $post->createFromArray($data);

// print_r($post->toArray());
// $data = [
//     'finished' => true
// ];
// $post->editWith($data)->saveUpdate();

// print_r($post->toArray());




// $post = Post::findFirst();
// print_r($post);

// $posts = Post::findMany();
// print_r($posts);


// $posts = Post::where('id', '1')->findMany();
// print_r($posts);

// $posts = Post::where('id', 'IN', [1,2])->findMany();
// print_r($posts);

// $posts = Post::where('id', '>', '2')->findMany();
// print_r($posts);

// $posts = Post::orderBy('id', 'DESC')->findMany();
// print_r($posts);


// $posts = Post::limit(2)->findMany();
// print_r($posts);


// $posts = Post::groupBy('author_id')->having('views > 4')->sum('views');
// print_r($posts);

// $posts = Post::count('views');
// print($posts);


// $posts = Post::max('views');
// print($posts);

// $exists = Post::where('author_id', 1)->Where('hidden', 'IS', null)->exists();
// print($exists);

// $posts = Post::where('body', 'good')->orWhere('finished', 1)->min('views');
// print_r($posts);

// $sql = Post::where('body', 'good')->orWhere('finished', 1)->toSql();
// print($sql);

// $posts = Post::find('id IN (1,5,10)');
// print_r($posts);

// $post = new Post();

// $post->title = 'How to cook pizza';
// $post->date = (new DateTime())->format("Y-m-d");
// $post->finished = false;

// $post->saveNew();

// echo "new post id: " .$post->id;
