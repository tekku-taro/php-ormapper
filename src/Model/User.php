<?php
namespace ORM\Model;

use \ORM\Model\ActiveRecord\Model;

class User extends Model
{
    protected static $tableName = 'users';

    protected static $insertable = ['name','email','password'];

    protected static $hiddenField = ['password'];

    protected $relations = [
        'posts'=>[Post::class,'hasMany' ,'user_id'],
        'favorites'=>[Post::class,'belongsToMany', 'favorites', 'user_id', 'post_id']
    ];
}
