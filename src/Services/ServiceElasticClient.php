<?php
namespace Module\Searchit\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Poirot\Ioc\Container\Service\aServiceContainer;


class ServiceElasticClient
    extends aServiceContainer
{
    protected $hosts = 'elastic:changeme@search_server_storage:9200';


    /**
     * Create Service
     *
     * @return Client
     */
    function newService()
    {
        return ClientBuilder::create()
            ->setHosts($this->hosts)
            ->build();
    }


    // options

    /**
     * @param mixed $hosts
     */
    function setHosts($hosts)
    {
        $this->hosts = $hosts;
    }
}
