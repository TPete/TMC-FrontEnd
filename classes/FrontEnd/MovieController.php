<?php

namespace TinyMediaCenter\FrontEnd;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class MovieController
 */
class MovieController extends AbstractController
{
    const FETCH_SIZE = 6;

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     *
     * @return ResponseInterface
     */
    public function movieAction(Request $request, Response $response, $category)
    {
        try {
            $sort = $request->getQueryParam('sort', 'name_asc');
            $filter = $request->getQueryParam('filter');
            $genre = $request->getQueryParam('genre');
            $offset = intval($request->getQueryParam('offset', 0));
            $collection = intval($request->getQueryParam('collection', 0));
            $list = intval($request->getQueryParam('list', 0));
            $fetchSize = self::FETCH_SIZE;

            if ($collection > 0 or $list > 0) {
                $filter = '';
                $genre = '';
                $sort = 'name_asc';
            }

            $movies = $this->api->getMovies($category, $sort, $fetchSize, $offset, $filter, $genre, $collection, $list);
            $comp = $this->api->getCompilations($category);
            $genreOptions = $this->api->getGenres($category);

            $data = [
                'header' => $category,
                'host' => $this->host,
                'target' => $this->host,
                'sort' => $sort,
                'filter' => $filter,
                'categories' => $this->container['categories'],
                'title' => $category,
                'fetchSize' => $fetchSize,
                'category' => $category,
                'movies' => $movies['list'],
                'genre' => $genre,
                'genreOptions' => $genreOptions,
                'collection' => $collection,
                'collectionOptions' => $comp['collections'],
                'list' => $list,
                'listOptions' => $comp['lists'],
            ];

            if ($request->isXhr()) {
                return $this->twig->render(
                    $response,
                    'movies/movieWrapper.html.twig',
                    $data
                );
            }

            return $this->twig->render(
                $response,
                'movies/page.html.twig',
                $data
            );
        } catch (RemoteException $exp) {
            return Util::renderException($exp, $this->host, $this->container, $response);
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
            $movie = $this->api->getMovie($category, $id);
            $format = $request->getQueryParam('format', 'json');

            if ('html' === $format) {
                return $this->twig->render(
                    $response,
                    'movies/detailsDialog.html.twig',
                    ['movie' => $movie]
                );
            } else {
                return $response->withJson([
                    'status' => 'Ok',
                    'title' => $movie['title'],
                    'template' => $this->twig->fetch('movies/detailsDialog.html.twig', ['movie' => $movie]),
                ]);
            }

        } catch (RemoteException $exp) {
            return Util::renderException($exp, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     *
     * @return ResponseInterface
     */
    public function lookupAction(Request $request, Response $response, $category)
    {
        try {
            $movie = $this->api->lookupMovie($request->getQueryParam('movieDbId'));
            $json = [
                'status' => 'Ok',
                'data'   => null !== $movie ? $movie : [],
            ];

            return $response->withJson($json);
        } catch (RemoteException $exp) {
            return Util::renderException($exp, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     */
    public function genresAction(Request $request, Response $response, $category)
    {
        try {
            $term = $request->getQueryParam('term', '');
            $res  = $this->api->getGenres($category, $term);

            echo json_encode($res);
        } catch (RemoteException $exp) {
            Util::renderException($exp, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param int      $id
     */
    public function editAction(Request $request, Response $response, $category, $id)
    {
        throw new \Exception('Gibt es nicht mehr');
//
//        try {
//            $movie  = $this->api->getMovie($category, $id);
//            $output = $request->getQueryParam('output', 'html');
//
//            if ($output === 'html') {
//                $movie['path']     = $movie['filename'];
//                $movie['filename'] = substr($movie['filename'], strrpos($movie['filename'], '/') + 1);
//
//                $this->twig->render($response, 'movies/movieCard.html.twig', $movie);
//            }
//            if ($output === 'edit') {
//                $movieDbId = $movie['movie_db_id'];
//                $this->twig->render(
//                    $response,
//                    'movies/movieDetailsDialog.html.twig',
//                    [
//                        'data'        => $movie,
//                        'movie_db_id' => $movieDbId,
//                    ]
//                );
//            }
//        } catch (RemoteException $exp) {
//            Util::renderException($exp, $this->host, $this->container, $response);
//        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $category
     * @param int      $id
     *
     * @return ResponseInterface
     */
    public function updateMovieAction(Request $request, Response $response, $category, $id)
    {
        try {
            $json = $this->api->updateMovie($category, $id, $_POST['movieDbId'], $_POST['filename']);

            return $response->withJson($json);
        } catch (RemoteException $exp) {
            Util::renderException($exp, $this->host, $this->container, $response);
        }
    }
}
