<?php
namespace Module\Searchit\Model\Entity;

use Poirot\Std\Struct\aDataOptions;


class EntitySearchableItem
    extends aDataOptions
{
    /** @var mixed unique identifier */
    protected $identifier;
    /** @var string */
    protected $type;
    /** @var array */
    protected $entity;
    /** @var array */
    protected $tags;


    /**
     * @return mixed
     */
    function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return array
     */
    function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    function getType()
    {
        return $this->type;
    }

    /**
     * @return boolean
     */
    function hasTags()
    {
        return !empty($this->tags);
    }

    /**
     * @return array of strings
     */
    function getTags()
    {
        return $this->tags;
    }
}
