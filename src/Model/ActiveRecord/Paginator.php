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


    public $nextChar = '&gt;';
    public $prevChar = '&lt;';
    public $lastChar = '&gt;&gt;';
    public $firstChar = '&lt;&lt;';
    public $separator = ' | ';

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

        $html = '<div class="pagination">' .
        implode($this->separator, $anchors) .
        '</div>';

        print $html;
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


        if ($curPage > 0) {
            $anchors[] = '<a href="'. $this->pageInfo['url'] . $queryString . '&curPage=0" >' .
            $this->firstChar . '</a>';
            $anchors[] = '<a href="'. $this->pageInfo['url'] . $queryString . '&curPage='.($curPage -1) .'" >' .
            $this->prevChar  . '</a>';
        }

        for ($i=0; $i < $this->pageInfo['max'] + 1; $i++) {
            if ($i == $curPage) {
                $anchors[] = ($i+1);
            } else {
                $anchors[] = '<a href="'. $this->pageInfo['url'] . $queryString . '&curPage='.$i .'" >' .
                ($i+1) . '</a>';
            }
        }

        if ($max > $curPage) {
            $anchors[] = '<a href="'. $this->pageInfo['url'] . $queryString . '&curPage='.($curPage+1) .'" >' .
            $this->nextChar  . '</a>';
            $anchors[] = '<a href="'. $this->pageInfo['url'] . $queryString . '&curPage='.$max.'" >' .
            $this->lastChar  . '</a>';
        }



        return $anchors;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }
}
