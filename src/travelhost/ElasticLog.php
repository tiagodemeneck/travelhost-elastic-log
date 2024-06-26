<?php

namespace Travelhost;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

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

        $logs = $this->client->search(['indexName' => $indexName]);
        return $logs;
    }
}

?>