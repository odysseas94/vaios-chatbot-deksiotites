<?php

use app\controllers\ApiExampleController;
use app\controllers\ChatController;
use app\controllers\FileUploadController;
use app\middlewares\AdminAuthMiddleware;
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
	$router->post('/clear', [ $Chat_Controller, 'clearHistory' ]);
});

// File upload routes - Protected with admin authentication
$router->group('/files', function() use ($router, $app) {
	$File_Upload_Controller = new FileUploadController($app);
	
	$authMiddleware = new AdminAuthMiddleware($app);
	
	$router->get('', function() use ($authMiddleware, $File_Upload_Controller) {
		if ($authMiddleware->before()) {
			$File_Upload_Controller->showUploadPage();
		}
	});
	
	$router->post('/upload', function() use ($authMiddleware, $File_Upload_Controller) {
		if ($authMiddleware->before()) {
			$File_Upload_Controller->uploadFiles();
		}
	});
	
	$router->get('/status', function() use ($authMiddleware, $File_Upload_Controller) {
		if ($authMiddleware->before()) {
			$File_Upload_Controller->getUploadedFiles();
		}
	});
	
	$router->post('/delete', function() use ($authMiddleware, $File_Upload_Controller) {
		if ($authMiddleware->before()) {
			$File_Upload_Controller->deleteUploadedFiles();
		}
	});
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