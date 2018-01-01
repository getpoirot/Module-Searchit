<?php
use Module\Searchit\Model\Driver\Elastic\RepoSearchableItems;
use Module\Searchit\Model\Driver\Elastic\RepoSearchableTypes;
use Poirot\Ioc\Container\BuildContainer;

return [
    'services' => [
        // Services Used By QueueDriver

        'elasticClient'  => [
            BuildContainer::INST => \Module\Searchit\Services\ServiceElasticClient::class,
            'options' => [
                'hosts' => getenv('SEARCHIT_ELASTIC_HOSTS'),
            ],
        ],

    ],

    'nested' => [
        'repository' => [
            'services' => [

                'SearchableTypes' => new \Poirot\Ioc\instance(
                    RepoSearchableTypes::class
                    , [
                        'client' => '/module/searchit/services/ElasticClient',
                    ]
                ),

                'SearchableItems' => new \Poirot\Ioc\instance(
                    RepoSearchableItems::class
                    , [
                        'client' => '/module/searchit/services/ElasticClient',
                    ]
                ),

            ],
        ],
    ],
];
