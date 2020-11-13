# Php ORMapper

PHPでリレーショナルデータベースのテーブル操作するための ActiveRecordタイプの**ORMapper** ライブラリです。モデルクラスを使って、レコードの保存、クエリビルダの作成と問い合わせができます。

## 使い方

## モデルクラス

*\ORM\Model\ActiveRecord\Model* クラスを継承する

```php
class Post extends Model
{
    // 操作するテーブル名
    protected static $tableName = 'posts';

    // createFromArray, editWithで挿入できるカラム名
    protected static $insertable = ['title', 'body', 'user_id'];

}
```

### リレーションの定義

**$relations** プロパティに連想配列で指定する

```php
protected static $relations = [
    # belongsTo, hasMany,hasOne の場合
	# 'リレーション名'=> [ リレーション先のクラス, 'belongsTo'/'hasMany'/'hasOne' , '外部キー' ]
    'posts'=>[Post::class,'hasMany' ,'user_id'],  
    # belongsToMany の場合
    # 'リレーション名'=> [ リレーション先のクラス, 'belongsToMany' , '中間テーブル名', '自モデルの外部キー', '関連モデルの外部キー' ]
    'favorites'=>[Post::class,'belongsToMany', 'favorites', 'user_id', 'post_id']
];
```



### レコードの新規作成

1. インスタンスを作成し、プロパティに値を代入して保存

   ```php
   $post = new Post();
   $post->title = 'How to cook pizza';
   $post->content = 'pizza recipe content';
   $post->finished = false;
   $post->saveNew();
   ```

   

2. **createFromArray**メソッドに、配列を渡して一括保存

```php
$post = new Post();
$data = [
    'title'=>'How to cook pizza2',
    'content'=>'test create from array',
    'finished'=>true
];
$post->createFromArray($data);

```

一括保存したいカラムはモデルクラスの**$insertable**プロパティに指定しておく

```php
protected static $insertable = ['title', 'body', 'user_id'];
```



### レコードの更新

1. インスタンスのプロパティに更新データを代入

   ```php
   $post = Post::where('id',1)->findFirst();
   $post->finished = true;
   $post->saveUpdate();
   ```

   

2. インスタンスの**editWith**メソッドに、更新データを配列で渡して更新

   ```php
   $post = Post::where('id',1)->findFirst();
   $data = [
       'finished' => true
   ];
   $post->editWith($data)->saveUpdate();
   ```

### レコードの削除

```php
$post = Post::where('id',1)->findFirst();
$post->delete();
```

## クエリビルダとクエリの実行

モデルクラスからクエリビルダ作成メソッドへ*static*にアクセスする

### クエリビルダの作成

必要なメソッドをメソッドチェーンで繋いでいく

```php
// where(カラム名, 値) 又は、 where(カラム名, oper, 値)
Post::where('id', 'IN', [1,2])
Post::where('id', '1')
// orWhere(カラム名, 値) 又は、 orWhere(カラム名, oper, 値)
Post::where('body', 'good')->orWhere('finished', 1)   

// orderBy(カラム名, ['DESC' | 'ASC'])
Post::orderBy('id', 'DESC')
// limit(取得数)
Post::limit(2)
// groupBy(カラム名) having(カラム名_集計関数名, oper, 値)
Post::groupBy('user_id')->having('views_sum', '>', 4)
```

### クエリの実行

クエリビルダを作成した後、あるいは単独で実行できるメソッド。

```php
// 複数のレコードを取得
$posts = Post::where('id', '>', '2')->findMany();
// 最初のレコードを取得
$posts = Post::where('id', '1')->findFirst();
// モデルクラスから単独で実行できる
$posts = Post::findMany();

// 集計用のメソッド
// count, max, min, sum
Post::count();
Post::count('views'); // カラムを指定

// レコードの存在確認 
$exists = Post::where('user_id', 1)->exists();
print($exists); // true or false
```

### ページネーション

レコードを指定した数づつ取得して表示

```php
// paginate(一度に取得するレコード数)
$posts = Post::paginate(10);
?>
    
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
    	<?php foreach ($posts as $post): ?>
            <tr>
            <td><?= $post->id ?></td>
            <td><?= $post->title ?></td>
            <td><?= $post->body ?></td>
            <td><?= $post->date ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php 
   // リンクを表示する
   $posts->showLinks(); 
?>

```



### リレーション先のモデルの取得

```php
// 関連モデルのデータを取得
$user = $post->relation('posts')->findFirst();
$posts = $user->relation('posts')->findMany();
// 関連モデルのカウント
$user = $user->countRelations(['posts','favorites']);
// counts['リレーション名']　でカウントにアクセス
print $user->counts['posts'];

// 関連モデルがあるレコードのみ取得
$users = User::hasRelations(['posts'])->findMany();

// 動的プロパティ
$posts = $user->posts; // $post->relation('posts')->findMany(); と同じ

// eagerloading N+1問題の解決
// with(['リレーション名'])
$user = User::with(['posts'])->findFirst();
$posts = $user->posts;
```

### 中間テーブル

**newPivot(), updatePivot(), removePivot()** を使って、中間テーブルのレコードを操作

```php
// 関連モデルのレコードIDを引数として渡す
$user = $user->relation('favorites')->newPivot(3);

// 中間テーブルに追加データを保存したい
$data = ['star'=>4]; 
$user = $user->relation('favorites')->newPivot(3, $data);

// 追加カラムを更新
$data = ['star'=>2];
$user = $user->relation('favorites')->updatePivot(2, $data);

// レコードを削除
$user->relation('favorites')->removePivot(3);

// 中間テーブルのカラムデータを一緒に取得
// appendPivot(['追加取得するカラム名'])
$posts= $user->relation('favorites')->appendPivot(['star'])->findMany();
```



## DB接続設定ファイル

*src/config* フォルダ内のDB接続設定ファイル(**DbConfig.php**)内にデータベース接続情報を登録する。

```php
// $data の databasesに、接続名をキーとして、接続情報を指定する
public static $data = [
    'default'=>'mysql',
    'databases'=>[
        'mysql'=>[
            'CONNECTION'=>'mysql',
            'HOST'=>'localhost',
            'DB_NAME'=>'tasksdb',
            'USERNAME'=>'root',
            'PASSWORD'=>'pass',
        ],
    ]

];
```

### データベースへ接続

モデルクラスを使う前に、RDBAdapterクラスのinitメソッドでDBへ接続する

```php
use ORM\Model\Adapter\RDBAdapter;
// 引数として利用する接続名を渡す（省略すると、default値が使われる）
RDBAdapter::init('mysql');
```



## ライセンス (License)

**Php ORMapper**は[MIT license](https://opensource.org/licenses/MIT)のもとで公開されています。

**Php ORMapper** is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).