<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::require();

        $counts = [];
        foreach (['services' => 'Услуги', 'reviews' => 'Отзывы', 'posts' => 'Блог', 'works' => 'Работы', 'videos' => 'Видео', 'about' => 'О нас', 'users' => 'Пользователи'] as $table => $label) {
            try {
                $counts[$label] = (int) Database::pdo()->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            } catch (\Throwable) {
                $counts[$label] = null;
            }
        }

        $this->view('dashboard/index', [
            'counts' => $counts,
        ], 'Дашборд — ' . config('app.name'));
    }
}
