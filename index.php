<?php

set_time_limit(900);
require 'vendor/autoload.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use TinyMediaCenter\FrontEnd;
use TinyMediaCenter\FrontEnd\MovieController;
use TinyMediaCenter\FrontEnd\ShowController;
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

$container = $app->getContainer();
$host = FrontEnd\Util::getHost();
$container['host'] = $host;

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

// Override error handler
$container['errorHandler'] = function ($container) {
    return function ($request, ResponseInterface $response, \Exception $exception) use ($container) {
        return $container['view']->render(
            $response,
            'error/page.html.twig',
            [
                'host' => $container['host'],
                'header' => 'Error',
                'showEditButton' => false,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace(),
            ]
        );
    };
};

try {
    $config = FrontEnd\Util::getConfig();
    $api = new FrontEnd\RestApi($config["restUrl"]);
    $categories = [
        'shows' => $api->getSeriesCategories(),
        'movies' => $api->getMovieCategories(),
    ];
    $container['api'] = $api;
    $container['categories'] = $categories;

    //Redirect url ending in non-trailing slash to trailing equivalent
    $app->add(function (RequestInterface $request, ResponseInterface $response, callable $next) {
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
                    'host' => $host,
                    'title' => 'TV',
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
                $this->get('/shows/{showId}/episodes/{episodesId}/', '\TinyMediaCenter\FrontEnd\ShowController:getEpisodeDescriptionAction');
            }
        )
        ->add($checkAPI());

    // movies
    $app
        ->group(
            '/movies/{category}',
            function () {
                $this->get('/lookup/', MovieController::class.':lookupAction');
                $this->get('/genres/', MovieController::class.':genresAction');
                $this->get('/', MovieController::class.':movieAction');
                $this->get('/{id}/', MovieController::class.':detailsAction');
                $this->post('/{id}/', MovieController::class.':updateMovieAction');
            }
        )
            ->add($checkAPI());

    $app->run();
} catch (\Exception $e) {
    /** @var Response $response */
    $response = call_user_func_array($container['errorHandler'], [null, $container['response'], $e]);

    echo $response->getBody();
}
