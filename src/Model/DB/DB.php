<?php
namespace ORM\Model\DB;

interface DB
{
    public function beginTrans();
    public function commit();
    public function rollBack();
    public function database();
}
