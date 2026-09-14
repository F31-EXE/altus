<?php

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;
use App\Controllers\ServiceController;
use App\Controllers\ReviewController;
use App\Controllers\BlogController;
use App\Controllers\WorkController;
use App\Controllers\VideoController;
use App\Controllers\AboutController;
use App\Controllers\SiteController;
use App\Controllers\PublicController;

/** @var Router $router */

// --- Публичный сайт. Занимает корень, поэтому объявлен первым ---
$router->get('/',             [PublicController::class, 'home']);
$router->get('/blog',         [PublicController::class, 'blogIndex']);
$router->get('/blog/{slug}',  [PublicController::class, 'blogShow']);

// --- Демо-витрина прошлого разработчика. Оставлена как справочник ---
$router->get('/site',             [SiteController::class, 'home']);
$router->get('/site/services',    [SiteController::class, 'services']);
$router->get('/site/works',       [SiteController::class, 'works']);
$router->get('/site/reviews',     [SiteController::class, 'reviews']);
$router->get('/site/blog',        [SiteController::class, 'blogIndex']);
$router->get('/site/blog/{slug}', [SiteController::class, 'blogShow']);

// --- Аутентификация ---
$router->get(admin_path('/login'),  [AuthController::class, 'showLogin']);
$router->post(admin_path('/login'), [AuthController::class, 'login']);
$router->post(admin_path('/logout'), [AuthController::class, 'logout']);

// --- Дашборд ---
$router->get(admin_path('/'), [DashboardController::class, 'index']);

// --- Пользователи ---
$router->get(admin_path('/users'),            [UserController::class, 'index']);
$router->get(admin_path('/users/create'),     [UserController::class, 'create']);
$router->post(admin_path('/users'),           [UserController::class, 'store']);
$router->get(admin_path('/users/{id}/edit'),  [UserController::class, 'edit']);
$router->post(admin_path('/users/{id}'),      [UserController::class, 'update']);
$router->post(admin_path('/users/{id}/delete'), [UserController::class, 'destroy']);

// --- Наши услуги ---
$router->get(admin_path('/services'),             [ServiceController::class, 'index']);
$router->get(admin_path('/services/create'),      [ServiceController::class, 'create']);
$router->post(admin_path('/services'),            [ServiceController::class, 'store']);
$router->get(admin_path('/services/{id}/edit'),   [ServiceController::class, 'edit']);
$router->post(admin_path('/services/{id}'),       [ServiceController::class, 'update']);
$router->post(admin_path('/services/{id}/delete'), [ServiceController::class, 'destroy']);

// --- Отзывы ---
$router->get(admin_path('/reviews'),             [ReviewController::class, 'index']);
$router->get(admin_path('/reviews/create'),      [ReviewController::class, 'create']);
$router->post(admin_path('/reviews'),            [ReviewController::class, 'store']);
$router->get(admin_path('/reviews/{id}/edit'),   [ReviewController::class, 'edit']);
$router->post(admin_path('/reviews/{id}'),       [ReviewController::class, 'update']);
$router->post(admin_path('/reviews/{id}/delete'), [ReviewController::class, 'destroy']);

// --- Блог ---
$router->get(admin_path('/blog'),                [BlogController::class, 'index']);
$router->get(admin_path('/blog/create'),         [BlogController::class, 'create']);
$router->post(admin_path('/blog/upload-image'),  [BlogController::class, 'uploadImage']); // до /blog/{id}
$router->post(admin_path('/blog'),               [BlogController::class, 'store']);
$router->get(admin_path('/blog/{id}/edit'),      [BlogController::class, 'edit']);
$router->post(admin_path('/blog/{id}'),          [BlogController::class, 'update']);
$router->post(admin_path('/blog/{id}/delete'),   [BlogController::class, 'destroy']);

// --- Наши работы ---
$router->get(admin_path('/works'),              [WorkController::class, 'index']);
$router->get(admin_path('/works/create'),       [WorkController::class, 'create']);
$router->post(admin_path('/works'),             [WorkController::class, 'store']);
$router->get(admin_path('/works/{id}/edit'),    [WorkController::class, 'edit']);
$router->post(admin_path('/works/{id}'),        [WorkController::class, 'update']);
$router->post(admin_path('/works/{id}/delete'), [WorkController::class, 'destroy']);

// --- Видео ---
$router->get(admin_path('/videos'),              [VideoController::class, 'index']);
$router->get(admin_path('/videos/create'),       [VideoController::class, 'create']);
$router->post(admin_path('/videos'),             [VideoController::class, 'store']);
$router->get(admin_path('/videos/{id}/edit'),    [VideoController::class, 'edit']);
$router->post(admin_path('/videos/{id}'),        [VideoController::class, 'update']);
$router->post(admin_path('/videos/{id}/delete'), [VideoController::class, 'destroy']);

// --- О нас ---
$router->get(admin_path('/about'),              [AboutController::class, 'index']);
$router->get(admin_path('/about/create'),       [AboutController::class, 'create']);
$router->post(admin_path('/about'),             [AboutController::class, 'store']);
$router->get(admin_path('/about/{id}/edit'),    [AboutController::class, 'edit']);
$router->post(admin_path('/about/{id}'),        [AboutController::class, 'update']);
$router->post(admin_path('/about/{id}/delete'), [AboutController::class, 'destroy']);
