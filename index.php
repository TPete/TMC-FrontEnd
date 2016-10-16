<?php

set_time_limit(900);
require 'vendor/autoload.php';

use TinyMediaCenter\FrontEnd;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

$app = new Slim\App(
    [
        'settings' => [
            'displayErrorDetails' => true,
        ],
    ]
);

// Get container
$container = $app->getContainer();

// Register twig component on container
$container['view'] = function ($container) {
    $view = new Twig(
        'templates',
        ['cache' => false]
    );
    $view->addExtension(new TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));

    return $view;
};

// Use RequestResponseArgs Strategy
$container['foundHandler'] = function () {
    return new \Slim\Handlers\Strategies\RequestResponseArgs();
};

$host   = FrontEnd\Util::getHost();
$config = FrontEnd\Util::getConfig();

$api = new FrontEnd\RestApi($config["restUrl"]);

$container['api']        = $api;
$container['host']       = $host;
$container['categories'] = $api->getCategories();

//Redirect url ending in non-trailing slash to trailing equivalent
$app->add(function (Request $request, Response $response, callable $next) {
    $uri = $request->getUri();
    $path = $uri->getPath();
    if ($path != '/' && substr($path, -1) !== '/') {
        $uri = $uri->withPath($path.'/');

        return $response->withRedirect((string) $uri, 301);
    }

    return $next($request, $response);
});

//Route middleware, added to "show" and "movies" groups
$checkAPI = function (FrontEnd\RestApi $api, $host) {
    return function (Request $request, Response $response, $next) use ($api, $host) {
        if (false === $api->isValid()) {
            return $response->withRedirect('https://'.$host.'/install/', 301);
        }

        return $next($request, $response);
    };
};


$app->get(
    '/',
    function (Request $request, Response $response) use ($app, $host, $api) {
        return $this->view->render(
            $response,
            'main/page.html.twig',
            [
                'host'       => $host,
                'title'      => 'TV',
                'categories' => $this->categories,
            ]
        );
    }
);

$app->get(
    '/install/',
    function (Request $request, Response $response) use ($app, $host, $api) {
        $file = "config.json";
        $knowsAPI = true;
        if (!file_exists($file)) {
            $file     = "example_config.json";
            $knowsAPI = false;
        }
        $config = FrontEnd\Util::readJSONFile($file);
        $apiConfig = [];
        if ($knowsAPI && $api->isValid()) {
            $apiConfig = $api->getConfig();
        }

        $this->view->render(
            $response,
            "settings/page.html.twig",
            [
                'host'       => $host,
                'title'      => 'Einstellungen',
                'target'     => $host,
                'config'     => $config,
                'apiConfig'  => $apiConfig,
                'categories' => $this->categories,
            ]
        );
    }
);

$app->post(
    '/install/',
    function (Request $request, Response $response) use ($app, $host, $api) {
        $config = ["restUrl" => $_POST["restUrl"]];
        FrontEnd\Util::writeJSONFile("config.json", $config);

        if (isset($_POST["pathMovies"])) {
            $config = [
                "pathMovies"  => $_POST["pathMovies"],
                "aliasMovies" => $_POST["aliasMovies"],
                "pathShows"   => $_POST["pathShows"],
                "aliasShows"  => $_POST["aliasShows"],
                "dbHost"      => $_POST["dbHost"],
                "dbName"      => $_POST["dbName"],
                "dbUser"      => $_POST["dbUser"],
                "dbPassword"  => $_POST["dbPassword"],
                "TMDBApiKey"  => $_POST["TMDBApiKey"],
                "TTVDBApiKey" => $_POST["TTVDBApiKey"],
            ];

            $api->updateConfig($config);
        }

        $uri = 'http://'.$host.'/install/';

        return $response->withRedirect($uri, 301);
    }
);

$app->get(
    '/install/check/{type}/',
    function (Request $request, Response $response, $type) {
        if ($type === "restUrl") {
            $api = new FrontEnd\RestAPI($_GET["restUrl"]);
            $res['result'] = $api->isValid() ? "Ok" : "Error";

            echo json_encode($res);
        }
        if ($type === "db") {
            $api = new FrontEnd\RestAPI($_GET["restUrl"]);
            $args = [
                "host"     => $_GET["dbHost"],
                "name"     => $_GET["dbName"],
                "user"     => $_GET["dbUser"],
                "password" => $_GET["dbPassword"],
            ];
            if ($api->isValid()) {
                $res = $api->check("db", $args);
            } else {
                $res['result'] = 'Error';
            }
            echo json_encode($res);
        }
        if ($type === "movies") {
            $api = new FrontEnd\RestAPI($_GET["restUrl"]);
            $args = [
                "pathMovies"  => $_GET["pathMovies"],
                "aliasMovies" => $_GET["aliasMovies"],
            ];
            $res = $api->check("movies", $args);

            echo json_encode($res);
        }
        if ($type === "shows") {
            $api = new FrontEnd\RestAPI($_GET["restUrl"]);
            $args = [
                "pathShows"  => $_GET["pathShows"],
                "aliasShows" => $_GET["aliasShows"],
            ];
            $res = $api->check("shows", $args);

            echo json_encode($res);
        }
    }
);

$app->post(
    '/install/db/',
    function (Request $request, Response $response) use ($app, $host, $api) {
        $api->setupDB();

        return $response->withRedirect("http://".$host."/install/", 301);
    }
);

$app
    ->group(
        '/shows',
        function () {
            $this->post('/update/', '\TinyMediaCenter\FrontEnd\ShowController:updateAction');

            $this->post('/{category}/edit/{id}/', '\TinyMediaCenter\FrontEnd\ShowController:updateShowAction');

            $this->get('/{category}/episodes/{id}/', '\TinyMediaCenter\FrontEnd\ShowController:getEpisodeDescriptionAction');

            $this->get('/{category}/[{id}/]', '\TinyMediaCenter\FrontEnd\ShowController:showAction');
        }
    )
->add($checkAPI($api, $host));

$app
    ->group(
        '/movies',
        function () use ($app, $host, $api) {
            $app->get(
                '/{category}/',
                function (Request $request, Response $response, $category) use ($app, $host, $api) {
                    try {
                        $sort       = FrontEnd\Util::initGET("sort", "name_asc");
                        $filter     = FrontEnd\Util::initGET("filter");
                        $genres     = FrontEnd\Util::initGET("genres");
                        $offset     = FrontEnd\Util::initGET("offset", 0, true);
                        $collection = FrontEnd\Util::initGET("collection", 0, true);
                        $list       = FrontEnd\Util::initGET("list", 0, true);
                        $display    = FrontEnd\Util::initGET("display", "all");
                        $cnt        = 6;

                        if ($collection > 0 or $list > 0) {
                            $filter = "";
                            $genres = "";
                            $sort = "name_asc";
                        }

                        $movies = $api->getMovies($category, $sort, $cnt, $offset, $filter, $genres, $collection, $list);

                        $previous = FrontEnd\Util::getPreviousLink($offset, $cnt, $sort, $filter, $genres, $collection, $list);
                        $next     = FrontEnd\Util::getNextLink($offset, $cnt, $movies["cnt"], $sort, $filter, $genres, $collection, $list);

                        $comp = $api->getCompilations($category);

                        $header = $category;
                        if ($display === "all") {
                            $data = [
                                "host"          => $host,
                                "header"        => $header,
                                "target"        => $host,
                                "searchButtons" => true,
                                "sort"          => $sort,
                                "filter"        => $filter,
                                "genres"        => $genres,
                                "collection"    => $collection,
                                "list"          => $list,
                                "movies"        => $movies["list"],
                                "previous"      => $previous["link"],
                                "next"          => $next["link"],
                                "previousClass" => $previous["class"],
                                "nextClass"     => $next["class"],
                                "lists"         => $comp["lists"],
                                "collections"   => $comp["collections"],
                                'categories'    => $this->categories,
                            ];
                            $this->view->render(
                                $response,
                                "movies/page.html.twig",
                                $data
                            );
                        }
                        if ($display === "overview") {
                            $data = [
                                "movies"        => $movies["list"],
                                "previous"      => $previous["link"],
                                "next"          => $next["link"],
                                "previousClass" => $previous["class"],
                                "nextClass"     => $next["class"],
                            ];
                            $this->view->render(
                                $response,
                                "movies/movieOverview.html.twig",
                                $data
                            );
                        }
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );

            $app->post(
                '/update/',
                function (Request $request, Response $response) use ($app, $host, $api) {
                    try {
                        $res = $api->updateMovies();

                        echo $res["protocol"];
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );

            $app->get(
                '/{category}/lookup/{id}/',
                function (Request $request, Response $response, $category, $id) use ($app, $host, $api) {
                    try {
                        $movie = $api->lookupMovie($_GET["movieDBID"]);

                        if ($movie !== null) {
                            $this->view->render(
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
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );

            $app->get(
                '/{category}/genres/',
                function (Request $request, Response $response, $category) use ($app, $host, $api) {
                    try {
                        $term = FrontEnd\Util::initGET("term", "");
                        $res  = $api->getGenres($category, $term);

                        echo json_encode($res);
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );

            $app->get(
                '/{category}/{id}/',
                function (Request $request, Response $response, $category, $id) use ($app, $host, $api) {
                    try {
                        $movie  = $api->getMovie($category, $id);
                        $output = FrontEnd\Util::initGET("output", "html");

                        if ($output === "html") {
                            $movie["path"]     = $movie["filename"];
                            $movie["filename"] = substr($movie["filename"], strrpos($movie["filename"], "/") + 1);

                            $this->view->render($response, "movies/movieDetails.html.twig", $movie);
                        }
                        if ($output === "edit") {
                            $movieDbId = $movie["movie_db_id"];
                            $this->view->render(
                                $response,
                                "movies/movieDetailsDialog.html.twig",
                                [
                                    "data"        => $movie,
                                    "movie_db_id" => $movieDbId,
                                ]
                            );
                        }
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );

            $app->post(
                '/{category}/{dbid}/',
                function (Request $request, Response $response, $category, $dbid) use ($app, $host, $api) {
                    try {
                        echo $api->updateMovie($category, $dbid, $_POST["movieDBID"], $_POST["filename"]);
                        echo "OK";
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );
        }
    )
->add($checkAPI($api, $host));

$app->run();
