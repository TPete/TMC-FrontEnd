<?php

namespace TinyMediaCenter\FrontEnd;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;

/**
 * Class SetupController
 */
class SetupController extends AbstractController
{
    /**
     * Get the index page.
     *
     * @param Request           $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function indexAction(Request $request, ResponseInterface $response)
    {
        $file     = "config.json";
        $knowsAPI = file_exists($file);

        if (false === $knowsAPI) {
            $file = "example_config.json";
        }

        $config = Util::readJSONFile($file);
        $apiConfig = [];

        if ($knowsAPI && $this->api->isValid()) {
            $apiConfig = $this->api->getConfig();
        }

        return $this->twig->render(
            $response,
            "settings/page.html.twig",
            [
                'host'       => $this->host,
                'title'      => 'Einstellungen',
                'target'     => $this->host,
                'config'     => $config,
                'apiConfig'  => $apiConfig,
                'categories' => $this->getNavigationCategories(),
            ]
        );
    }

    /**
     * Update application configuration.
     *
     * @param Request           $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function updateAction(Request $request, ResponseInterface $response)
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

        return $this->redirect($response, $uri, 301);
    }

    /**
     * @param Request           $request
     * @param ResponseInterface $response
     * @param string            $type
     */
    public function checkAction(Request $request, ResponseInterface $response, $type)
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
     * Perform initial DB setup.
     *
     * @param Request           $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function setupDbAction(Request $request, ResponseInterface $response)
    {
        $this->api->setupDB();

        return $this->redirect($response, "http://".$this->host."/install/", 301);
    }

    /**
     * Update library data.
     *
     * @param Request           $request
     * @param ResponseInterface $response
     * @param string            $type
     */
    public function updateLibraryAction(Request $request, ResponseInterface $response, $type)
    {
        try {
            if ($type === 'movies') {
                $res = $this->api->updateMovies();

                echo $res['protocol'];
            } elseif ($type === 'shows') {
                $res = $this->api->updateShows();

                echo $res['protocol'];
            }
        } catch (RemoteException $exp) {
            Util::renderException($exp, $this->host, $this->container, $response);
        }
    }
}
