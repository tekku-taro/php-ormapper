<?php
require_once 'vendor/autoload.php';

use ORM\Model\Post;
use ORM\Model\Adapter\RDBAdapter;
use ORM\Model\DB\RDB;

$dbName = 'mysql';
RDBAdapter::init($dbName);

// $today = new DateTime();
// $data = [
//     ['title'=>'title4', 'body'=>'bad','author_id'=>1,'date'=>$today->format('Y-m-d'),'views'=>2,'finished'=>0,'hidden'=>'hidden1'],
//     ['title'=>'title5', 'body'=>'good','author_id'=>1,'date'=>$today->format('Y-m-d'),'views'=>3,'finished'=>1,'hidden'=>null],
//     ['title'=>'title6', 'body'=>'good','author_id'=>2,'date'=>$today->format('Y-m-d'),'views'=>6,'finished'=>1,'hidden'=>'hidden3'],
// ];

// $count = Post::insertAll($data);
// echo 'count:'.$count.PHP_EOL;
// $data = [
//     'body'=>'soso'
// ];
// $post = Post::updateAll(['finished'=>false], $data);

$posts = Post::paginate(2);

foreach ($posts as $key => $post) {
    echo $post->title . PHP_EOL;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Paginator</title>
</head>

<body>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>title</th>
                <th>body</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $row): ?>
            <tr>
                <td><?= $row->id ?>
                </td>
                <td><?= $row->title ?>
                </td>
                <td><?= $row->body ?>
                </td>
                <td><?= $row->date ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php $posts->showLinks(); ?>
</body>

</html>