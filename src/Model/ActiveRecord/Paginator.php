<?php
namespace ORM\Model\ActiveRecord;

class Paginator implements \IteratorAggregate
{
    public $collection;

    public $pageInfo = [
        'pageSize' => 20,
        'currentPage'=>0,
        'maxPage'=>0,
    ];


    public $nextChar = '>';
    public $prevChar = '<';
    public $lastChar = '>>';
    public $firstChar = '<<';
    public $separator = '|';

    public function __construct(array $collection, $pageInfo = [])
    {
        $this->collection = $collection;
        $this->pageInfo = $pageInfo + $this->pageInfo;
    }

    public function setPagination($pageInfo)
    {
        $this->pageInfo = $pageInfo + $this->pageInfo;
    }

    public function showLinks()
    {
        $anchors = $this->generateAnchors();
        if (!$anchors) {
            return '';
        }

        $html = '<div class="pagination"><ul>' .
        implode($this->pageInfo['separator'], $anchors) .
        '</ul></div>';

        return $html;
    }

    protected function generateAnchors()
    {
        if ($this->pageInfo['max'] == 0) {
            return false;
        }
        $max = $this->pageInfo['max'];
        $curPage = $this->pageInfo['curPage'];
        $anchors = [];
        $queryString = '?' . 'max=' .$max;

        $anchors[] = '<li><a href="'. $this->pageInfo['url'] . $queryString . '&curPage=0" >' .
        $this->firstChar . '</a></li>';
        if ($curPage > 0) {
            $anchors[] = '<li><a href="'. $this->pageInfo['url'] . $queryString . '&curPage='.($curPage -1) .'" >' .
            $this->prevChar  . '</a></li>';
        }

        for ($i=0; $i < $this->pageInfo['max'] + 1; $i++) {
            $anchors[] = '<li><a href="'. $this->pageInfo['url'] . $queryString . '&curPage='.$i .'" >' .
            ($i+1) . '</a></li>';
        }

        if ($max > $curPage) {
            $anchors[] = '<li><a href="'. $this->pageInfo['url'] . $queryString . '&curPage='.($curPage+1) .'" >' .
            $this->nextChar  . '</a></li>';
        }

        $anchors[] = '<li><a href="'. $this->pageInfo['url'] . $queryString . '&curPage='.$max.'" >' .
        $this->lastChar  . '</a></li>';

        return $anchors;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }
}
