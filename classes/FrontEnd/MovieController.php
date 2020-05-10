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
            $fetchSize = self::FETCH_SIZE;

            if ($collection > 0) {
                $filter = '';
                $genre = '';
                $sort = 'name_asc';
            }

            $movies = $this->api->getMovies($category, $sort, $fetchSize, $offset, $filter, $genre, $collection);
            $collectionsOptions = array_map(function ($resource) {
                return ['id' => $resource['id'], 'name' => $resource['attributes']['name']];
            }, $this->api->getCollections($category)); //TODO use models for api response
            $genreOptions = array_map(function ($resource) {
                return $resource['id'];
            }, $this->api->getGenres($category)); //TODO use models for api response

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
                'movies' => $movies,
                'genre' => $genre,
                'genreOptions' => $genreOptions,
                'collection' => $collection,
                'collectionOptions' => $collectionsOptions,
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
                    'title' => $movie['attributes']['title'],
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
     *
     * @return ResponseInterface
     */
    public function updateMovieAction(Request $request, Response $response, $category, $id)
    {
        try {
            $details = $this->api->updateMovie($category, $id, $_POST['movieDbId'], $_POST['filename']);

            if (isset($details['type']) && $details['type'] === 'movie') {
                $data = ['status' => 'Ok'];
            } else {
                $data = ['status' => 'Error'];
            }

            return $response->withJson($data);
        } catch (RemoteException $exp) {
            return Util::renderException($exp, $this->host, $this->container, $response);
        }
    }
}
