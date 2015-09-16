<?php

namespace BackBee\Bundle\GSABundle\Model;

use GuzzleHttp\Client;

class Request
{
    private $serverAddress;
    private $serverPort;
    private $defaultParams;
    private $parameters;
    private $requiredFields;
    private $partialFields;
    private $format;
    private $mandatoryParameters;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @param string $serverAddress
     * @param string $serverPort
     * @param array $defaultParams
     */
     public function __construct(Client $httpClient, $serverAddress, $serverPort, $defaultParams)
    {

        $this->serverAddress = $serverAddress;
        $this->serverPort = $serverPort;
        $this->httpClient = $httpClient;

        $this->defaultParams = $defaultParams;
        $this->parameters = array();
        $this->requiredFields = array();
        $this->partialFields = array();
        $this->setResultsFormat('xml');
        $this->mandatoryParameters = array(
            'client',
            'output',
            'q',
            'site',
        );
        var_dump("Request / __construct");//die;
    }

    /**
     * Resets the request parameters
     */
    public function reset()
    {
        $this->parameters = array();
        $this->requiredFields = array();
        $this->partialFields = array();
        $this->setResultsFormat('xml');

        return $this;
    }

    /**
     * @return array
     */
    public function getMandatoryParameters()
    {
        return $this->mandatoryParameters;
    }

    /**
     * @return array
     */
    public function getDefaultParams($key = null)
    {
        if (!is_null($key)) {
            return isset($this->defaultParams[$key]) ? $this->defaultParams[$key] : '';
        }
        return $this->defaultParams;
    }

    /**
     * @param string $serverAddress
     */
    public function setServerAddress($serverAddress)
    {
        $this->serverAddress = $serverAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getServerAddress()
    {
        return $this->serverAddress;
    }

    /**
     * @param string $serverPort
     */
    public function setServerPort($serverPort)
    {
        $this->serverPort = $serverPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getServerPort()
    {
        return $this->serverPort;
    }

    /**
     * Return a parameter that will be use for the request.
     * if no key is specified, all parameters are returned
     * Parameters set up by the user are merged with defaults one.
     * @param string $key
     * @return mixed
     */
    public function getParameters($key = null)
    {
        $parameters = array_merge($this->parameters,array_diff_key($this->defaultParams,$this->parameters));
        if ($key !== null) {
            return isset($parameters[$key]) ? $parameters[$key]: '';
        }

        return $parameters;
    }

    public function setParameters(array $params)
    {
        $this->addParameters($params);
    }

    /**
     * Merge old parameters with new ones
     * Old parameters are overwritten by new ones.
     * @param array $params
     * @return $this
     */
    private function addParameters(array $params)
    {
        $params = $this->customParameters($params);
        $this->parameters = array_merge($params,array_diff_key($this->parameters,$params));
        return $this;
    }

    public function customParameters(array $params)
    {
        $customParamsArray = array('requiredfields' => 'type');

        if (array_key_exists('type', $params)) {
            $params['requiredfields'] = $params['type'];
            unset($params['type']);
        }

        return $params;
    }

    /**
     * Build the entire query string
     * @return string
     */
    private function buildQueryString()
    {
        $queryString = http_build_query($this->getParameters());

        return $queryString;
    }

    /**
     * Build the querystring part for required fields
     * format is: name:value
     * and operator: .
     * or operator: |
     * @return string
     */
    private function buildRequiredFieldsQuery()
    {
        $requiredFieldsQuery = '';
        if(0 < count($this->requiredFields)) {
            foreach($this->requiredFields as $requireFieldName => $fieldValues) {
                $iter = new \CachingIterator(new \ArrayIterator($fieldValues));
                foreach($iter as $fieldValue) {
                    $requiredFieldsQuery .= $requireFieldName;
                    if (!is_null($fieldValue)) {
                        $requiredFieldsQuery .= ':'.$fieldValue;
                    }
                    if($iter->hasNext()) {
                        $requiredFieldsQuery .= '.';
                    }
                }
            }
        }

        return $requiredFieldsQuery;
    }
    /**
     * Build the querystring part for partial fields
     * format is: name:value
     * and operator: .
     * or operator: |
     * @return string
     */
    private function buildPartialFieldsQuery()
    {
        $partialFieldsQuery = '';
        if(0 < count($this->partialFields)) {
            foreach($this->partialFields as $partialFieldName => $fieldValues) {
                $iter = new \CachingIterator(new \ArrayIterator($fieldValues));
                foreach($iter as $fieldValue) {
                    $partialFieldsQuery .= $partialFieldName;
                    if (!is_null($fieldValue)) {
                        $partialFieldsQuery .= ':'.$fieldValue;
                    }
                    if($iter->hasNext()) {
                        $partialFieldsQuery .= '.';
                    }
                }
            }
        }

        return $partialFieldsQuery;
    }

    public function getQueryString()
    {
        return $this->buildQueryString();
    }

    /**
     * @param $searchString
     * @return $this
     */
    public function setSearchString($searchString)
    {
        if (!is_null($searchString)) {
            $this->addParameters(array('q' => $searchString));
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getSearchString()
    {
        return isset($this->parameters['q']) ? $this->parameters['q'] :'';
    }

    public function setRequiredFields($requiredFields)
    {
        $this->addParameters(array('requiredfields' => $requiredFields));

        return $this;
    }

    public function setPartialFields($partialFields)
    {
        $this->addParameters(array('partialfields' => $partialFields));

        return $this;
    }

    /**
     * Add a require field (metatag) for the request.
     * If type == OVERWRITE, the value for the meta remplace the old value
     * Default behavior is:
     * every call with a value for a meta is added as an 'AND' operation
     * @param string $metaTagName
     * @param string $meTagValue
     * @return $this
     */
    public function addRequiredField($metaTagName,$meTagValue = null, $type = 'AND')
    {
        if(!isset($this->requiredFields[$metaTagName])) {
            $this->requiredFields[$metaTagName] = array();
        }
        switch($type)
        {
            case 'OVERWRITE':
                $this->requiredFields[$metaTagName] = array($meTagValue);
                break;

            default:
                $this->requiredFields[$metaTagName][] = $meTagValue;
                break;
        }

        $requiredFieldsQuery = $this->buildRequiredFieldsQuery();
        $this->addParameters(array('requiredfields' => $requiredFieldsQuery));

        return $this;
    }

    /**
     * Add a partial field (metatag) for the request.
     * If type == OVERWRITE, the value for the meta remplace the old value
     * Default behavior is:
     * every call with a value for a meta is added as an 'AND' operation
     * @param string $metaTagName
     * @param string $meTagValue
     * @return $this
     */
    public function addPartialField($metaTagName,$meTagValue = null, $type = 'AND')
    {
        if(!isset($this->partialFields[$metaTagName])) {
            $this->partialFields[$metaTagName] = array();
        }
        switch($type)
        {
            case 'OVERWRITE':
                $this->partialFields[$metaTagName] = array($meTagValue);
                break;

            default:
                $this->partialFields[$metaTagName][] = $meTagValue;
                break;
        }

        $partialFieldsQuery = $this->buildPartialFieldsQuery();
        $this->addParameters(array('partialfields' => $partialFieldsQuery));

        return $this;
    }

    public function getRequiredFields()
    {
        return $this->requiredFields;
    }

    public function getPartialFields()
    {
        return $this->partialFields;
    }

    /**
     * Return the url that will be use for the request
     * @return string
     */
    public function getUrl()
    {
        $queryString = $this->buildQueryString();

        return $this->buildHttpRequest($queryString)->getUrl();
    }

    /**
     * Build an http request to be use for querying the server
     * @return \Guzzle\Http\Message\RequestInterface
     */
    private function buildHttpRequest($query)
    {
        // Setting up an url based on server_adress and server_port defined in the services.yml
        $baseUrl = 'http://'.$this->serverAddress;
        if (!empty($this->serverPort)) {
            $baseUrl .= ':'.$this->serverPort;
        }
        $baseUrl .= '/search?';//. $query;

        var_dump('baseUrl');
        var_dump($baseUrl);
        var_dump('query');
        var_dump($query);
        // Setting up a guzzle client with a timeout and an url base
        $client = new Client([
            'timeout'   => 5,
            'base_uri'  => $baseUrl ,
        ]);

        // Request based on the $baseUrl adding $urladd
        $request= $client->request('GET', $query);

        //var_dump('request');
        //var_dump(get_class_methods($request));


//        $this->httpClient->setBaseUrl($baseUrl);
        //$request = $this->httpClient->get('/search?'.$query);
        //$request->addHeader('Accept-Language','fr;q=0.8,en;q=0.6');

        return $request;
    }

    /**
     * Set the index for the first result
     * and the number of results to get from the first
     * @param int $start
     * @param int $num
     */
    public function limit($start, $num)
    {
        $start = (int) $start;
        $num = (int) $num;
        if (0 < $num) {
            $this->addParameters(array('start' => $start, 'num' => $num));
        }
        return $this;
    }

    public function setAsQ($value)
    {
        $this->addParameters(array('as_q' => $value));
    }

    /**
     * Set the results format type and associated paramters for request
     * @param string $format
     * @throws \InvalidArgumentException
     */
    public function setResultsFormat($format = 'xml') {
        if (!in_array($format,array('xml','json'))) {
            throw new \InvalidArgumentException('Accepted formats are json or xml');
        }
        $this->format = $format;
        if ('json' == $format) {
            $this->addParameters(array('proxystylesheet' => 'json'));
        } else {
            unset($this->parameters['proxystylesheet']);
        }

        return $this;
    }

    public function getResultFormat()
    {
        return $this->format;
    }

    public function setResultCountPrecision($param)
    {
        $this->addParameters(array('rc' => (int) $param));

        return $this;
    }

    /**
     * Execute a GET request to the GSA server
     * Fetch and return a Response object
     * @param string $searchString
     * @return Response
     * @throws \BadFunctionCallException
     */
    public function send($searchString = null)
    {
        if (!is_null($searchString)) {
            $this->addParameters(array('q' => $searchString));
        }

        $parameters = $this->getParameters();

        var_dump('parameters');
        var_dump($parameters);
        foreach($this->mandatoryParameters as $mandatoryParameter) {
            if (!isset($parameters[$mandatoryParameter])) {
                throw new \Exception('missing mandatory parameters : '.$mandatoryParameter);
            }
        }

        if (!isset($this->parameters['q']) || empty($this->parameters['q'])) {
            throw new \BadFunctionCallException('gsa request needs at least a search string');
        }


        $searchString = $this->buildQueryString();
  //      $request = $this->buildHttpRequest($searchString);
        //$response = (string) $request->send()->getBody();
//setResultsFormat
        var_dump('searchString');
        var_dump($searchString);
        $request = $this->buildHttpRequest($searchString);

        try {
            //$response = (string) $request->getBody();
            var_dump($this->buildQueryString());
            $response = (string) $request->getBody();
            var_dump($response);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \Exception('GuzzleHttp Exception : '.$e->getMessage());
        }



        return $response;
    }
}
