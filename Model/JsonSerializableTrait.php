<?php

namespace BackBee\Bundle\GSABundle\Model;


trait JsonSerializableTrait
{
    public function jsonSerialize()
    {
        $json = array();
        foreach($this as $key => $value) {
            $json[$key] = $value;
        }
        return $json;
    }
}