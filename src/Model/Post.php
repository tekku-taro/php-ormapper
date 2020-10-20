<?php
namespace ORM\Model;

use \ORM\Model\ActiveRecord\Model;

class Post extends Model
{
    protected static $tableName = 'posts';

    protected static $insertable = ['title', 'body', 'user_id', 'date','views','finished','hidden'];

    protected static $hiddenField = ['hidden'];
    
    protected static $relations = [
        'user'=>[User::class,'belongsTo' ,'user_id'],
        'favorites'=>[User::class,'belongsToMany', 'favorites', 'post_id', 'user_id']
    ];
}
