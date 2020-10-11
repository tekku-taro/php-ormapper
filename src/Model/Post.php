<?php
namespace ORM\Model;

use \ORM\Model\ActiveRecord\Model;

class Post extends Model
{
    protected static $tableName = 'posts';

    protected static $insertable = ['title', 'body', 'author_id', 'date','views','finished','hidden'];

    protected static $hiddenField = ['hidden'];
    
    protected $relations = [
        'user'=>[User::class,'belongsTo' ,'user_id'],
    ];
}
