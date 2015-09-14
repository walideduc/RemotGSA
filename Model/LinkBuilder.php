<?php

namespace BackBuilder\Bundle\GSABundle\Model;


class LinkBuilder
{
    private $gsaRequest;

    public function __construct(Request $gsaRequest,$pathInfo)
    {
        $this->gsaRequest = $gsaRequest;
        $this->pathInfo = $pathInfo;
    }

    public function getLink($query = null,$startIndex = null,$nbResultsPerPage = null,$requiredFields = null)
    {
        $params = array(
            'q' => is_null($query) ? $this->gsaRequest->getParameters('q') :$query,
            'start' => is_null($startIndex) ? $this->gsaRequest->getParameters('start') : $startIndex,
            'num' => is_null($nbResultsPerPage) ? $this->gsaRequest->getParameters('num') : $nbResultsPerPage,
            'type' => is_null($requiredFields) ? $this->gsaRequest->getParameters('requiredfields') : $requiredFields,
            'filter' => 0
        );
        if ( empty($params['requiredfields']) )
        {
            unset($params['requiredfields']);
        }
        return urldecode($this->pathInfo.'?'.http_build_query($params));
    }
}