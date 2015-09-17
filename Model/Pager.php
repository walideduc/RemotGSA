<?php

namespace BackBee\Bundle\GSABundle\Model;


use BackBee\BBApplication;

class Pager
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var LinkBuilder
     */
    private $linkBuilder;

    /**
     * @var int the current page (index 0 base)
     */
    private $current;

    /**
     * the number of results in the page
     * @var int
     */
    private $pageSize;

    /**
     * The index of the result to be shown
     * @var int
     */
    private $resultIndex;

    /**
    * The max results amount
    */
    const MAX_RESULT_AMOUNT = 988;

    public function __construct(Request $request, Response $response, LinkBuilder $linkBuilder)
    {
        $this->response = $response;
        $this->linkBuilder = $linkBuilder;
        $this->pageSize =  (int)$request->getParameters('num');
        $this->resultIndex = (int) $request->getParameters('start');
        $this->current = floor($this->resultIndex / $this->pageSize);
    }

    /**
     * Returns an array of links for the pager
     * @param int $limit how many page links should we get
     * @return array
     */
    public function getPageLinksAround($limit = 12)
    {
        $first = $this->current - floor($limit/2);
        $first = $first>= 0 ? $first :0;
        $maxPages = ceil($this->response->getTotalResults() / $this->pageSize);
        $last = min($maxPages,(int)$first + $limit);

        $pageLinks = array();

        for($i=$first; $i<$last; $i++) {
            $pageLinks[$i+1] = $this->linkBuilder->getLink(null,($i*$this->pageSize),$this->pageSize);
        }
        return $pageLinks;
    }

    /**
     * Get the next page link
     * @return string url
     */
    public function getNextPageLink()
    {
        return $this->linkBuilder->getLink(null,$this->resultIndex+$this->pageSize,$this->pageSize);
    }

    /**
     * Get the previous page link
     * @return string url
     */
    public function getPrevPageLink()
    {
        return $this->linkBuilder->getLink(null,$this->resultIndex-$this->pageSize,$this->pageSize);
    }

    /**
     * Get the first page link
     * @return string url
     */
    public function getFirstPageLink()
    {
        return $this->linkBuilder->getLink(null,0,$this->pageSize);
    }

    /**
     * Get the last page link
     * @return string url
     */
    public function getLastPageLink()
    {
        return $this->linkBuilder->getLink(null,self::MAX_RESULT_AMOUNT, $this->pageSize);
    }

    /**
     * @return int the current page (index 1 based)
     */
    public function getCurrent()
    {
        return $this->current +1;
    }

    /**
     * @return int the max page (index 1 based)
     */
    public function getMaxPageLink()
    {
        $maxPages = floor($this->response->getTotalResults() / $this->pageSize);
        if($maxPages == 1) {
            return $maxPages;
        }
        else {
            return ceil(self::MAX_RESULT_AMOUNT / $this->pageSize);
        }
    }


}