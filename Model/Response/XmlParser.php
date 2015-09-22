<?php

namespace BackBee\Bundle\GSABundle\Model\Response;

use BackBee\Bundle\GSABundle\Model\Response;
use BackBee\Bundle\GSABundle\Model\Result;
use BackBee\Bundle\CommonBundle\Utils\Encoding;

class XmlParser implements ParserInterface
{
    /**
     * @var Response gsaResponse Model
     */
    private $gsaResponse;
    /**
     * @var string the xml string returned by GSA
     */
    private $xmlString;

    public function __construct($xmlString = null, $gsaResponse = null)
    {
        $this->gsaResponse = $gsaResponse;
        $this->xmlString = $xmlString;
    }

    public function setDataToParse($data)
    {
        $this->xmlString = $data;
        return $this;
    }

    public function getDataToParse($data)
    {
        return $this->xmlString;
    }

    /**
     * @parent
     * @param string $xml
     * @return Response|null
     * @throws \BadMethodCallException if no xml string to parse
     */
    public function parse($xml = null)
    {
        $xml = is_null($xml) ? $this->xmlString : $xml;

        if (is_null($xml)) {
            throw new \BadMethodCallException('no xml string to parse');
        }

        $sXml = simplexml_load_string($xml);

        $this->gsaResponse = new Response();

        $this->gsaResponse
            ->setGsaVersion((string)$sXml->attributes()->VER)
            ->setTime((string)$sXml->TM)
            ->setSearchString((string)$sXml->Q);

        $this->parseParameters($sXml);
        $this->parseSpelling($sXml);
        $this->parseSynonyms($sXml);
        $this->parsePartnersLinks($sXml);
        $this->parseResponse($sXml);

        return $this->gsaResponse;
    }

    /**
     * @param \SimpleXMLElement $sXml
     */
    private function parseParameters(\SimpleXMLElement $sXml)
    {
        foreach($sXml->PARAM as $paramNode)
        {
            $attributes = $paramNode->attributes();
            $this->gsaResponse->addParameter(
                (string)$attributes->name,
                (string)$attributes->value,
                (string)$attributes->original_value);
        }

        return $this;
    }

    private function parseSpelling(\SimpleXMLElement $sXml)
    {
        if($sXml->Spelling) {
            foreach($sXml->Spelling->Suggestion as $suggestionNode) {
                $this->gsaResponse->addSuggestion(
                    (string)$suggestionNode->attributes()->q
                );
            }
        }
    }

    private function parseSynonyms(\SimpleXMLElement $sXml)
    {
        if($sXml->Synonyms) {
            foreach($sXml->Synonyms->OneSynonym as $synonymNode) {
                $this->gsaResponse->addSynonym(
                    (string)$synonymNode,
                    (string)$synonymNode->attributes()->q
                );
            }
        }
    }

    private function parsePartnersLinks(\SimpleXMLElement $sXml)
    {
        foreach($sXml->GM as $linkNode)
        {
            $this->gsaResponse->addPartnersLink((string)$linkNode->GD,(string)$linkNode->GL);
        }
    }

    /**
     * @param \SimpleXmlElement $sXml
     * @param Response $gsaResponse
     */
    private function parseResponse(\SimpleXMLElement $sXml)
    {
        $resNode = $sXml->RES;
        if ($resNode) {
            $this->gsaResponse->setTotalResults((string)$resNode->M);
            if($resNode->NB) {
                $this->gsaResponse
                    ->setPreviousPageUrl((string)$resNode->NB->PU)
                    ->setNextPageUrl((string)$resNode->NB->NU);
            }
            $this->parseResults($resNode->R);
            $this->parseMetaTags($resNode->PARM);

        }

        return $this;
    }

    /**
     * @param \SimpleXmlElement $resultNode
     * @param Response $gsaResponse
     */
    private function parseResults(\SimpleXMLElement $resultsNode)
    {
        foreach($resultsNode as $resultNode) {
            $result = new Result();
            $result->setUrl((string)$resultNode->U)
                   ->setTitle((string)Encoding::UTF8FixWin1252Chars($resultNode->T))
                   ->setRank((string)$resultNode->RK)
                   ->setCrawlDate((string)$resultNode->CRAWLDATE)
                   ->setSearchApplianceId((string)$resultNode->ENT_SOURCE)
                   ->setSnippet((string)Encoding::UTF8FixWin1252Chars($resultNode->S))
                   ->setLanguage((string)$resultNode->LANG);

            foreach($resultNode->MT as $metaNode) {
                $this->parseResultMetaTag($metaNode,$result);
            }
            $this->gsaResponse->addResult($result);
        }

        return $this;
    }

    /**
     * @param \SimpleXmlElement $metaNode
     * @param Result $result
     */
    private function parseResultMetaTag(\SimpleXMLElement $metaNode, Result $result)
    {
        $metaNodeAttributes = $metaNode->attributes();
        $result->addMetaTag(
            (string)$metaNodeAttributes->N,
            (string)Encoding::UTF8FixWin1252Chars($metaNodeAttributes->V)
        );

        return $this;
    }

    private function parseMetaTags(\SimpleXMLElement $parmNode)
    {
        if($parmNode->PMT) {
            foreach($parmNode->PMT as $pmtNode) {
                foreach($pmtNode->PV as $pvNode)
                $this->gsaResponse->addMetaTag(
                    (string)$pmtNode->attributes()->NM,
                    (string)$pvNode->attributes()->V,
                    (string)$pvNode->attributes()->C
                );
            }
        }

        return $this;
    }
}