<?php

use App\DB;
use App\Url;
use DI\Container;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\TwigMiddleware;
use Valitron\Validator;

require __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$dotenv = Dotenv::createUnsafeImmutable(__DIR__ . '/../');
$dotenv->load();

$container = new Container();
AppFactory::setContainer($container);

$container->set('view', function (ContainerInterface $container) {
    $twig = Twig::create(__DIR__ . '/../templates');
    $twig->getEnvironment()->addGlobal('flash', $container->get('flash'));
    return $twig;
});

$container->set('DB', function () {
    return new DB();
});

$container->set('url', function (ContainerInterface $container) {
    return new Url($container->get('DB'));
});

$container->set('flash', function () {
    return new Messages();
});

$app = AppFactory::create();

$app->add(TwigMiddleware::createFromContainer($app));

$routeParser = $app->getRouteCollector()->getRouteParser();

$app->get('/', function (Request $request, Response $response) {
    return $this->get('view')->render($response, 'index.twig');
})->setName('index');

$app->get('/urls', function (Request $request, Response $response) {
    $urls = $this->get('url')->getAll();
    return $this->get('view')->render($response, 'urls/index.twig', ['urls' => $urls]);
})->setName('urls.index');

$app->get('/urls/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
    $url = $this->get('url')->findBy('id', $args['id']);
    return $this->get('view')->render($response, 'urls/show.twig', ['url' => $url]);
})->setName('urls.show');

$app->post('/urls', function (Request $request, Response $response) use ($routeParser) {
    $parsedBody = $request->getParsedBody();
    $urlName = $parsedBody['url']['name'];

    $validator = new Validator($parsedBody);
    $validator->rule('required', 'url.name')->message('URL не должен быть пустым');
    $validator->rule('url', 'url.name')->message('Некорректный URL');

    if (!$validator->validate()) {
        $data = [
            'urlName' => $urlName,
            'errors' => $validator->errors(),
        ];

        return $this->get('view')->render($response, 'index.twig', $data);
    }

    $parsedUrl = parse_url($urlName);
    $parsedUrlName = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

    $url = $this->get('url')->findBy('name', $parsedUrlName);

    if ($url) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
    } else {
        $url = $this->get('url')->addUrl($parsedUrlName);
        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
    }

    $id = $url['id'];

    return $response
        ->withHeader('Location', $routeParser->urlFor('urls.show', ['id' => $id]))
        ->withStatus(302);
})->setName('urls.create');

$app->run();
