<?php

namespace BackBee\Bundle\GSABundle\Model\Response;


class ParserFactory
{
    /**
     * @param $type
     * @return ParserInterface
     * @throws \InvalidArgumentException
     */
    public static function getParser($type)
    {
        $class = 'BackBee\Bundle\GSABundle\Model\Response\\'.ucfirst($type).'Parser';
        if (!class_exists($class)) {
            throw new \InvalidArgumentException('parser type: '.$class.' unknown');
        }

        return new $class();
    }
}