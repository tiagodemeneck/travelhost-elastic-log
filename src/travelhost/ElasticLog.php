<?php

namespace Travelhost;

use Elasticsearch\ClientBuilder;

class ElasticLog{

    protected $client;
    public function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();           
    }
    public function teste(){
        return "teste";
    }

    public function budgetActionsReport(string $indexName){

        $logs = $this->client->search(['index' => $indexName]);
        return $logs;
    }
}

?>