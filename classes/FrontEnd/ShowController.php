<?php

namespace TinyMediaCenter\FrontEnd;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class ShowController
 */
class ShowController
{
    /** @var  Container */
    private $container;

    /** @var  RestApi */
    private $api;

    /** @var  string */
    private $host;

    /**
     * ShowController constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->api = $container['api'];
        $this->host = $container['host'];
    }

    /**
     * @param Request  $request
     * @param Response $response
     */
    public function updateAction(Request $request, Response $response)
    {
        try {
            $res = $this->api->updateShows();

            echo $res["protocol"];
        } catch (RemoteException $exp) {
            Util::renderException($exp, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $id
     *
     * @return Response
     */
    public function updateShowAction(Request $request, Response $response, $category, $id)
    {
        try {
            $this->api->updateShowDetails($category, $id, $_POST["title"], $_POST["tvdbId"], $_POST["lang"]);

            $url = "http://".$this->host.'/shows/'.$category.'/'.$id;

            return $response->withRedirect($url, 302);
        } catch (RemoteException $exp) {
            Util::renderException($exp, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $id
     */
    public function getEpisodeDescriptionAction(Request $request, Response $response, $category, $id)
    {
        try {
            $data = $this->api->getEpisodeDescription($category, $id);
            $data['link'] = $_GET['link'];

            $this->container->view->render(
                $response,
                "shows/details/episodeDetailsAjax.html.twig",
                $data
            );
        } catch (RemoteException $exp) {
            Util::renderException($exp, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $id
     */
    public function showAction(Request $request, Response $response, $category, $id)
    {
        try {
            if (empty($id)) {
                $data = $this->api->getCategoryOverview($category);
                $title = ucfirst($category);
                $target = $this->host;

                $this->container->view->render(
                    $response,
                    'shows/overview/page.html.twig',
                    [
                        'host'           => $this->host,
                        'title'          => $title,
                        'target'         => $target,
                        'overview'       => $data,
                        'showEditButton' => false,
                        'categories'     => $this->container->categories,
                    ]
                );
            } else {
                $data = $this->api->getShowDetails($category, $id);
                $title = $data["title"];
                $target = $this->host."/shows/".$category."/";

                $this->container->view->render(
                    $response,
                    'shows/details/page.html.twig',
                    [
                        'host'           => $this->host,
                        'title'          => $title,
                        'target'         => $target,
                        'overview'       => $data,
                        'showEditButton' => true,
                        'imageUrl'       => $data['imageUrl'],
                        'showData'       => $data['seasons'],
                        "tvdbId"         => $data["tvdbId"],
                        "url"            => "http://".$this->host.'/shows/'.$category.'/edit/'.$id.'/',
                        'categories'     => $this->container->categories,
                    ]
                );
            }
        } catch (RemoteException $exp) {
            Util::renderException($exp, $this->host, $this->container, $response);
        }
    }
}
