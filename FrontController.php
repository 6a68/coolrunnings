<?php

class FrontController
{
    public function decodeRequest($inputAsJson)
    {
        return json_decode($inputAsJson, true);
    }

    protected $absolutelyCool;

    public function setAbsolutelyCool($aCoolObject)
    {
        $this->absolutelyCool = $aCoolObject;
        return $this;
    }

    public function dispatch($inputAsArray)
    {
        $this->absolutelyCool->runnings($inputAsArray);
        return $this;
    }
}
