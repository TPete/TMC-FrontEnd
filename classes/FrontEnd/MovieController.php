<?php

namespace TinyMediaCenter\FrontEnd;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;

/**
 * Class MovieController
 */
class MovieController extends AbstractController
{
    const FETCH_SIZE = 6;

    /**
     * @param Request           $request
     * @param ResponseInterface $response
     * @param string            $category
     *
     * @return ResponseInterface
     */
    public function movieAction(Request $request, ResponseInterface $response, $category)
    {
        try {
            $sort       = $request->getQueryParam("sort", "name_asc");
            $filter     = $request->getQueryParam("filter");
            $genres     = $request->getQueryParam("genres");
            $offset     = intval($request->getQueryParam("offset", 0));
            $collection = intval($request->getQueryParam("collection", 0));
            $list       = intval($request->getQueryParam("list", 0));
            $fetchSize  = self::FETCH_SIZE;

            if ($collection > 0 or $list > 0) {
                $filter = "";
                $genres = "";
                $sort = "name_asc";
            }

            $movies = $this->api->getMovies($category, $sort, $fetchSize, $offset, $filter, $genres, $collection, $list);
            $comp = $this->api->getCompilations($category);

            $header = $category;

            $data = [
                "host"          => $this->host,
                "header"        => $header,
                "target"        => $this->host,
                "sort"          => $sort,
                "filter"        => $filter,
                "genres"        => $genres,
                "collection"    => $collection,
                "list"          => $list,
                "movies"        => $movies["list"],
                "lists"         => $comp["lists"],
                "collections"   => $comp["collections"],
                'categories'    => $this->container['categories'],
                'title'         => $category,
                'fetchSize'     => $fetchSize,
                'category'      => $category,
            ];

            if ($request->isXhr()) {
                return $this->twig->render(
                    $response,
                    "movies/movieWrapper.html.twig",
                    $data
                );
            }

            return $this->twig->render(
                $response,
                "movies/page.html.twig",
                $data
            );
        } catch (RemoteException $exp) {
            return Util::renderException($exp, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request           $request
     * @param ResponseInterface $response
     * @param string            $category
     * @param int               $id
     *
     * @return ResponseInterface
     */
    public function lookupAction(Request $request, ResponseInterface $response, $category, $id)
    {
        try {
            $movie = $this->api->lookupMovie($_GET["movieDBID"]);

            if ($movie !== null) {
                return $this->twig->render(
                    $response,
                    "movies/movieDetailsDialog.html.twig",
                    [
                        "data"        => $movie,
                        "movie_db_id" => $_GET["movieDBID"],
                    ]
                );
            } else {
                echo "No Match";
            }
        } catch (RemoteException $exp) {
            return Util::renderException($exp, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request           $request
     * @param ResponseInterface $response
     * @param string            $category
     */
    public function genresAction(Request $request, ResponseInterface $response, $category)
    {
        try {
            $term = $request->getQueryParam("term", "");
            $res  = $this->api->getGenres($category, $term);

            echo json_encode($res);
        } catch (RemoteException $exp) {
            Util::renderException($exp, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request           $request
     * @param ResponseInterface $response
     * @param string            $category
     * @param int               $id
     */
    public function editAction(Request $request, ResponseInterface $response, $category, $id)
    {
        try {
            $movie  = $this->api->getMovie($category, $id);
            $output = $request->getQueryParam("output", "html");

            if ($output === "html") {
                $movie["path"]     = $movie["filename"];
                $movie["filename"] = substr($movie["filename"], strrpos($movie["filename"], "/") + 1);

                $this->twig->render($response, "movies/movieCard.html.twig", $movie);
            }
            if ($output === "edit") {
                $movieDbId = $movie["movie_db_id"];
                $this->twig->render(
                    $response,
                    "movies/movieDetailsDialog.html.twig",
                    [
                        "data"        => $movie,
                        "movie_db_id" => $movieDbId,
                    ]
                );
            }
        } catch (RemoteException $exp) {
            Util::renderException($exp, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request           $request
     * @param ResponseInterface $response
     * @param string            $category
     * @param int               $dbid
     */
    public function updateMovieAction(Request $request, ResponseInterface $response, $category, $dbid)
    {
        try {
            echo $this->api->updateMovie($category, $dbid, $_POST["movieDBID"], $_POST["filename"]);
            echo "OK";
        } catch (RemoteException $exp) {
            Util::renderException($exp, $this->host, $this->container, $response);
        }
    }
}
