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

$container['foundHandler'] = function () {
    return new \Slim\Handlers\Strategies\RequestResponseArgs();
};

$host   = FrontEnd\Util::getHost();
$config = FrontEnd\Util::getConfig();

$api = new FrontEnd\RestApi($config["restUrl"]);

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
        $categories = $api->getCategories();

        return $this->view->render(
            $response,
            'main/page.html.twig',
            [
                'host'       => $host,
                'title'      => 'TV',
                'categories' => $categories,
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
                'host'      => $host,
                'header'    => 'Install',
                'target'    => $host,
                "config"    => $config,
                "apiConfig" => $apiConfig,
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
            echo $api->isValid() ? "Ok" : "Error";
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
                echo json_encode($res);
            } else {
                echo "Error";
            }
        }
        if ($type === "movies") {
            $api = new FrontEnd\RestAPI($_GET["restUrl"]);
            $args = [
                "pathMovies"  => $_GET["pathMovies"],
                "aliasMovies" => $_GET["aliasMovies"],
            ];
            $res = $api->check("movies", $args);

            echo $res["result"];
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
        function () use ($app, $host, $api) {
            $app->post(
                '/update/',
                function (Request $request, Response $response) use ($app, $host, $api) {
                    try {
                        $res = $api->updateShows();

                        echo $res["protocol"];
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );

            $app->get(
                '/{category}/edit/{id}/',
                function (Request $request, Response $response, $category, $id) use ($app, $api, $host) {
                    try {
                        $details = $api->getShowDetails($category, $id);

                        $this->view->render(
                            $response,
                            "showDetails/editDialog.html.twig",
                            [
                                "url"    => "http://".$host.'/shows/'.$category.'/edit/'.$id.'/',
                                "title"  => $details["title"],
                                "tvdbId" => $details["tvdbId"],
                                "lang"   => $details["lang"],
                            ]
                        );
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );

            $app->post(
                '/{category}/edit/{id}/',
                function (Request $request, Response $response, $category, $id) use ($app, $api, $host) {
                    try {
                        $api->updateShowDetails($category, $id, $_POST["title"], $_POST["tvdbId"], $_POST["lang"]);

                        $url = "http://".$host.'/shows/'.$category.'/'.$id;

                        return $response->withRedirect($url, 302);
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );

            $app->get(
                '/{category}/episodes/{id}/',
                function (Request $request, Response $response, $category, $id) use ($app, $api, $host) {
                    try {
                        $data = $api->getEpisodeDescription($category, $id);

                        $this->view->render(
                            $response,
                            "showDetails/episodeDetailsAjax.html.twig",
                            $data
                        );
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );

            $this->get(
                '/{category}/[{id}/]',
                function (Request $request, Response $response, $category, $id) use ($app, $host, $api) {
                    try {
                        if (empty($id)) {
                            $data   = $api->getCategoryOverview($category);
                            $title  = ucfirst($category);
                            $target = $host;

                            $this->view->render(
                                $response,
                                'categoryOverview/page.html.twig',
                                [
                                    'host'           => $host,
                                    'title'          => $title,
                                    'target'         => $target,
                                    'overview'       => $data,
                                    'showEditButton' => false,
                                ]
                            );
                        } else {
                            $data   = $api->getShowDetails($category, $id);
                            $title  = $data["title"];
                            $target = $host."/shows/".$category."/";

                            $this->view->render(
                                $response,
                                'showDetails/page.html.twig',
                                [
                                    'host'           => $host,
                                    'title'          => $title,
                                    'target'         => $target,
                                    'overview'       => $data,
                                    'showEditButton' => true,
                                    'imageUrl'       => $data['imageUrl'],
                                    'showData'       => $data['seasons'],
                                ]
                            );
                        }
                    } catch (FrontEnd\RemoteException $exp) {
                        FrontEnd\Util::renderException($exp, $host, $this, $response);
                    }
                }
            );
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
                            ];
                            $this->view->render(
                                $response,
                                "movieOverview/page.html.twig",
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
                                "movieOverview/movieOverview.html.twig",
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
                '/{category}/search/',
                function (Request $request, Response $response, $category) use ($app, $host, $api) {
                    try {
                        $comp = $api->getCompilations($category);

                        $this->view->render(
                            $response,
                            "movieOverview/movieSearch.html.twig",
                            [
                                "lists"       => $comp["lists"],
                                "collections" => $comp["collections"],
                            ]
                        );
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
                                "movieOverview/movieDetailsDialog.html.twig",
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

                            $this->view->render($response, "movieOverview/movieDetails.html.twig", $movie);
                        }
                        if ($output === "edit") {
                            $movieDbId = $movie["movie_db_id"];
                            $this->view->render(
                                $response,
                                "movieOverview/movieDetailsDialog.html.twig",
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
