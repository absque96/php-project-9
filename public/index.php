<?php

use App\DB;
use App\Url;
use App\UrlCheck;
use DI\Container;
use DiDom\Document;
use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\TwigMiddleware;
use Valitron\Validator;
use Illuminate\Support\Arr;

require __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$dotenv = Dotenv::createUnsafeImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

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

$container->set('urlCheck', function (ContainerInterface $container) {
    return new UrlCheck($container->get('DB'));
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
    $checks = $this->get('urlCheck')->getDistinct();

    $data = [
        'urls' => $urls,
        'checks' => Arr::keyBy($checks, 'url_id'),
    ];

    return $this->get('view')->render($response, 'urls/index.twig', $data);
})->setName('urls.index');

$app->get('/urls/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
    $url = $this->get('url')->findBy('id', $args['id']);
    $checks = $this->get('urlCheck')->findBy('url_id', $url['id'], true);

    $data = [
        'url' => $url,
        'checks' => $checks,
    ];

    return $this->get('view')->render($response, 'urls/show.twig', $data);
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

        return $this->get('view')->render($response->withStatus(422), 'index.twig', $data);
    }

    $parsedUrl = parse_url(mb_strtolower($urlName));
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

$app->post('/urls/{id:[0-9]+}/checks', function (Request $request, Response $response, array $args) use ($routeParser) {
    $url = $this->get('url')->findBy('id', $args['id']);

    $client = new Client();
    try {
        $response = $client->request('GET', $url['name']);
    } catch (ClientException $e) {
        $this->get('flash')->addMessage('error', 'При проверке произошла ошибка');
        return $response
            ->withHeader('Location', $routeParser->urlFor('urls.show', ['id' => $url['id']]))
            ->withStatus(302);
    }

    $statusCode = $response->getStatusCode();
    $content = $response->getBody()->getContents();
    $document = new Document($content);
    $h1 = optional($document->first('h1'))->text();
    $title = optional($document->first('title'))->text();
    $description = optional($document->first('meta[name=description]'))->getAttribute('content');

    $urlCheckData = [
        'url_id' => $url['id'],
        'status_code' => $statusCode,
        'h1' => $h1,
        'title' => $title,
        'description' => $description,
    ];

    $this->get('urlCheck')->addCheck($urlCheckData);
    $this->get('flash')->addMessage('success', 'Страница успешно проверена');

    return $response
        ->withHeader('Location', $routeParser->urlFor('urls.show', ['id' => $url['id']]))
        ->withStatus(302);
})->setName('checks.create');

$app->run();
