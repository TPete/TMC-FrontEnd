<?php

namespace TinyMediaCenter\FrontEnd;

use Psr\Http\Message\ResponseInterface;
use Slim\Container;
use Slim\Http\Response;
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

    /**
     * @param ResponseInterface $response
     * @param string            $uri
     * @param int|null          $status
     *
     * @throws \Exception
     *
     * @return Response
     */
    protected function redirect(ResponseInterface $response, $uri, $status)
    {
        if ($response instanceof Response) {
            return $response->withRedirect($uri, $status);
        }

        throw new \Exception('Redirect Failed');
    }
}
