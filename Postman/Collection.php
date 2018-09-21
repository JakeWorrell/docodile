<?php
namespace Docodile\Postman;

class Collection {
    protected $collection;

    static function fromFile($filename, $env = null) {
        $json = file_get_contents($filename);
        if ($env) {
            foreach($env as $key => $val) {
                $json = str_ireplace($key, $val, $json);
            }
        }
        return new self($json);
    }

    public function __construct($json){
        $this->collection = json_decode($json);
    }

    public function getVersion()
    {
        preg_match('/(?<=v)\d+(\.\d+)?(\.\d+)?/', $this->data()->info->schema, $collectionVersion);
        return $collectionVersion[0];
    }

    public function data()
    {
        return $this->collection;
    }

    public function getRequests(){
        return $this->collection->requests;
    }

    public function getItems()
    {
        return $this->data()->item;
    }

}