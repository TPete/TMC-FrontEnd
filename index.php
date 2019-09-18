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

        return $response->withRedirect((string) $uri);
    }

    return $next($request, $response);
});

//Route middleware, added to "show" and "movies" groups
$checkAPI = function () use ($api, $host) {
    return function (Request $request, Response $response, $next) use ($api, $host) {
        if (false === $api->isValid()) {
            return $response->withRedirect('http://'.$host.'/install/');
        }

        return $next($request, $response);
    };
};

// Main index page
$app->get(
    '/',
    function (Request $request, Response $response) use ($host) {
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

// setup/config
$app
    ->group(
        '/install',
        function () {
            $this->get('/', '\TinyMediaCenter\FrontEnd\SetupController:indexAction');

            $this->post('/', '\TinyMediaCenter\FrontEnd\SetupController:updateAction');

            $this->post('/db/', '\TinyMediaCenter\FrontEnd\SetupController:setupDbAction');

            $this->get('/check/{type}/', '\TinyMediaCenter\FrontEnd\SetupController:checkAction');

            $this->post('/update-db/type/{type}/', '\TinyMediaCenter\FrontEnd\SetupController:updateLibraryAction');
        }
    );

// shows
$app
    ->group(
        '/shows/{category}',
        function () {
            $this->get('/', '\TinyMediaCenter\FrontEnd\ShowController:indexAction');

            $this->get('/{id}/', '\TinyMediaCenter\FrontEnd\ShowController:detailsAction');

            $this->post('/{id}/', '\TinyMediaCenter\FrontEnd\ShowController:updateShowAction');

            $this->get('/episodes/{id}/', '\TinyMediaCenter\FrontEnd\ShowController:getEpisodeDescriptionAction');
        }
    )
->add($checkAPI());

// movies
$app
    ->group(
        '/movies/{category}',
        function () {
            $this->get('/lookup/', '\TinyMediaCenter\FrontEnd\MovieController:lookupAction');

            $this->get('/genres/', '\TinyMediaCenter\FrontEnd\MovieController:genresAction');

            $this->get('/', '\TinyMediaCenter\FrontEnd\MovieController:movieAction');

            $this->get('/{id}/', '\TinyMediaCenter\FrontEnd\MovieController:detailsAction');

            $this->post('/{id}/', '\TinyMediaCenter\FrontEnd\MovieController:updateMovieAction');
        }
    )
->add($checkAPI());

$app->run();
