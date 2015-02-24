<?php

use App\Controllers\ImagesController;
use App\Repositories\ImageRepository;
use App\Services\ImageHelper;

require_once __DIR__ . "/../vendor/autoload.php";

$app = new Silex\Application();
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app['debug'] = true;//delete this in prod

//configuration
$app['config.imagesWebDir'] = "/img/";
$app['config.imagesUploadDir'] = __DIR__ . $app['config.imagesWebDir'];
$app['config.allowedMimeTypes'] = [
    'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png',
];
$app['config.allowedExtensions'] = [
    'jpg', 'jpeg', 'png',
];

//services
$app['db'] = $app->share(function () {
    $mongo = new MongoClient();
    return $mongo->selectDB('funny');
});
$app['images.helper'] = $app->share(function () use ($app) {
    return new ImageHelper($app);
});
$app['images.repository'] = $app->share(function () use ($app) {
    return new ImageRepository($app['db']);
});
$app['images.controller'] = $app->share(function () use ($app) {
    return new ImagesController($app['images.repository'], $app['config.imagesWebDir']);
});

//routes
$app->get('/images', 'images.controller:listAction');
$app->get('/images/{id}', 'images.controller:retrieveAction');
$app->post('/images', 'images.controller:createAction');
$app->post('/images/{id}/likes', 'images.controller:likeAction');
$app->delete('/images/{id}', 'images.controller:removeAction');

$app->run();