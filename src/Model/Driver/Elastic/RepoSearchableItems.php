<?php
namespace Module\Searchit\Model\Driver\Elastic;

use Elasticsearch\Client;
use Module\Searchit\Exceptions\exSearchableEntityNotFound;
use Module\Searchit\Exceptions\exSearchableItemExist;
use Module\Searchit\Interfaces\iRepoSearchableItems;
use Module\Searchit\Model\Entity\EntitySearchableItem;


/**
 * Elastic Search Driver (Repository pattern) for search interface @see iRepSearchableItem
 *
 */
class RepoSearchableItems
    implements iRepoSearchableItems
{
    /** @var Client */
    private $_gateway;
    /** @var string */
    private $_index;


    /**
     * SearchableTypeRepository constructor.
     *
     * @param Client $gateway
     * @param string $index
     */
    public function __construct(Client $gateway, $index = 'search')
    {
        $this->_gateway = $gateway;
        $this->_index = $index;
    }


    /**
     * @param $identifier
     * @param string $type
     *
     * @return EntitySearchableItem
     */
    function findByIdentifier($identifier, $type)
    {
        $params = [
            'index' => env('ELASTIC_INDEX'),
            'type'  => $type,
            'id'    => $identifier
        ];

        $result = $this->_gateway->getSource($params);

        return new EntitySearchableItem($result);
    }


    /**
     * @param EntitySearchableItem $entity
     * @param bool $index_tags
     *
     * @return EntitySearchableItem
     * @throws \Exception
     * @throws exSearchableItemExist
     */
    function insert(EntitySearchableItem $entity, $index_tags = true)
    {
        if ($this->exist($entity))
            throw new exSearchableItemExist;

        /** @var EntitySearchableItem $entity */
        $body = $entity->getEntity();

        # here we add another field to all our searchable items
        # which is called tags_list, which as it's obvious shows a text
        # listing all tags this item have
        # in future we use this field to find top trend tags

        $body['tags_list'] = $entity->getTags();

        if( $index_tags && $entity->hasTags() ){
            $body['name_suggest'] = [
                'input' => $entity->getTags(),
                'contexts' => [
                    'suggest_type' => [ $entity->getType() ]
                ]
            ];

            unset($body['tags']);
        }

        $params = [
            'index' => $this->_index,
            'type' => $entity->getType(),
            'id' => $entity->getIdentifier(),
            'body' => $body
        ];

        $result = $this->_gateway->index($params);

        if($result['created'] == false){
            throw new \Exception(json_encode($result));
        }

        return $entity;
    }


    /**
     * this function returns most used tags with their usage count
     * in specified mapping types
     *
     * @param array $types
     * @return array
     */
    function mostUsedTags(array $types = [])
    {
        $params = [
            'index' => $this->_index,
            'type' => 'user',
            'body' => [
                'query'=> [
                    'size' => 0,
                    'match_all' => new \stdClass()
                ],
                /*                'aggs' => [
                                    'Most_Used_Tags' => [
                                        'terms' => ['field' => 'tags_list']
                                    ]
                                ]*/

                'aggs' => [
                    'count' => [
                        'nested' => [
                            'path' => 'attributes'
                        ],
                        'aggs' => [
                            'attribCount' => [
                                'terms'=> [
                                    'field' => 'attributes.name'
                                ]
                            ],
                            'attribVal' => [
                                'terms'=> [
                                    'field'=> 'attributes.name'
                                ],
                                "aggs"=> [
                                    "attribval2"=> [
                                        "terms"=> [
                                            "field"=> "attributes.value"
                                        ]
                                    ]
                                ]
                            ]

                        ]
                    ]
                ]
            ]
        ];



        return $result = $this->_gateway->search($params);
    }


    /**
     * @param EntitySearchableItem $entity
     * @return bool
     */
    private function exist(EntitySearchableItem $entity)
    {
        $params = [
            'index' => $this->_index,
            'type' => $entity->getType(),
            'id' => $entity->getIdentifier()
        ];

        if($this->_gateway->exists($params)){
            return true;
        }

        return false;
    }



    /**
     * @param $identifier
     * @param $type
     * @return mixed
     */
    function deleteByIdentifier($identifier, $type)
    {
        $params = [
            'index' => $this->_index,
            'type' => $type,
            'id' => $identifier
        ];

        return $this->_gateway->delete($params);
    }


    /**
     * @param EntitySearchableItem $item
     * @return array
     */
    function updateByIdentifier(EntitySearchableItem $item)
    {
        /** @var EntitySearchableItem $item */
        $params = [
            'index' => $this->_index,
            'type' => $item->getType(),
            'id' => 'my_id',
            'body' => [
                'doc' => $item->toArray()
            ]
        ];

        return $this->_gateway->update($params);
    }


    /**
     * @param EntitySearchableItem $item
     *
     * @return mixed
     * @throws \Exception
     * @throws exSearchableEntityNotFound
     */
    function updateByType(EntitySearchableItem $item)
    {
        if($this->exist($item))
            throw new exSearchableEntityNotFound;


        /** @var EntitySearchableItem $entity */
        $params = [
            'index' => $this->_index,
            'type' => $entity->getType(),
            'id' => $entity->getIdentifier(),
            'body' => $entity->toArray()
        ];

        $result = $this->_gateway->index($params);

        if($result['result'] == false){
            throw new \Exception(json_encode($result));
        }

        return $entity;


    }


    /**
     * todo implement pagination
     *
     * @param string $type
     *
     * @return \Traversable
     */
    function findAll($type)
    {
        $params = [
            'index' => $this->_index,
            'type' => $type
        ];

        $result = $this->_gateway->search($params);

        return $this->getResultFromSearchResult($result);
    }


    /**
     * @param array $result
     *
     * @return \Traversable
     */
    function getResultFromSearchResult(array $result)
    {
        $d = [];

        $data = $result['hits']['hits'];
        $col = new Collection();

        foreach ($data as $item){
            $col->add(new EntitySearchableItem($item['_source']));
        }

        $d['data'] = $col;
        $d['total'] = $result['hits']['total'];

        return $d;
    }


    /**
     * @param \Traversable $entities
     *
     * @return \Traversable
     * @throws \Exception
     */
    function insertBulk(\Traversable $entities)
    {
        $params = [];

        $count = count($entities);

        /** @var EntitySearchableItem $firstEntity */
        $firstEntity = $entities[0];

        $type = $firstEntity->getType();

        for($i = 0; $i < $count ; $i++) {

            /** @var EntitySearchableItem $entity */
            $entity = $entities[$i];

            $params['body'][] = [
                'index' => [
                    '_index' => $this->_index,
                    '_type' => $type,
                    '_id' => $entity->getIdentifier()
                ]
            ];

            $params['body'][] = $entity->getEntity();
        }

        $responses = $this->_gateway->bulk($params);


        if($responses['errors'] == false){
            return $entities;
        }
        else{
            throw new \Exception(json_encode($responses));
        }


    }


    /**
     * @param string $query
     * @param string $type
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    function searchSingleIndexSingleType(
        $query,
        $type,
        $limit = 10,
        $offset = 0

    ) {
        $params = [
            'index' => $this->_index,
            'type' => $type,
            'body' => [

                'size' => $limit,
                'from' => $offset,

                'query'=> [
                    'wildcard' => [
                        '_all' => [
                            'value' => "*".$query."*"
                        ]
                    ]
                ]

            ]
        ];


        $result = $this->_gateway->search($params);

        return [
            'query' => $query,
            $type => $this->getResultFromSearchMultipleType($result)
        ];
    }


    /**
     * @param string $query
     * @param array $types
     * @param array $options
     *
     * @return \Traversable
     */
    function searchSingleIndexMultipleType(
        $query,
        array $types,
        array $options = []
    )
    {
        $params = [];


        foreach ($types as $type){


            $limit = 10;
            $offset = 0;

            $params['body'][] = [
                'index' => $this->_index,
                'type' => $type,
            ];

            if(
                array_key_exists($type.'_limit', $options) &&
                !empty($options[$type.'_limit'])
            ){
                $limit = $options[$type.'_limit'];
            }

            if(
                array_key_exists($type.'_offset', $options) &&
                !empty($options[$type.'_offset'])
            ){
                $offset = $options[$type.'_offset'];
            }


            $params['body'][] = [
                'size' => $limit,
                'from' => $offset,
                'query'=> [

                    'wildcard' => [
                        '_all' => [
                            'value' => "*".$query."*"
                        ]
                    ]
                ]
            ];

        }

        $result = $this->_gateway->msearch($params);
        $responses = $result['responses'];

        $res = []; $i = 0;

        foreach ($responses as $response){
            $res[$types[$i]] = $this->getResultFromSearchMultipletype($response);
            $i++;
        }

        $res['query'] = $query;

        return $res;
    }



    private function getResultFromSearchMultipleType(array $response)
    {
        $d = [];

        $data = $response['hits']['hits'];
        $col = new Collection();


        foreach ($data as $item){
            $type = $item['_type'];
            $identifier = $item['_id'];

            $tags = [];

            if(array_key_exists('name_suggest', $item['_source'])){
                $tags = $item['_source']['name_suggest']['input'];
                unset($item['_source']['name_suggest']);
            }
            $entity = $item['_source'];


            $col->add(new EntitySearchableItem(
                compact('identifier', 'type', 'entity', 'tags')
            ));
        }

        $d['data'] = $col;
        $d['total'] = $response['hits']['total'];

        return $d;
    }


    /**
     * Search auto complete
     * auto complete suggestion
     *
     * @param string $query
     * @param array $types
     * @param int $size
     *
     * @return \Traversable
     */
    function autocomplete($query, array $types, $size = 10)
    {
        $params = [
            'index' => $this->_index,
            'body'  => [
                'suggestions' => [
                    'prefix' => $query,
                    'completion' => [
                        'field'    => 'name_suggest',
                        'size'     => $size,
                        'contexts' => [
                            'suggest_type' => $types
                        ]
                    ]
                ]
            ]

        ];

        $result = $this->_gateway->suggest($params)['suggestions'][0]['options'];

        $out = [];

        foreach ($result as $res){
            $entityArray = $res['_source'];
//            $entityArray['tags'] = $entityArray['name_suggest']['input'];

            unset($entityArray['name_suggest']);

            $out[] = [
                'type'   => $res['_type'],
                'entity' => $entityArray,
                'match'  => $res['text']
            ];

        }

        return $out;

    }
}
