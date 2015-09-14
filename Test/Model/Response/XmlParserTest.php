<?php

namespace BackBuilder\Bundle\GSABundle\Test\Model\Response;



use BackBuilder\Bundle\GSABundle\Model\Response\XmlParser;
use BackBuilder\Bundle\GSABundle\Model\Response;

class XmlParserTest extends \PHPUnit_Framework_TestCase
{
    public static $xmlNoResult;
    public static $xmlOneResult;
    public static $xmlMultipleResults;
    public static $xmlWithPartnersLinks;

    public static function setUpBeforeClass()
    {
        self::loadXmlFiles();
    }

    public function testParseWithoutDataThrowException()
    {
        $this->setExpectedException('BadMethodCallException');
        $parser = new XmlParser();
        $parser->parse();
    }

    public static function loadXmlFiles()
    {
        if (is_null(self::$xmlNoResult)) {
            self::$xmlWithPartnersLinks = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR .'XmlTestFiles/WithPartnersLinks.xml');
            self::$xmlNoResult = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR .'XmlTestFiles/NoResult.xml');
            self::$xmlOneResult = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR .'XmlTestFiles/OneResult.xml');
            self::$xmlMultipleResults = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR .'XmlTestFiles/MultipleResults.xml');
        }
    }

    private function parseXml($xml)
    {
        $xmlParser = new XmlParser($xml);
        return $xmlParser->parse();
    }

    public function testParsingSetBasicInformation()
    {
        $gsaResponse = $this->parseXml(self::$xmlNoResult);

        $this->assertEquals('3.2',$gsaResponse->getGsaVersion());
        $this->assertEquals('0.042443',$gsaResponse->getTime());
        $this->assertEquals('testqsdfsdqfsdqfsdqfsdq',$gsaResponse->getSearchString());
        $this->assertEquals(15,count($gsaResponse->getParameters()));
    }

    public function testParsingResponseNoResultXml()
    {
        $gsaResponse = $this->parseXml(self::$xmlNoResult);

        $this->assertEquals(0,count($gsaResponse->getResults()));
        $this->assertEquals(0,$gsaResponse->getTotalResults());
    }

    public function testParsingResults()
    {
        $gsaResponse = $this->parseXml(self::$xmlOneResult);
        $results = $gsaResponse->getResults();

        $this->assertEquals(1,count($results));
        $result = $results[0];

        $this->assertEquals('http://www.01net.com/article/197278/',$result->getUnrewritedUrl());
        $this->assertEquals('Rapidité Les <b>tests</b> ont été réalisés avec la définition maximale <b>...</b>',
            $result->getTitle());
        $this->assertEquals('9',$result->getRank());
        $this->assertEquals('11 fév 2014',$result->getCrawlDate());
        $this->assertEquals('T4-BK38FXVE46NLN',$result->getSearchApplianceId());
        $this->assertEquals('<b>...</b> Les <b>tests</b> 3D professionnels SPECviewperf 7.0 créent la surprise. <b>...</b> des<br> autonomies comprises entre 1 h 24 et 2 h 30 lors de notre <b>test</b> de lecture de <b>...</b>  ',
            $result->getSnippet());
        $this->assertEquals('fr',$result->getLanguage());
    }

    public function testParsingResultMetaTags()
    {
        $gsaResponse = $this->parseXml(self::$xmlOneResult);
        $results = $gsaResponse->getResults();
        $result = $results[0];

        $this->assertEquals(13,count($result->getMetaTags()));
        $this->assertEquals('article',$result->getMetaTag('typology'));
    }

    public function testParsingResponseMetaTags()
    {
        $gsaResponse = $this->parseXml(self::$xmlOneResult);

        $metas = $gsaResponse->getMetaTags();
        $this->assertEquals(1,count($metas));
        $this->assertTrue(isset($metas['typology']));
        $this->assertEquals(3, count($metas['typology']));
        $this->assertEquals('article',$metas['typology'][0]['value']);
        $this->assertEquals('17486',$metas['typology'][0]['count']);

    }

    public function testParseSynonyms()
    {
        $gsaResponse = $this->parseXml(self::$xmlMultipleResults);
        $synonyms = $gsaResponse->getSynonyms();
        $this->assertEquals(1, count($synonyms));
        $this->assertEquals('obama',$synonyms['obama']);
    }

    public function testParseSpellingSuggestions()
    {
        $gsaResponse = $this->parseXml(self::$xmlMultipleResults);
        $suggestions= $gsaResponse->getSuggestions();
        $this->assertEquals(1, count($suggestions));
        $this->assertEquals('nssa',$suggestions[0]);
    }

    public function testParsePartnersLinks()
    {
        $gsaResponse = $this->parseXml(self::$xmlWithPartnersLinks);
        $partnersLinks = $gsaResponse->getPartnersLinks();
        $this->assertCount(2,$partnersLinks);
        $this->assertEquals('http://soslaptop.com/',$partnersLinks[0]['url']);
        $this->assertEquals('http://www.google.fr/',$partnersLinks[1]['url']);
    }


    /**
     * @dataProvider getAllValidXml
     *//*
    public function testParseCorrectXmlWillSetBasicsData($xml)
    {
        $gsaResponseMock = $this->getMock('BackBuilder\Bundle\GSABundle\Model\Response',
            array('setGsaVersion','setTime','setSearchString'));

        $gsaResponseMock
            ->expects($this->once())
            ->method('setGsaVersion')
            ->will($this->returnSelf());
        $gsaResponseMock
            ->expects($this->once())
            ->method('setTime')
            ->will($this->returnSelf());
        $gsaResponseMock
            ->expects($this->once())
            ->method('setSearchString')
            ->will($this->returnSelf());

        $xmlParserMock = $this->getMock('BackBuilder\Bundle\GSABundle\Model\Response\XmlParser',
            array('parseParameters','parseResponse'),
            array($xml,$gsaResponseMock));

        $xmlParserMock
            ->expects($this->once())
            ->method('parseParameters');;

        $xmlParserMock
            ->expects($this->once())
            ->method('parseResponse');

        $xmlParserMock->parse();
    }

    public function getAllValidXml()
    {
        $this->loadXmlFiles();
        return array(
            array(self::$xmlNoResult),
            array(self::$xmlOneResult),
            array(self::$xmlMultipleResults),
        );
    }*/



} 