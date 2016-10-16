<?php

namespace TinyMediaCenter\FrontEnd;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class SetupController
 */
class SetupController
{
    /** @var  Container */
    private $container;

    /** @var  RestApi */
    private $api;

    /** @var  string */
    private $host;

    /**
     * SetupController constructor.
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
    public function indexAction(Request $request, Response $response)
    {
        $file     = "config.json";
        $knowsAPI = true;
        if (!file_exists($file)) {
            $file     = "example_config.json";
            $knowsAPI = false;
        }
        $config = Util::readJSONFile($file);
        $apiConfig = [];
        if ($knowsAPI && $this->api->isValid()) {
            $apiConfig = $this->api->getConfig();
        }

        $this->container->view->render(
            $response,
            "settings/page.html.twig",
            [
                'host'       => $this->host,
                'title'      => 'Einstellungen',
                'target'     => $this->host,
                'config'     => $config,
                'apiConfig'  => $apiConfig,
                'categories' => $this->container->categories,
            ]
        );
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function updateAction(Request $request, Response $response)
    {
        $config = ["restUrl" => $_POST["restUrl"]];
        Util::writeJSONFile("config.json", $config);

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

            $this->api->updateConfig($config);
        }

        $uri = 'http://'.$this->host.'/install/';

        return $response->withRedirect($uri, 301);
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $type
     */
    public function checkAction(Request $request, Response $response, $type)
    {
        $types = [
            'restUrl',
            'db',
            'movies',
            'shows',
        ];
        if (in_array($type, $types)) {
            $api = new RestAPI($_GET["restUrl"]);
            array_shift($types);

            if ($type === 'restUrl') {
                $res['result'] = $api->isValid() ? "Ok" : "Error";

                echo json_encode($res);
            } elseif (in_array($type, $types)) {
                $args = [];
                if ($type === "db") {
                    $args = [
                        "host"     => $_GET["dbHost"],
                        "name"     => $_GET["dbName"],
                        "user"     => $_GET["dbUser"],
                        "password" => $_GET["dbPassword"],
                    ];
                }
                if ($type === "movies") {
                    $args = [
                        "pathMovies"  => $_GET["pathMovies"],
                        "aliasMovies" => $_GET["aliasMovies"],
                    ];
                }
                if ($type === "shows") {
                    $args = [
                        "pathShows"  => $_GET["pathShows"],
                        "aliasShows" => $_GET["aliasShows"],
                    ];
                }

                if ($api->isValid()) {
                    $res = $api->check($type, $args);
                } else {
                    $res['result'] = 'Error';
                }
                echo json_encode($res);
            }
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function setupDbAction(Request $request, Response $response)
    {
        $this->api->setupDB();

        return $response->withRedirect("http://".$this->host."/install/", 301);
    }
}
