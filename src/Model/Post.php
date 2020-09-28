<?php
namespace ORM\Model;

use \ORM\Model\ActiveRecord\Model;

class Post extends Model
{
    protected static $tableName = 'posts';

    protected static $insertable = ['title', 'body', 'author_id', 'date','views','finished','hidden'];

    protected static $hiddenField = ['hidden'];

    // public $id;

    // public $title;
  
    // public $body;
  
    // public $author_id;
  
    // public $date;
  
    // public $views;
  
    // public $finished;
}
