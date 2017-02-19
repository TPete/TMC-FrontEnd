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

// Main index page
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
->add($checkAPI($api, $host));

// movies
$app
    ->group(
        '/movies/{category}',
        function () use ($app, $host, $api) {
            $app->get('/', '\TinyMediaCenter\FrontEnd\MovieController:movieAction');

            $app->get('/lookup/{id}/', '\TinyMediaCenter\FrontEnd\MovieController:lookupAction');

            $app->get('/genres/', '\TinyMediaCenter\FrontEnd\MovieController:genresAction');

            $app->get('/{id}/', '\TinyMediaCenter\FrontEnd\MovieController:editAction');

            $app->post('/{dbid}/', '\TinyMediaCenter\FrontEnd\MovieController:updateMovieAction');
        }
    )
->add($checkAPI($api, $host));

$app->run();
