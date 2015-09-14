<?php

namespace BackBuilder\Bundle\GSABundle\Model;


class Response implements \JsonSerializable
{
    use JsonSerializableTrait;

    /**
     * @var string Version of the GSA
     */
    private $gsaVersion;
    /**
     * @var string Time taken by the query to be executed
     */
    private $time;
    /**
     * @var string the query string researched
     */
    private $searchString;

    /**
     * @var array parameters used for the query
     */
    private $parameters;

    /**
     * @var string the number of total results for this query
     */
    private $totalResults;

    /**
     * @var string the url to get the previous page results
     */
    private $previousPageUrl;

    /**
     * @var string the url to get the next page results
     */
    private $nextPageUrl;

    /**
     * @var array results for the query
     */
    private $results;

    /**
     * @var array of metaTags
     * used for dynamic navigation results
     */
    private $metaTags;

    /**
     * @var array of spelling suggestions
     */
    private $suggestions;

    /**
     * @var array of synonyms
     */
    private $synonyms;

    /**
     * @var array of partners links info
     */
    private $partnersLinks;


    public function __construct()
    {
        $this->parameters = array();
        $this->results = array();
        $this->metaTags = array();
        $this->suggestions = array();
        $this->synonyms = array();
        $this->totalResults = 0;
        $this->partnersLinks = array();
    }

    /**
     * @param string $gsaVersion
     */
    public function setGsaVersion($gsaVersion)
    {
        $this->gsaVersion = $gsaVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getGsaVersion()
    {
        return $this->gsaVersion;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters($key = null)
    {
        if (!is_null($key)) {
            return isset($this->parameters[$key]) ? $this->parameters[$key] : '';
        }
        return $this->parameters;
    }

    /**
     * @param string $searchString
     */
    public function setSearchString($searchString)
    {
        $this->searchString = $searchString;

        return $this;
    }

    /**
     * @return string
     */
    public function getSearchString()
    {
        return $this->searchString;
    }

    /**
     * @param string $time
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    public function addParameter($name,$value,$originalValue)
    {
        $this->parameters[$name] = array(
            'name' => $name,
            'value' => $value,
            'originalValue' => $originalValue,
        );

        return $this;
    }

    /**
     * @param string $totalResults
     */
    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;

        return $this;
    }

    /**
     * @return string
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * @param string $nexPageUrl
     */
    public function setNextPageUrl($nexPageUrl)
    {
        $this->nextPageUrl = $nexPageUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getNextPageUrl()
    {
        return $this->nexPageUrl;
    }

    /**
     * @param string $previousPageUrl
     */
    public function setPreviousPageUrl($previousPageUrl)
    {
        $this->previousPageUrl = $previousPageUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreviousPageUrl()
    {
        return $this->previousPageUrl;
    }

    /**
     * @param Result $result
     */
    public function addResult(Result $result)
    {
        $this->results[] = $result;

        return $this;
    }

    /**
     * @return array of Results
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return array of keyword
     */
    public function getKeyword()
    {
        if(!isset($this->getMetaTags()['keyword']))
            return;

        return $this->getMetaTags()['keyword'];
    }

    /**
     * @return relevant keyword / most used
     */
    public function getTopKeyword()
    {
        $keywordArr = $this->getKeyword();
        for ($i = 0; $i <= count($keywordArr); $i++) {
            if(strtoupper($keywordArr[$i]['value']) != strtoupper($this->getParameters('q')['value'])) {
                return $keywordArr[$i]['value'];
            }
        }
    }

    /**
     * @param $name of the meta
     * @param $value for the meta
     * @param $count results count for this value
     */
    public function addMetaTag($name, $value, $count)
    {
        if (!isset($this->metaTags[$name])) {
            $this->metaTags[$name] = array();
        }
        $this->metaTags[$name][] = array(
            'value' => $value,
            'count' => $count,
        );

        return $this;
    }

    public function getMetaTags($metaTagName = null)
    {
        if (!is_null($metaTagName)) {
            return isset($this->metaTags[$metaTagName]) ? $this->metaTags[$metaTagName] : '';
        }
        return $this->metaTags;
    }

    public function addSuggestion($suggestion) {
        $this->suggestions[] = $suggestion;

        return $this;
    }

    public function getSuggestions()
    {
        return $this->suggestions;
    }

    public function addSynonym($name,$query)
    {
        $this->synonyms[$name] = $query;
    }

    public function getSynonyms()
    {
        return $this->synonyms;
    }

    public function addPartnersLink($title,$url)
    {
        $this->partnersLinks[] = array('title' => $title, 'url' => $url);

        return $this;
    }

    public function getPartnersLinks()
    {
        return $this->partnersLinks;
    }

}