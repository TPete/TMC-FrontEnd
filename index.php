<?php

set_time_limit(900);
require 'vendor/autoload.php';

use TinyMediaCenter\FrontEnd;

$app = new Slim\Slim(['templates.path' => 'templates/']);

$host = FrontEnd\Util::getHost();
$config = FrontEnd\Util::getConfig();

$api = new FrontEnd\RestApi($config["restUrl"]);

$checkAPI = function (FrontEnd\RestApi $api, $host) {
    return function () use ($api, $host) {
        if (!$api->isValid()) {
            $app = \Slim\Slim::getInstance();
            $app->redirect('http://'.$host.'/install');
        }
    };
};

$app->get(
    '/',
    $checkAPI($api, $host),
    function () use ($app, $host, $api) {
        $header = "TV";
        $app->render("pageHeader.php", ["pageTitle" => $header." Index", "host" => $host]);
        $app->render("headerBarMain.php", ["header" => $header, "host" => $host]);
        $categories = $api->getCategories();
        $app->render("categorySelection.php", ["categories" => $categories]);
        $app->render("pageFooter.php", ["host" => $host]);
    }
);

$app->get(
    '/install',
    function () use ($app, $host, $api) {
        $header = "Install";
        $app->render("pageHeader.php", ["pageTitle" => $header." Index", "host" => $host]);
        $app->render("headerBarMovies.php", ["header" => $header, "target" => $host]);
        $file = "config.json";
        $knowsAPI = true;
        if (!file_exists($file)) {
            $file = "example_config.json";
            $knowsAPI = false;
        }
        $config = FrontEnd\Util::readJSONFile($file);
        $apiConfig = [];
        if ($knowsAPI and $api->isValid()) {
            $apiConfig = $api->getConfig();
        }
        $app->render("install.php", ["host" => $host, "config" => $config, "apiConfig" => $apiConfig]);
        $app->render("pageFooter.php", ["host" => $host]);
    }
);

$app->post(
    '/install',
    function () use ($app, $host, $api) {
        $config = ["restUrl" => $_POST["restUrl"]];
        FrontEnd\Util::writeJSONFile("config.json", $config);

        if (isset($_POST["pathMovies"])) {
            $config = [];
            $config["pathMovies"] = $_POST["pathMovies"];
            $config["aliasMovies"] = $_POST["aliasMovies"];
            $config["pathShows"] = $_POST["pathShows"];
            $config["aliasShows"] = $_POST["aliasShows"];
            $config["dbHost"] = $_POST["dbHost"];
            $config["dbName"] = $_POST["dbName"];
            $config["dbUser"] = $_POST["dbUser"];
            $config["dbPassword"] = $_POST["dbPassword"];
            $config["TMDBApiKey"] = $_POST["TMDBApiKey"];
            $config["TTVDBApiKey"] = $_POST["TTVDBApiKey"];

            $api->updateConfig($config);
        }

        $app->redirect('http://'.$host.'/install');
    }
);

$app->get(
    '/install/check/:type/',
    function ($type) {
        if ($type === "restUrl") {
            $api = new FrontEnd\RestAPI($_GET["restUrl"]);
            echo $api->isValid() ? "Ok" : "Error";
        }
        if ($type === "db") {
            $api = new FrontEnd\RestAPI($_GET["restUrl"]);
            $args = [
                "host" => $_GET["dbHost"],
                "name" => $_GET["dbName"],
                "user" => $_GET["dbUser"],
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
                "pathMovies" => $_GET["pathMovies"],
                "aliasMovies" => $_GET["aliasMovies"],
            ];
            $res = $api->check("movies", $args);

            echo $res["result"];
        }
        if ($type === "shows") {
            $api = new FrontEnd\RestAPI($_GET["restUrl"]);
            $args = [
                "pathShows" => $_GET["pathShows"],
                "aliasShows" => $_GET["aliasShows"],
            ];
            $res = $api->check("shows", $args);

            echo json_encode($res);
        }
    }
);

$app->post(
    '/install/db',
    function () use ($app, $host, $api) {
        $api->setupDB();
        $app->redirect("http://".$host."/install");
    }
);

$app->group('/shows', $checkAPI($api, $host), function () use ($app, $host, $api) {
    $app->post(
        '/update',
        function () use ($app, $host, $api) {
            try {
                $res = $api->updateShows();

                echo $res["protocol"];
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );

    $app->get(
        '/:category/edit/:id',
        function ($category, $id) use ($app, $api, $host) {
            try {
                $details = $api->getShowDetails($category, $id);
                $app->render(
                    "showEdit.php",
                    [
                        "category" => $category,
                        "id" => $id,
                        "title" => $details["title"],
                        "tvdbId" => $details["tvdbId"],
                        "lang" => $details["lang"],
                    ]
                );
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );

    $app->post(
        '/:category/edit/:id',
        function ($category, $id) use ($app, $api, $host) {
            try {
                $api->updateShowDetails($category, $id, $_POST["title"], $_POST["tvdbId"], $_POST["lang"]);

                $app->redirect("http://".$host.'/shows/'.$category.'/'.$id);
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );

    $app->get(
        '/:category/episodes/:id',
        function ($category, $id) use ($app, $api, $host) {
            try {
                $data = $api->getEpisodeDescription($category, $id);

                $app->render("episodeDetails.php", $data);
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );

    $app->get(
        '/:category/(:id)',
        function ($category, $id = "") use ($app, $host, $api) {
            try {
                if (strlen($id) === 0) {
                    $data = $api->getCategoryOverview($category);
                    $header = ucfirst($category);
                    $target = $host;
                    $content = "categoryOverview.php";
                    $contentParams = ["overview" => $data];
                    $showEditButton = false;
                } else {
                    $data = $api->getShowDetails($category, $id);
                    $header = $data["title"];
                    $target = $host."/shows/".$category."/";
                    $content = "episodesList.php";
                    $contentParams = [
                        "showData" => $data["seasons"],
                        "imageUrl" => $data["imageUrl"],
                    ];
                    $showEditButton = true;
                }
                $app->render("pageHeader.php", ["pageTitle" => $header, "host" => $host]);
                $app->render("headerBarShows.php", ["header" => $header, "target" => $target, "showEditButton" => $showEditButton]);
                $app->render($content, $contentParams);
                $app->render("pageFooter.php", ["host" => $host]);
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );
});

$app->group('/movies', $checkAPI($api, $host), function () use ($app, $host, $api) {

    $app->get(
        '/:category/',
        function ($category) use ($app, $host, $api) {
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
                    $app->render("pageHeader.php", ["pageTitle" => $header." Index", "host" => $host]);
                    $app->render(
                        "headerBarMovies.php",
                        [
                            "header"        => $header,
                            "target"        => $host,
                            "searchButtons" => true,
                            "sort"          => $sort,
                            "filter"        => $filter,
                            "genres"        => $genres,
                            "collection"    => $collection,
                            "list"          => $list,
                        ]
                    );
                    $view = $app->view();
                    $view->setTemplatesDirectory("templates/");
                    $view->clear();
                    $view->set("movies", $movies["list"]);
                    $view->set("previous", $previous["link"]);
                    $view->set("next", $next["link"]);
                    $view->set("previousClass", $previous["class"]);
                    $view->set("nextClass", $next["class"]);
                    $movieOverview = $view->fetch("movieOverview.php");
                    $app->render(
                        "movieWrapper.php",
                        [
                            "movieOverview" => $movieOverview,
                            "sort"          => $sort,
                            "filter"        => $filter,
                            "genres"        => $genres,
                            "offset"        => $offset,
                            "collection"    => $collection,
                            "list"          => $list,
                        ]
                    );
                    $app->render("pageFooter.php", ["host" => $host]);
                }
                if ($display === "overview") {
                    $app->render(
                        "movieOverview.php",
                        [
                            "movies"        => $movies["list"],
                            "previous"      => $previous["link"],
                            "next"          => $next["link"],
                            "previousClass" => $previous["class"],
                            "nextClass"     => $next["class"],
                        ]
                    );
                }
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );

    $app->post(
        '/update',
        function () use ($app, $host, $api) {
            try {
                $res = $api->updateMovies();

                echo $res["protocol"];
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );

    $app->get(
        '/:category/search/',
        function ($category) use ($app, $host, $api) {
            try {
                $comp = $api->getCompilations($category);
                $app->render("movieSearch.php", ["lists" => $comp["lists"], "collections" => $comp["collections"]]);
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );

    $app->get(
        '/:category/lookup/:id',
        function ($category, $id) use ($app, $host, $api) {
            try {
                $movie = $api->lookupMovie($_GET["movieDBID"]);
                if ($movie !== null) {
                    $app->render("movieDetailsDialog.php", ["data" => $movie, "movie_db_id" => $_GET["movieDBID"]]);
                } else {
                    echo "No Match";
                }
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );

    $app->get(
        '/:category/genres/',
        function ($category) use ($app, $host, $api) {
            try {
                $term = FrontEnd\Util::initGET("term", "");
                $res  = $api->getGenres($category, $term);

                echo json_encode($res);
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );

    $app->get(
        '/:category/:id',
        function ($category, $id) use ($app, $host, $api) {
            try {
                $movie  = $api->getMovie($category, $id);
                $output = FrontEnd\Util::initGET("output", "html");
                if ($output === "html") {
                    $movie["path"] = $movie["filename"];
                    $movie["filename"] = substr($movie["filename"], strrpos($movie["filename"], "/") + 1);
                    $app->render("movieDetails.php", $movie);
                }
                if ($output === "edit") {
                    $movieDbId = $movie["movie_db_id"];
                    $app->render("movieDetailsDialog.php", ["data" => $movie, "movie_db_id" => $movieDbId]);
                }
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );

    $app->post(
        '/:category/:dbid',
        function ($category, $dbid) use ($app, $host, $api) {
            try {
                echo $api->updateMovie($category, $dbid, $_POST["movieDBID"], $_POST["filename"]);
                echo "OK";
            } catch (FrontEnd\RemoteException $exp) {
                FrontEnd\Util::renderException($exp, $host, $app);
            }
        }
    );
});

$app->run();
