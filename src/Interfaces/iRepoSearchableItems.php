<?php
namespace Module\Searchit\Interfaces;
use Module\Searchit\Model\Entity\EntitySearchableItem;


/**
 * This Interface should be implemented by any class which is responsible
 * for interacting an outsource search engine
 *
 * for example elasticsearch driver
 */
interface iRepoSearchableItems
{
    /**
     * Find an indexed document by specifying it's mapping type and Identifier
     *
     * @param $identifier
     * @param string $type
     *
     * @return EntitySearchableItem
     */
    function findByIdentifier($identifier, $type);


    /**
     * Index (Store) a document in search engine
     * $entity object contains both mapping type and array entity to be indexed
     *
     * @param EntitySearchableItem $entity
     *
     * @return EntitySearchableItem
     */
    function insert(EntitySearchableItem $entity);


    /**
     * @param array $types
     *
     * @return mixed
     */
    function mostUsedTags(array $types = []);


    /**
     * Index (store) multiple document in elastic
     *
     * @param \Traversable $entities
     * @return mixed
     */
    function insertBulk(\Traversable $entities);

    /**
     * Delete an indexed document by specifying its identifier an it's mapping type
     *
     * @param $identifier
     * @param $type
     * @return mixed
     */
    function deleteByIdentifier($identifier, $type);


    /**
     * Update an indexed document
     *
     * @param EntitySearchableItem $item
     * @return mixed
     */
    function updateByType(EntitySearchableItem $item);

    /**
     * List all specified searchable items of specified type
     *
     * @param string $type
     * @return mixed
     */
    function findAll($type);

    /**
     * Search through specified type
     *
     * @param string $query
     * @param string $type
     * @param int $limit
     * @param int $offset
     *
     * @return \Traversable
     */
    function searchSingleIndexSingleType(
        $query,
        $type,
        $limit = 10,
        $offset = 0
    );

    /**
     * Search through specified types
     * if no mapping type is specified
     * search will go through all existing types
     *
     *
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
    );


    /**
     * Search auto complete
     * auto complete suggestion
     *
     *
     * @param string $query
     * @param array  $types
     * @param int    $size
     *
     * @return \Traversable
     */
    function autocomplete($query, array $types, $size = 10);
}
