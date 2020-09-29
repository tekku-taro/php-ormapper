<?php
namespace ORM\Model;

use \ORM\Model\ActiveRecord\Model;

class User extends Model
{
    protected static $tableName = 'users';

    protected static $insertable = ['name','email','password'];

    protected static $hiddenField = ['password'];
}
