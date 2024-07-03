<?php

namespace Travelhost;

use Elasticsearch\ClientBuilder;
use Exception;
use Illuminate\Support\Facades\Log;

class ElasticLog{

    protected $client;
    protected static $level;

    public function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();           
    }

    /** Level do log */
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
    /**
     * @param array $parameters Parametros esperados dentro do array: context(string), message(string) e created_at(Y-m-d)
     * @return void
     */
    public function registerLog(array $parameters):void{

        try{

            $this->indexExists();
            $parameters['level'] = self::$level;
            $this->client->index(['index' => env("ELASTICSEARCH_LOG_INDEX"), "body" => $parameters]);
        }
        catch(Exception $e){
            Log::error("ElasticLog", $e->getMessage());
        }
        
    }

    /**
     * @param array $parameters Você pode passar, duas datas dentro de um array dates,
     * startDate e endDate, ['dates' => ['startdate' => 'Y-m-d', 'endDate' => 'Y-m-d]],
     * ou você pode passar uma keyword, ['keyword' => :keyword]
     * @return array $logs
     */
    public function searchLogs(array $parameters){

        $this->setSearchParameters($parameters);
        $elasticResponse = $this->client->search($parameters);
        if(count($elasticResponse["hits"]["hits"]) == 0){
            return [];
        }
        return $elasticResponse["hits"]["hits"];
    }

    private function setSearchParameters(&$parameters):void{

        if(isset($parameters['dates'])){
            $parameters = [
                "index" => env('ELASTICSEARCH_LOG_INDEX'),
                'body' => [
                    'query' => [
                        "bool" => [
                            "must" => [ 
                                ['match' => [
                                    'level' => self::$level
                                ]],
                                ['range' => [
                                    'created_at' => [
                                        'gte' => $parameters['dates']['startDate'],
                                        'lte' => $parameters['dates']['endDate']
                                    ]
                                ]]
                            ]
                        ]
                    ]
                ]
            ];
        }
       
        else if(isset($parameters['keyword']) && is_string($parameters['keyword'])){
            $keyword = $parameters["keyword"];
            $parameters = [
                'index' => env('ELASTICSEARCH_LOG_INDEX'),
                'body' => [
                    'query' => [
                        "bool" => [
                            "must" =>[ [
                                "query_string" => [
                                    "default_field" => "context",
                                    "query" => "*$keyword*"
                                ]],
                                [
                                'match' => [
                                    'level' => self::$level
                                ]]
                            ]
                        ]
                        
                    ]
                ]
            ];
        }
    }

    private function indexExists(){

        if(!env("ELASTICSEARCH_LOG_INDEX")) throw new Exception("ELASTICSEARCH_LOG_INDEX missing in your .env file.");
        if(!$this->client->indices()->exists(['index' => env('ELASTICSEARCH_LOG_INDEX')])){

            $this->client->create($this->getIndexParameters());
        }

    }

    private function getIndexParameters(){

        return [
        "index" => env('ELASTICSEARCH_LOG_INDEX'),
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
}

?>