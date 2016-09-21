<?php

set_time_limit(900);
require 'vendor/autoload.php';

use TinyMediaCenter\FrontEnd;

$app = new Slim\Slim(['templates.path' => 'templates/']);

$host   = getHost();
$config = getConfig();

$api    = new FrontEnd\RestApi($config["restUrl"]);

/**
 * @return string
 */
function getHost()
{
    $dir = $_SERVER["SCRIPT_NAME"];
    $dir = substr($dir, 0, strrpos($dir, "/"));
    $host = $_SERVER["HTTP_HOST"].$dir;

    return $host;
}

/**
 * @return array
 */
function getConfig()
{
    $config = readJSONFile("config.json");

    return $config;
}

/**
 * @param string $file
 *
 * @return array
 */
function readJSONFile($file)
{
    $fileData = file_get_contents($file);
    if (!mb_check_encoding($fileData, 'UTF-8')) {
        $fileData = utf8_encode($fileData);
    }
    $res = json_decode($fileData, true);

    return $res;
}

/**
 * @param string $file
 * @param mixed  $data
 *
 * @return bool
 */
function writeJSONFile($file, $data)
{
    $json = json_encode($data);
    $res = file_put_contents($file, $json);

    return ($res !== false);
}

/**
 * @param mixed  $var
 * @param string $default
 * @param bool   $toInt
 *
 * @return int|string
 */
function initGET($var, $default = "", $toInt = false)
{
    $res = isset($_GET[$var]) ? $_GET[$var] : $default;
    $res = trim($res);
    if ($toInt) {
        $res = intval($res, 10);
    }

    return $res;
}

/**
 * @param int    $offset
 * @param int    $cnt
 * @param string $sort
 * @param string $filter
 * @param array  $genres
 * @param int    $collection
 * @param int    $list
 *
 * @return array
 */
function getPreviousLink($offset, $cnt, $sort, $filter, $genres, $collection, $list)
{
    if ($offset > 0) {
        $offsetPrev = $offset - $cnt;
        if ($offsetPrev < 0) {
            $offsetPrev = 0;
        }
        if ($collection === 0 and $list === 0) {
            $tmp = [
                "sort" => $sort,
                "filter" => $filter,
                "genres" => $genres,
                "offset" => $offsetPrev,
            ];
        } else {
            if ($collection > 0) {
                $tmp = [
                    "collection" => $collection,
                    "offset" => $offsetPrev,
                ];
            }
            if ($list > 0) {
                $tmp = [
                    "list" => $list,
                    "offset" => $offsetPrev,
                ];
            }
        }
        $previous = http_build_query($tmp);
        $previousClass = "";
    } else {
        $previous = "javascript: void(0);";
        $previousClass = "disabled";
    }

    return ["link" => $previous, "class" => $previousClass];
}

/**
 * @param int    $offset
 * @param int    $cnt
 * @param int    $moviesCnt
 * @param string $sort
 * @param string $filter
 * @param array  $genres
 * @param int    $collection
 * @param int    $list
 *
 * @return array
 */
function getNextLink($offset, $cnt, $moviesCnt, $sort, $filter, $genres, $collection, $list)
{
    if ($offset + 2 * $cnt <= $moviesCnt) {
        $offsetNext = $offset + $cnt;
        if ($collection === 0 and $list === 0) {
            $tmp = [
                "sort" => $sort,
                "filter" => $filter,
                "genres" => $genres,
                "offset" => $offsetNext,
            ];
        } else {
            if ($collection > 0) {
                $tmp = [
                    "collection" => $collection,
                    "offset" => $offsetNext,
                ];
            }
            if ($list > 0) {
                $tmp = [
                    "list" => $list,
                    "offset" => $offsetNext,
                ];
            }
        }
        $next = http_build_query($tmp);
        $nextClass = "";
    } else {
        if ($moviesCnt - $cnt > $offset) {
            $offsetNext = $moviesCnt - $cnt;
            if ($collection === 0 and $list === 0) {
                $tmp = [
                    "sort" => $sort,
                    "filter" => $filter,
                    "genres" => $genres,
                    "offset" => $offsetNext,
                ];
            } else {
                if ($collection > 0) {
                    $tmp = [
                        "collection" => $collection,
                        "offset" => $offsetNext,
                    ];
                }
                if ($list > 0) {
                    $tmp = [
                        "list" => $list,
                        "offset" => $offsetNext,
                    ];
                }
            }
            $next = http_build_query($tmp);
            $nextClass = "";
        } else {
            $next = "javascript: void(0);";
            $nextClass = "disabled";
        }
    }

    return ["link" => $next, "class" => $nextClass];
}

/**
 * @param FrontEnd\RemoteException $exp
 * @param string                   $host
 * @param Slim\Slim                $app
 */
function renderException(FrontEnd\RemoteException $exp, $host, Slim\Slim $app)
{
    $header = "Error";
    $app->render("pageHeader.php", ["pageTitle" => $header, "host" => $host]);
    $app->render("headerBarShows.php", ["header" => $header, "showEditButton" => false]);
    $app->render("error.php", ["message" => $exp->getMessage(), "trace" => $exp->getStackTrace()]);
    $app->render("pageFooter.php", ["host" => $host]);
}

$checkAPI = function ($api, $host) {
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
        $pageTitle = "Main Index";
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
        $config = readJSONFile($file);
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
        writeJSONFile("config.json", $config);

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
                renderException($exp, $host, $app);
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
                renderException($exp, $host, $app);
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
                renderException($exp, $host, $app);
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
                renderException($exp, $host, $app);
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
                renderException($exp, $host, $app);
            }
        }
    );
});

$app->group('/movies', $checkAPI($api, $host), function () use ($app, $host, $api) {

    $app->get(
        '/:category/',
        function ($category) use ($app, $host, $api) {
            try {
                $sort = initGET("sort", "name_asc");
                $filter = initGET("filter");
                $genres = initGET("genres");
                $offset = initGET("offset", 0, true);
                $collection = initGET("collection", 0, true);
                $list = initGET("list", 0, true);
                $display = initGET("display", "all");
                $cnt = 6;

                if ($collection > 0 or $list > 0) {
                    $filter = "";
                    $genres = "";
                    $sort = "name_asc";
                }

                $movies = $api->getMovies($category, $sort, $cnt, $offset, $filter, $genres, $collection, $list);

                $previous = getPreviousLink($offset, $cnt, $sort, $filter, $genres, $collection, $list);
                $next = getNextLink($offset, $cnt, $movies["cnt"], $sort, $filter, $genres, $collection, $list);

                $header = $category;
                if ($display === "all") {
                    $app->render("pageHeader.php", ["pageTitle" => $header." Index", "host" => $host]);
                    $app->render(
                        "headerBarMovies.php",
                        [
                            "header" => $header,
                            "target" => $host,
                            "searchButtons" => true,
                            "sort" => $sort,
                            "filter" => $filter,
                            "genres" => $genres,
                            "collection" => $collection,
                            "list" => $list,
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
                            "sort" => $sort,
                            "filter" => $filter,
                            "genres" => $genres,
                            "offset" => $offset,
                            "collection" => $collection,
                            "list" => $list,
                        ]
                    );
                    $app->render("pageFooter.php", ["host" => $host]);
                }
                if ($display === "overview") {
                    $app->render(
                        "movieOverview.php",
                        [
                            "movies" => $movies["list"],
                            "previous" => $previous["link"],
                            "next" => $next["link"],
                            "previousClass" => $previous["class"],
                            "nextClass" => $next["class"],
                        ]
                    );
                }
            } catch (FrontEnd\RemoteException $exp) {
                renderException($exp, $host, $app);
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
                renderException($exp, $host, $app);
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
                renderException($exp, $host, $app);
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
                renderException($exp, $host, $app);
            }
        }
    );

    $app->get(
        '/:category/genres/',
        function ($category) use ($app, $host, $api) {
            try {
                $term = initGET("term", "");
                $res = $api->getGenres($category, $term);

                echo json_encode($res);
            } catch (FrontEnd\RemoteException $exp) {
                renderException($exp, $host, $app);
            }
        }
    );

    $app->get(
        '/:category/:id',
        function ($category, $id) use ($app, $host, $api) {
            try {
                $movie = $api->getMovie($category, $id);
                $output = initGET("output", "html");
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
                renderException($exp, $host, $app);
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
                renderException($exp, $host, $app);
            }
        }
    );
});

$app->run();
