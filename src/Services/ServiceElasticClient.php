<?php
namespace Module\Searchit\Services;

use Poirot\Ioc\Container\Service\aServiceContainer;


class ServiceElasticClient
    extends aServiceContainer
{
    protected $hosts;


    /**
     * Create Service
     *
     * @return mixed
     */
    function newService()
    {

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
