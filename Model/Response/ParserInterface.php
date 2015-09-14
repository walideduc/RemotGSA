<?php

namespace BackBuilder\Bundle\GSABundle\Model\Response;

use BackBuilder\Bundle\GSABundle\Model\Response;

interface ParserInterface
{
    public function setDataToParse($data);
    public function getDataToParse($data);

    /**
     * @param string $xml
     * @return Response|null
     * @throws \BadMethodCallException if no data string to parse
     */
    public function parse($xml = null);
}