<?php

namespace BackBuilder\Bundle\GSABundle\Model;

use BackBuilder\BBApplication;

class Filter
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;
    /**
     * @var array
     */
    private $metatags;
    /**
     * @var array metatags + url information
     */
    private $filters;

    /**
     * @var LinkBuilder
     */
    private $linkBuilder;

    /**
     * @param BBApplication $bbapp
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Response $response, LinkBuilder $linkBuilder)
    {
        $this->linkBuilder = $linkBuilder;
        $this->response = $response;
        $this->metatags = $response->getMetaTags();
        $this->filters = array();
    }

    /**
     * We iterate on the metatags array
     * and add the url information containing requiredfields param
     * @return array
     */
    public function getFilters($metatagName = null)
    {
        if (empty($this->filters)) {
            $this->filters = $this->metatags;
            foreach($this->metatags as $name => $metatagValues)
            {
                foreach($metatagValues as $index => $metatagInfo) {
                    $this->filters[$name][$index]['url'] =
                        $this->linkBuilder->getLink(null,null,null,$name.':'.$metatagInfo['value']);
                }
            }
            $this->filters['all'] = array(
                'value' => 'all',
                'count' => $this->response->getTotalResults(),
                'url' => $this->linkBuilder->getLink(null,null,null,''),
            );
        }

        if (!is_null($metatagName)) {
            return isset($this->filters[$metatagName]) ? $this->filters[$metatagName] : '';
        }
        return $this->filters;
    }

    public function getResponse(){
        return $this->response;
    }
} 