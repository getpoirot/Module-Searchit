<?php
namespace Module\Searchit\Model\Driver\Elastic;

use Elasticsearch\Client;
use Module\Searchit\Exceptions\exSearchableEntityNotFound;
use Module\Searchit\Exceptions\exSearchableTypeExist;
use Module\Searchit\Interfaces\iRepoSearchableTypes;
use Module\Searchit\Model\Entity\EntitySearchableType;


class RepoSearchableTypes
    implements iRepoSearchableTypes
{
    /** @var Client */
    private $_gateway;
    /** @var string */
    private $_index;


    /**
     * SearchableTypeRepository constructor.
     *
     * @param Client $client
     * @param string $indexName
     */
    function __construct(Client $client, $indexName = 'search')
    {
        $this->_gateway = $client;
        $this->_index   = $indexName;

        /*
         * check if _index exist (if it doesn't create it )
         */
        if (! $this->_gateway->indices()->exists(['index' => $indexName]) )
            $this->_gateway->indices()->create(['index' => $indexName]);
    }


    /**
     * This method find a mapping type by it's identifier
     *
     * @param $identifier
     * @return EntitySearchableType
     * @throws exSearchableEntityNotFound
     */
    function findByIdentifier($identifier)
    {
        $res = $this->_gateway->indices()->getMapping([
            'index' => $this->_index,
        ])['search']['mappings'];

        $type = str_replace($this->_index.'_',"", $identifier);

        if (array_key_exists($type, $res))
            return $this->mappingToSearchableEntity($type, $res[$type]);

        throw new exSearchableEntityNotFound(
            sprintf(
                'searchable entity with identifier : %s not found.',
                $identifier
            )
        );
    }

    /**
     * This method find a mapping type by it's name
     *
     * @param $name
     * @return EntitySearchableType
     * @throws exSearchableEntityNotFound
     */
    function findByName($name)
    {
        $res = $this->_gateway->indices()->getMapping([
            'index' => $this->_index,
        ])['search']['mappings'];

        if (array_key_exists($name, $res))
            return $this->mappingToSearchableEntity($name, $res[$name]);

        throw new exSearchableEntityNotFound(
            sprintf(
                'searchable entity with name : %s not found.',
                $name
            )
        );
    }


    /**
     * List of existing mapping types in storage
     * no pagination
     *
     * Sample Output:
    [
    {
    "identifier": "search_test21",
    "name": "test21",
    "searchable_fields": [
    {
    "name": "bio",
    "type": "text"
    },
    {
    "name": "name",
    "type": "text"
    },
    {
    "name": "username",
    "type": "text"
    }
    ]
    },
    {
    "identifier": "search_test15",
    "name": "test15",
    "searchable_fields": [
    {
    "name": "bio",
    "type": "text"
    },
    {
    "name": "name",
    "type": "text"
    },
    {
    "name": "username",
    "type": "text"
    }
    ]
    }
    ]
     *
     * @return mixed
     */
    function findAll()
    {
        $res = $this->_gateway
            ->indices()
            ->getMapping( ['index' => $this->_index] )['search']['mappings'];

        $collection = new Collection();
        $array = []; $i = 0;

        foreach ($res as $type => $mapping){

            foreach ($mapping['properties'] as $key => $value){

                $array[$i]['searchable_fields'][] = [
                    'name' => $key,
                    'type' => $value['type']
                ];
            }

            $collection->add($this->mappingToSearchableEntity($type, $mapping));
            $i++;
        }

        return $collection;
    }


    /**
     * @param array $identifiers
     * @return mixed
     */
    function findManyByIdentifiers(array $identifiers)
    {
        // TODO: Implement findManyByIdentifiers() method.
    }

    /**
     * @param array $names
     * @return mixed
     */
    function findManyByNames(array $names)
    {
        // TODO: Implement findManyByNames() method.
    }


    /**
     * Create new searchable type
     * actually in elasticsearch creating new searchable_type means creating
     * new mapping type
     *
     * so based on searchable_type : name AND searchable_type : searchable_fields
     * we're going to create new mapping type
     * so after this creation we can index data of this new type
     *
     * @param EntitySearchableType $searchableTypeEntity
     *
     * @return EntitySearchableType
     * @throws \Exception if there is any error in write time
     * @throws exSearchableTypeExist if searchable_type already exist
     */
    function insert(EntitySearchableType $searchableTypeEntity)
    {
        // check if mapping (type) doesn't exist already
        $typeExists = $this->_gateway->indices()->existsType([
            'index' => $this->_index,
            'type'  => $searchableTypeEntity->getIdentifier()
        ]);

        if($typeExists){
            throw new exSearchableTypeExist(sprintf(
                'searchable_type : %s already exist if you want to update it
                 please use update function', $searchableTypeEntity->getIdentifier()
            ));
        }

        $params = $this->generateParamsForCreateNewMapping($searchableTypeEntity);

        // create new mappings
        $result = $this->_gateway->indices()->putMapping($params);

        if(array_key_exists('acknowledged', $result) &&
            $result['acknowledged'] == true
        ){

            /** @var EntitySearchableType $searchableTypeEntity */
            $searchableTypeEntity->setIdentifier(
//                $this->_index.'_'.$searchableTypeEntity->getIdentifier()
                $searchableTypeEntity->getIdentifier()
            );

            return $searchableTypeEntity;
        }

        else{
            throw new \Exception(json_encode($result));
        }
    }


    /**
     * This method create properties for an mapping type
     *
     *   sample output :
     *   [
     *      'name' => [
     *          'type' => 'string'
     *      ]
     *   ]
     *
     * @param EntitySearchableType $searchableTypeEntity
     * @return array
     */
    private function createPropertiesForSearchableEntity(
        EntitySearchableType $searchableTypeEntity
    )
    {
        $searchable_fields = $searchableTypeEntity->getSearchableFields();
        $params = [];

        foreach ($searchable_fields as $key => $searchable_field){
            $params[$key]['type'] = $searchable_field;
        }

        if($searchableTypeEntity->hasAutocomplete()){
            $params['name_suggest'] = [
                'type' => 'completion',
                'contexts' => [
                    'name' => 'suggest_type',
                    'type' => 'category'
                ]
            ];
        }

        return $params;
    }


    /**
     * This function generates params array to be given to elastic client
     * to create new mapping type for an existing index
     *
     * for example you have an index called my_index
     * and you want to create a mapping type for index: my_index
     * and you are going to create a new mapping_type called : my_type,
     * which has following fields :
     *
     * name [ 'type' => 'string' ]
     *
    array $params looks :

    $params = [
    'index' => 'my_index',
    'type' => 'my_type',
    'body' => [
    'my_type' => [
    '_source' => [
    'enabled' => true
    ],
    'properties' => [
    'name' => [
    'type' => 'string'
    ]
    ]
    ]
    ]
    ];

     * @param EntitySearchableType $searchableTypeEntity
     * @return array
     */
    private function generateParamsForCreateNewMapping(
        EntitySearchableType $searchableTypeEntity
    )
    {
        $properties = $this->createPropertiesForSearchableEntity($searchableTypeEntity);
        $type = $searchableTypeEntity->getIdentifier();

        $params = [
            'index' => $this->_index,
            'type'  => $type,
            'body' => [
                $type => [
                    '_source' => [
                        'enabled' => true
                    ],
                    'properties' => $properties
                ]
            ],

        ];

        return $params;
    }


    /**
     * @param string $mapping_type
     * @param array $mapping
     *
     * @return EntitySearchableType
     */
    private function mappingToSearchableEntity(
        $mapping_type,
        array $mapping
    )
    {
        $arr = [];

        //$arr['name'] = $mapping_type;
//        $arr['identifier'] = $this->_index.'_'.$mapping_type;
        $arr['identifier'] = $mapping_type;


        foreach ($mapping['properties'] as $key => $value){

            if($key == 'name_suggest'){
                $arr['autocomplete'] = true;
            }else{

                $arr['searchable_fields'][] = [
                    'name' => $key,
                    'type' => $value['type']
                ];
            }
        }

        return new EntitySearchableType($arr);
    }

    /**
     * @param $identifier
     * @return bool
     * @throws \Exception
     */
    function deleteByIdentifier($identifier)
    {
        throw new \Exception('deleting type is not an option.');
    }
}
