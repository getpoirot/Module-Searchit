<?php
namespace Module\Searchit\Interfaces;

use Module\Searchit\Model\Entity\EntitySearchableType;


interface iRepoSearchableTypes
{
    /**
     * @param EntitySearchableType $searchableTypeEntity
     *
     * @return EntitySearchableType
     */
    function insert(EntitySearchableType $searchableTypeEntity);

    /**
     * @param $identifier
     *
     * @return EntitySearchableType
     */
    function findByIdentifier($identifier);

    /**
     * @param $identifier
     * @return boolean
     */
    function deleteByIdentifier($identifier);

    /**
     * @param array $identifiers
     * @return mixed
     */
    function findManyByIdentifiers(array  $identifiers);

    /**
     * @param array $names
     * @return mixed
     */
    function findManyByNames(array $names);

    /**
     * @return mixed
     */
    function findAll();
}
