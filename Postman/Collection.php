<?php

class Collection {
    protected $collection;

    static function fromFile($filename) {
        return new self(file_get_contents($filename));
    }

    public function __construct($json){
        $this->collection = json_decode($json);
    }

    public function getRequests(){
        return $this->collection->requests;
    }
}