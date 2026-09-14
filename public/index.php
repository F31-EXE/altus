<?php

use App\Core\Router;
use App\Core\Request;

require dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();
require APP_PATH . '/routes.php';

$router->dispatch(Request::method(), Request::uri());
