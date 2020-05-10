<?php

namespace TinyMediaCenter\FrontEnd\Model;

/**
 * Class Category
 */
class Category
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $subCategories;

    /**
     * Category constructor.
     *
     * @param string $id
     * @param array  $subCategories
     */
    public function __construct($id, array $subCategories)
    {
        $this->id = $id;
        $this->subCategories = $subCategories;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Category
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return array
     */
    public function getSubCategories()
    {
        return $this->subCategories;
    }

    /**
     * @param array $subCategories
     * @return Category
     */
    public function setSubCategories($subCategories)
    {
        $this->subCategories = $subCategories;

        return $this;
    }
}
