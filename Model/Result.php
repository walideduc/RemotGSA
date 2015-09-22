<?php

namespace BackBee\Bundle\GSABundle\Model;


class Result implements \JsonSerializable
{
    use JsonSerializableTrait;
    /**
     * @var string url used for indexation
     */
    private $url;

    /**
     * @var string id used for indexation
     */
    private $id;

    /**
     * @var string title of the result
     */
    private $title;

    /**
     * @var string rank of the result
     */
    private $rank;

    /**
     * @var string date when the page was last crawled
     * only for pages that have been crawled within the past two days.
     */
    private $crawlDate;

    /**
     * @var id of the search appliance responsible for the result.
     */
    private $searchApplianceId;

    /**
     * @var array meta tags associated with this result
     */
    private $metaTags;

    /**
     * @var string snippet for the result
     */
    private $snippet;

    /**
     * @var language for the result;
     */
    private $language;

    /**
     * @var image for the result;
     */
    private $image;

    public function __construct()
    {
        $this->metaTags = array();
    }

    /**
     * @param string $crawlDate
     */
    public function setCrawlDate($crawlDate)
    {
        $this->crawlDate = $crawlDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getCrawlDate()
    {
        return $this->crawlDate;
    }

    /**
     * @param string $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * @return string
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @param \BackBee\Bundle\GSABundle\Model\id $searchApplianceId
     */
    public function setSearchApplianceId($searchApplianceId)
    {
        $this->searchApplianceId = $searchApplianceId;

        return $this;
    }

    /**
     * @return \BackBee\Bundle\GSABundle\Model\id
     */
    public function getSearchApplianceId()
    {
        return $this->searchApplianceId;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->getMetaTag('displayurl');
    }

    public function getUnrewritedUrl()
    {
        return $this->url;
    }

    /**
     * @param $name
     * @param $value
     */
    public function addMetaTag($name,$value)
    {
        $this->metaTags[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getMetaTags()
    {
        return $this->metaTags;
    }

    /**
     * @param string $name name of the meta
     * @return string the value for the meta or empty
     */
    public function getMetaTag($name)
    {
        return isset($this->metaTags[$name]) ? $this->metaTags[$name]:'';
    }

    /**
     * @param \BackBee\Bundle\GSABundle\Model\language $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return \BackBee\Bundle\GSABundle\Model\language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $snippet
     */
    public function setSnippet($snippet)
    {
        $this->snippet = $snippet;

        return $this;
    }

    /**
     * @return string
     */
    public function getSnippet()
    {
        return $this->snippet;
    }

    /**
     * @param string $name name of the meta
     * @return string the value for the meta or empty
     */
    public function getDuration()
    {
        if ($this->metaTags['duration']) {
             return date('i:s', $this->metaTags['duration']/1000);
        }
    }

    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->getMetaTag('image');
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getMetaTag('id');
    }


}