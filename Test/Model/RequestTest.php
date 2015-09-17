<?php

namespace BackBee\Bundle\GSABundle\Test\Model;

use BackBee\Bundle\GSABundle\Model\Request;
use GuzzleHttp\Client;
//use Guzzle\Http\Message\Request as httpRequest;

class RequestTest extends \PHPUnit_Framework_TestCase
{

    private function constructGsaRequest($client = null, $serverAddress = null, $serverPort = null, $defaultParams = array())
    {
        $httpClient = (!is_null($client)) ? $client : new Client();
        $serverAddress = (!is_null($serverAddress))? $serverAddress : 'gsa1.01net.com';
        $serverPort = (!is_null($serverPort))?$serverPort:'';
        $defaultParams = (count($defaultParams)>0)?$defaultParams:array(
            "client" => "json",
            "output" => "xml_no_dtd",
            "site" => "default_collection",
            "start" => "0",
            "num" => "10",
            "getfields" => "*",
            "requiredfields" => "typology",
        );

        return new Request($httpClient,$serverAddress,$serverPort,$defaultParams);
    }

    private function constructGsaRequestWithMockedClient()
    {
        $httpClientMock = $this->getMock('GuzzleHttp\Client');
        $httpRequestMock = $this->getMock('GuzzleHttp\Message\Request',array(),array(),'',false);
        $httpResponseMock = $this->getMock('GuzzleHttp\Message\Response',array(),array(),'',false);

        $httpClientMock
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($httpRequestMock));

        $httpRequestMock
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($httpResponseMock));

        $httpResponseMock
            ->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue('OK'));

        $gsaRequest = $this->constructGsaRequest($httpClientMock);

        return $gsaRequest;
    }

    public function testConstructor()
    {
        $request = $this->constructGsaRequest();

        $this->assertEquals('gsa1.01net.com',$request->getServerAddress());
        $this->assertEmpty($request->getServerPort());
        $this->assertEquals(7,count($request->getDefaultParams()));
        $this->assertEquals('xml',$request->getResultFormat());
    }

    public function testReset()
    {
        $request = $this->constructGsaRequest();
        $request->reset();

        $this->assertEquals($request->getDefaultParams(),$request->getParameters());
        $this->assertContainsOnly('typology',$request->getRequiredFields());
        $this->assertEquals('xml',$request->getResultFormat());
    }

    public function testSetResultFormatChangeProxystylesheetParam()
    {
        $request = $this->constructGsaRequest();
        $request->setResultsFormat('json');
        $parameters = $request->getParameters();

        $this->assertTrue(isset($parameters['proxystylesheet']));

        $request->setResultsFormat('xml');
        $parameters = $request->getParameters();

        $this->assertFalse(isset($parameters['proxystylesheet']));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function setUnavailableResultFormatThrowsException()
    {
        $request = $this->constructGsaRequest();
        $request->setResultsFormat('nawak');
    }

    public function testGetUnknownParameterReturnsEmpty()
    {
        $request = $this->constructGsaRequest();
        $this->assertEmpty($request->getParameters('unknownParam'));
    }

    public function testParametersAreCompletedByDefaultOnes()
    {
        $request = $this->constructGsaRequest();

        $this->assertEquals('*',$request->getParameters('getfields'));
    }

    public function testAddParametersOverwriteOldOnes()
    {
        $request = $this->constructGsaRequest();

        $request->limit(10,50);
        $request->limit(2,3);

        $this->assertEquals(2,$request->getParameters('start'));
        $this->assertEquals(3,$request->getParameters('num'));
    }

    public function testBuildingQueryString()
    {
        $request = $this->constructGsaRequest();
        $request->setSearchString('test');
        $this->assertEquals('q=test&client=json&output=xml_no_dtd&site=default_collection&start=0&num=10&getfields=%2A&requiredfields=typology',
            $request->getQueryString());
    }

    public function testGetUrl()
    {
        $request = $this->constructGsaRequest();
        $request->setSearchString('test');

        $this->assertEquals('http://gsa1.01net.com/search?q=test&client=json&output=xml_no_dtd&site=default_collection&start=0&num=10&getfields=%2A&requiredfields=typology',
            $request->getUrl());
    }

    public function testLimits()
    {
        $request = $this->constructGsaRequest();
        $request->limit('15','22');

        $this->assertEquals('15',$request->getParameters('start'));
        $this->assertEquals('22',$request->getParameters('num'));
    }

    public function testSuccessfullSend()
    {
        $httpClientMock = $this->getMock('Guzzle\Http\Client');
        $httpRequestMock = $this->getMock('Guzzle\Http\Message\Request',array(),array(),'',false);
        $httpResponseMock = $this->getMock('Guzzle\Http\Message\Response',array(),array(),'',false);

        $httpClientMock
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($httpRequestMock));

        $httpRequestMock
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($httpResponseMock));

        $httpResponseMock
            ->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue('OK'));

        $gsaRequest = $this->constructGsaRequest($httpClientMock);

        $this->assertEquals('OK',$gsaRequest->send('hello'));
    }

    /**
     * @expectedException \Exception
     */
    public function testSendWithoutMandatoryParameters()
    {
        $request = $this->constructGsaRequest();
        $request->send();
    }

    /**
     * @expectedException \Exception
     */
    public function testSendWithoutSearchStringParameters()
    {
        $request = $this->constructGsaRequest();
        $request->send('');
    }




}