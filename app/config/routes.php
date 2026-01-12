<?php

use app\controllers\ApiExampleController;
use app\controllers\ChatController;
use flight\Engine;
use flight\net\Router;

/** 
 * @var Router $router 
 * @var Engine $app
 */
$router->get('/', function() use ($app) {
	$app->redirect('/chat');
});

$router->get('/hello-world/@name', function($name) {
	echo '<h1>Hello world! Oh hey '.$name.'!</h1>';
});

$router->group('/api', function() use ($router, $app) {
	$Api_Example_Controller = new ApiExampleController($app);
	$router->get('/users', [ $Api_Example_Controller, 'getUsers' ]);
	$router->get('/users/@id:[0-9]', [ $Api_Example_Controller, 'getUser' ]);
	$router->post('/users/@id:[0-9]', [ $Api_Example_Controller, 'updateUser' ]);
});

// Chat routes
$router->group('/chat', function() use ($router, $app) {
	$Chat_Controller = new ChatController($app);
	$router->get('', [ $Chat_Controller, 'showForm' ]);
	$router->get('/interface', [ $Chat_Controller, 'showChat' ]);
	$router->post('/message', [ $Chat_Controller, 'handleMessage' ]);
});

// Serve static JSON data files
$router->get('/resources/data/@file', function($file) {
	$filePath = __DIR__ . '/../../resources/data/' . $file;
	
	if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'json') {
		header('Content-Type: application/json; charset=utf-8');
		readfile($filePath);
	} else {
		http_response_code(404);
		echo json_encode(['error' => 'File not found']);
	}
});