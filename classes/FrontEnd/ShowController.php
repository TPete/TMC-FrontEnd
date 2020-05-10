<?php

namespace TinyMediaCenter\FrontEnd;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class ShowController
 */
class ShowController extends AbstractController
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param string   $id
     *
     * @return ResponseInterface
     */
    public function updateShowAction(Request $request, Response $response, $category, $id)
    {
        try {
            $this->api->updateShowDetails($category, $id, $_POST["title"], $_POST["tvdbId"], $_POST["lang"]);

            $url = "http://".$this->host.'/shows/'.$category.'/'.$id;

            return $response->withRedirect($url);
        } catch (RemoteException $e) {
            return Util::renderException($e, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param int      $showId
     * @param int      $episodeId
     *
     * @return ResponseInterface
     */
    public function getEpisodeDescriptionAction(Request $request, Response $response, $category, $showId, $episodeId)
    {
        try {
            $data = $this->api->getEpisodeDescription($category, $showId, $episodeId);
            $data['link'] = $request->getQueryParam('link');

            return $this->twig->render(
                $response,
                "shows/details/episodeDetailsAjax.html.twig",
                $data
            );
        } catch (RemoteException $e) {
            return Util::renderException($e, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     *
     * @return ResponseInterface
     */
    public function indexAction(Request $request, Response $response, $category)
    {
        try {
            $data   = $this->api->getCategoryOverview($category);
            $title  = ucfirst($category);
            $target = $this->host;

            return $this->twig->render(
                $response,
                'shows/overview/page.html.twig',
                [
                    'host'           => $this->host,
                    'title'          => $title,
                    'target'         => $target,
                    'overview'       => $data,
                    'showEditButton' => false,
                    'categories'     => $this->getNavigationCategories(),
                ]
            );
        } catch (RemoteException $e) {
            return Util::renderException($e, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param int      $id
     *
     * @return ResponseInterface
     */
    public function detailsAction(Request $request, Response $response, $category, $id)
    {
        try {
            $data   = $this->api->getShowDetails($category, $id);
            $title  = $data['attributes']["title"];
            $target = $this->host."/shows/".$category."/";

            return $this->twig->render(
                $response,
                'shows/details/page.html.twig',
                [
                    'host' => $this->host,
                    'title' => $title,
                    'target' => $target,
                    'overview' => $data,
                    'showEditButton' => true,
                    'imageUrl' => $data['attributes']['background'],
                    'seasons' => $data['included'],
                    "tvdbId" => $data['attributes']["tvdbId"],
                    "url" => "http://".$this->host.'/shows/'.$category.'/'.$id.'/',
                    'categories' => $this->getNavigationCategories(),
                ]
            );
        } catch (RemoteException $e) {
            return Util::renderException($e, $this->host, $this->container, $response);
        }
    }
}
