<?php

namespace TinyMediaCenter\FrontEnd;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class SetupController
 */
class SetupController extends AbstractController
{
    /**
     * Get the index page.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function indexAction(Request $request, Response $response)
    {
        try {
            $file = 'config.json';
            $knowsAPI = file_exists($file);

            if (false === $knowsAPI) {
                $file = 'example_config.json';
            }

            $config = Util::readJSONFile($file);
            $apiConfig = [];

            if ($knowsAPI && $this->api->isValid()) {
                $apiConfig = $this->api->getConfig();
            }

            return $this->twig->render(
                $response,
                'settings/page.html.twig',
                [
                    'host'       => $this->host,
                    'title'      => 'Einstellungen',
                    'config'     => $config,
                    'apiConfig'  => $apiConfig,
                    'categories' => $this->getNavigationCategories(),
                ]
            );
        } catch (RemoteException $e) {
            return Util::renderException($e, $this->host, $this->container, $response);
        }
    }

    /**
     * Update application configuration.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function updateAction(Request $request, Response $response)
    {
        try {
            $config = ['restUrl' => $_POST['restUrl']];
            Util::writeJSONFile('config.json', $config);

            if (isset($_POST['pathMovies'])) {
                $config = [
                    'pathMovies'  => $_POST['pathMovies'],
                    'aliasMovies' => $_POST['aliasMovies'],
                    'pathShows'   => $_POST['pathShows'],
                    'aliasShows'  => $_POST['aliasShows'],
                    'dbHost'      => $_POST['dbHost'],
                    'dbName'      => $_POST['dbName'],
                    'dbUser'      => $_POST['dbUser'],
                    'dbPassword'  => $_POST['dbPassword'],
                    'TMDBApiKey'  => $_POST['TMDBApiKey'],
                    'TTVDBApiKey' => $_POST['TTVDBApiKey'],
                ];

                $this->api->updateConfig($config);
            }

            $uri = 'http://'.$this->host.'/install/';

            return $response->withRedirect($uri);
        } catch (RemoteException $e) {
            return Util::renderException($e, $this->host, $this->container, $response);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $type
     *
     * @return ResponseInterface
     */
    public function checkAction(Request $request, Response $response, $type)
    {
        try {
            $types = ['restUrl', 'db', 'movies', 'shows'];
            $res = [];

            if (in_array($type, $types)) {
                $api = new RestAPI($request->getQueryParam('restUrl'));

                if ($type === 'restUrl') {
                    $res['result'] = $api->isValid() ? 'Ok' : 'Error';
                } else {
                    if ('db' === $type) {
                        $args = [
                            'host'     => $request->getQueryParam('dbHost'),
                            'name'     => $request->getQueryParam('dbName'),
                            'user'     => $request->getQueryParam('dbUser'),
                            'password' => $request->getQueryParam('dbPassword'),
                        ];
                    } elseif ('movies' === $type) {
                        $args = [
                            'pathMovies'  => $request->getQueryParam('pathMovies'),
                            'aliasMovies' => $request->getQueryParam('aliasMovies'),
                        ];
                    } else {
                        $args = [
                            'pathShows'  => $request->getQueryParam('pathShows'),
                            'aliasShows' => $request->getQueryParam('aliasShows'),
                        ];
                    }

                    if ($api->isValid()) {
                        $res = $api->check($type, $args);
                    } else {
                        $res['result'] = 'Error';
                    }
                }
            } else {
                $res['result'] = 'Error';
            }

            return $response->withJson($res);
        } catch (RemoteException $e) {
            return Util::renderException($e, $this->host, $this->container, $response);
        }
    }

    /**
     * Perform initial DB setup.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function setupDbAction(Request $request, Response $response)
    {
        try {
            $this->api->setupDB();

            return $response->withRedirect('http://'.$this->host.'/install/');
        } catch (RemoteException $e) {
            return Util::renderException($e, $this->host, $this->container, $response);
        }
    }

    /**
     * Update library data.
     *
     * @param Request  $request
     * @param Response $response
     * @param string   $type
     *
     * @return ResponseInterface
     */
    public function updateLibraryAction(Request $request, Response $response, $type)
    {
        try {
            if ($type === 'movies') {
                $result = $this->api->updateMovies();
            } elseif ($type === 'shows') {
                $result = $this->api->updateShows();
            } else {
                return $response->withStatus(404);
            }

            $response = [];

            foreach ($result as $maintenance) {
                $category = $maintenance['id'];
                $response[] = $category;
                $steps = $maintenance['attributes']['steps'];

                foreach ($steps as $step) {
                    $response[] = '-------------';
                    $response[] = sprintf('%s: %s', $step['description'], $step['success'] ? 'Ok' : 'Failed');

                    foreach ($step['protocol'] as $row) {
                        $response[] = '---'.print_r($row, true);
                    }
                }
            }

            //TODO add template
            //TODO check result
            return implode('<br>', $response);
        } catch (RemoteException $exp) {
            return Util::renderException($exp, $this->host, $this->container, $response);
        }
    }
}
