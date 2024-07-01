<?php

namespace Travelhost;

use Elasticsearch\ClientBuilder;
use Exception;

class ElasticLog{

    protected $client;
    protected static $level;

    public function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();           
    }


    public static function error(){

        self::$level = 'error';
        return new self();
    }
    public static function info(){

        self::$level = 'info';
        return new self();
    }
    public static function warning(){

        self::$level = 'warning';
        return new self();
    }
    public function registerLog(array $parameters){

        $this->indexExists();
        $parameters['level'] = self::$level;
        $this->client->index($parameters);
    }

    private function indexExists(){

        if(!env("ELASTIC_INDEX")) throw new Exception("ELASTIC_INDEX missing in your .env file.");
        if(!$this->client->indices()->exists(['index' => env('ELASTIC_INDEX')])){

            $this->client->create($this->getIndexParameters());
        }

    }

    private function getIndexParameters(){

        return [
        "index" => "logs",
        "id" => "id",
        'body' => [
            'settings' => [
                'number_of_shards' => 3,
                'number_of_replicas' => 0,
                'index.mapping.ignore_malformed' => true
            ],
            'mappings' => [
                '_source' => [
                    'enabled' => true
                ],
                'properties' => [
                    'level' => ['type' => 'text'],
                    'message' => ['type' => 'text'],
                    'created_at' => ['type' => 'date', "format" => "yyyy-MM-dd"],
                    'context' => ['type' => 'text']
                ]
            ]
        ]];
    }

    public function teste(){
        
        return self::$level;
    }
}

?>