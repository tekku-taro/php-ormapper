<?php
namespace ORM\Model\ActiveRecord\Relationships;

use ORM\Model\ActiveRecord\QueryBuilder;
use ORM\Model\Adapter\RDBAdapter;

class Relation extends QueryBuilder
{
    protected $type;
    protected $foreignKey;
    protected $relatedKey;
    protected $pivot;
}
