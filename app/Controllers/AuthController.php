<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\LoginThrottle;
use App\Core\Request;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirectAdmin('/');
        }
        $this->viewBare('auth/login', [
            'flashes' => Flash::pull(),
        ], 'Вход — ' . config('app.name'));
        clear_old();
    }

    public function login(): void
    {
        Csrf::check();

        $lockLeft = LoginThrottle::lockedForSeconds();
        if ($lockLeft > 0) {
            $min = (int) ceil($lockLeft / 60);
            Flash::error("Слишком много неудачных попыток. Попробуйте через {$min} мин.");
            $this->redirectAdmin('/login');
        }

        $email = (string) Request::post('email', '');
        $password = (string) Request::post('password', '');

        if ($email === '' || $password === '') {
            Flash::error('Введите email и пароль');
            $this->withInput(['email' => $email]);
            $this->redirectAdmin('/login');
        }

        if (!Auth::attempt($email, $password)) {
            LoginThrottle::registerFailure();
            usleep(250_000); // лёгкая задержка против скриптового перебора
            Flash::error('Неверный email или пароль');
            $this->withInput(['email' => $email]);
            $this->redirectAdmin('/login');
        }

        LoginThrottle::clear();
        clear_old();
        Flash::success('Добро пожаловать!');
        $this->redirectAdmin('/');
    }

    public function logout(): void
    {
        Csrf::check();
        Auth::logout();
        Flash::success('Вы вышли из системы');
        $this->redirectAdmin('/login');
    }
}
