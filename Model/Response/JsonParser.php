<?php

namespace BackBee\Bundle\GSABundle\Model\Response;

use BackBee\Bundle\GSABundle\Model\Response;
use BackBee\Bundle\GSABundle\Model\Result;

class JsonParser implements ParserInterface
{
    /**
     * @var Response gsaResponse Model
     */
    private $gsaResponse;
    /**
     * @var string the json string returned by GSA
     */
    private $jsonString;

    public function __construct($jsonString = null, $gsaResponse = null)
    {
        $this->gsaResponse = $gsaResponse;
        $this->jsonString = $jsonString;
    }

    public function setDataToParse($data)
    {
        $this->jsonString = $data;
        return $this;
    }

    public function getDataToParse($data)
    {
        return $this->jsonString;
    }

    /**
     * @parent
     * @param string $json
     * @return Response|null
     * @throws \BadMethodCallException if no json string to parse
     */
    public function parse($jsonString = null)
    {
        $jsonString = is_null($jsonString) ? $this->jsonString : $jsonString;
        if (is_null($jsonString)) {
            throw new \BadMethodCallException('no json string to parse');
        }
        echo($jsonString); exit();
        $json = json_decode($jsonString,true);

        $this->gsaResponse =  new Response();

        $this->gsaResponse
            ->setGsaVersion($json['GSP']['VER'])
            ->setTime($json['GSP']['TM'])
            ->setSearchString($json['GSP']['Q']);

        $this->parseParameters($json['GSP']);
        $this->parseSpelling($json['GSP']);
        $this->parseSynonyms($json['GSP']);
        $this->parsePartnersLinks($json['GSP']);
        $this->parseResponse($json['GSP']);

        return $this->gsaResponse;
    }

    private function parseSpelling($json)
    {
        if(isset($json['Spelling'])) {
            /**TODO*/
        }
    }

    /**
     * @param array $json
     */
    private function parseParameters($json)
    {
        $params = isset($json['PARAMS']) ? $json['PARAMS'] : array();

        foreach($params as $param)
        {
            $this->gsaResponse->addParameter(
                $param['name'],
                $param['value'],
                $param['original_value']
            );
        }

        return $this;
    }

    /**
     * @param array $json
     */
    private function parseSynonyms($json)
    {
        if(isset($json['Synonyms'])) {
            foreach($json['Synonyms'] as $tab) {
                $this->gsaResponse->addSynonym(
                    $tab['oneSynonym'],
                    $tab['oneSynonym']
                );
            }
        }
    }

    /**
     * @param array $json
     */
    private function parsePartnersLinks($json)
    {
        if (isset($json['GM'])) {
            foreach($json['GM'] as $link)
            {
                $this->gsaResponse->addPartnersLink($link['GD'],$link['GL']);
            }
        }
    }

    /**
     * @param array $json
     * @param Response $gsaResponse
     */
    private function parseResponse($json)
    {
        if (isset($json['RES'])) {
            $this->gsaResponse->setTotalResults($json['RES']['M']);
            if(isset($json['RES']['NB'])) {
                if (isset($json['RES']['NB']['PU'])) {
                    $this->gsaResponse
                        ->setPreviousPageUrl($json['RES']['NB']['PU']);
                }
                if (isset($json['RES']['NB']['NU'])) {
                    $this->gsaResponse
                        ->setPreviousPageUrl($json['RES']['NB']['NU']);
                }
            }
            $this->parseResults($json['RES']['R']);
            $this->parseMetaTags($json['RES']['PARM']);

        }

        return $this;
    }

    /**
     * @param array $json
     * @param Response $gsaResponse
     */
    private function parseResults($json)
    {
        foreach($json as $resultJson) {
            $result = new Result();
            $result->setUrl($this->getOrNull($resultJson,'U'))
                ->setTitle($this->getOrNull($resultJson,'T'))
                ->setRank($this->getOrNull($resultJson,'RK'))
                ->setCrawlDate($this->getOrNull($resultJson,'CRAWLDATE'))
                ->setSearchApplianceId($this->getOrNull($resultJson,'ENT_SOURCE'))
                ->setSnippet($this->getOrNull($resultJson,'S'))
                ->setLanguage($this->getOrNull($resultJson,'LANG'));
            if (isset($json['MT'])) {
                foreach($json['MT'] as $meta) {
                    $this->parseResultMetaTag($meta,$result);
                }
            }
            $this->gsaResponse->addResult($result);
        }

        return $this;
    }

    private function getOrNull($tab,$key)
    {
        if (isset($tab[$key])) {
            return $tab[$key];
        }

        return null;
    }

    /**
     * @param array $json
     * @param Result $result
     */
    private function parseResultMetaTag($json, Result $result)
    {
        $result->addMetaTag(
            $json['N'],
            $json['V']
        );

        return $this;
    }

    /**
     * @param array $json
     * @return $this
     */
    private function parseMetaTags($json)
    {
        if (isset($json['PMT'])) {
            foreach($json['PMT'] as $pmt) {
                foreach($pmt['PV'] as $pv)
                    $this->gsaResponse->addMetaTag(
                        $pmt['NM'],
                        $pv['V'],
                        $pv['C']
                    );
            }
        }

        return $this;
    }
}