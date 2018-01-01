<?php
namespace Module\Searchit\Model\Entity;

use Poirot\Std\Struct\aDataOptions;


class EntitySearchableType
    extends aDataOptions
{
    /** @var mixed */
    protected $identifier;
    /** @var string */
    protected $description;
    /** @var boolean */
    protected $autocomplete;
    /** @var array */
    protected $searchable_fields;


    /**
     * @return mixed
     */
    function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param mixed $identifier
     * @return $this
     */
    function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * get searchable fields of entity
     * like :
     *      user => (name, username, bio)
     *      post => (title, body)
     *
     * @return array
     */
    function getSearchableFields()
    {
        return $this->searchable_fields;
    }

    /**
     * whether this searchable type support name suggestion (autocomplete)
     *
     * it can be false or true
     * @return boolean
     */
    function getAutocomplete()
    {
        return $this->autocomplete;
    }

    /**
     * syntactical sugar, this method returns exact result of getAutocomplete
     *
     * @return boolean
     */
    function hasAutocomplete()
    {
        return $this->getAutocomplete();
    }

    /**
     * @return string
     */
    function getDescription()
    {
        return $this->description;
    }
}
