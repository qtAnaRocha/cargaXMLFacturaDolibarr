<?php

class Enlace {    
    protected $location;
    protected $ref;
    protected $bandError;
    
    function __construct($location, $ref, $bandError=false) {
        $this->location = $location;
        $this->ref = $ref;
        $this->bandError = $bandError;
    }
    
    function getLocation() {
        return $this->location;
    }

    function getRef() {
        return $this->ref;
    }

    function getBandError() {
        return $this->bandError;
    }
}
