<?php

namespace TinyMediaCenter\FrontEnd;

use Slim\Container;
use Slim\Views\Twig;

/**
 * Class AbstractController
 */
abstract class AbstractController
{
    /** @var  Container */
    protected $container;

    /** @var  RestApi */
    protected $api;

    /** @var  string */
    protected $host;

    /** @var  Twig */
    protected $twig;

    /**
     * SetupController constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->api       = $container['api'];
        $this->host      = $container['host'];
        $this->twig      = $container['view'];
    }

    /**
     * @return array
     */
    protected function getNavigationCategories()
    {
        return $this->container['categories'];
    }
}
