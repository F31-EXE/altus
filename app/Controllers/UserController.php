<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Validator;
use App\Models\User;

final class UserController extends Controller
{
    public function __construct()
    {
        Auth::require();
    }

    public function index(): void
    {
        $page = (int) Request::get('page', 1);
        $data = User::paginate($page, 20, 'id ASC');

        $this->view('users/index', [
            'users' => $data,
        ], 'Пользователи');
        clear_old();
    }

    public function create(): void
    {
        $this->view('users/form', [
            'user'   => null,
            'action' => admin_url('/users'),
        ], 'Новый пользователь');
        clear_old();
    }

    public function store(): void
    {
        Csrf::check();

        $input = [
            'name'     => (string) Request::post('name', ''),
            'email'    => (string) Request::post('email', ''),
            'password' => (string) Request::raw('password', ''),
        ];

        $v = new Validator($input);
        $v->validate([
            'name'     => 'required|string|maxlen:100',
            'email'    => 'required|email|maxlen:190',
            'password' => 'required|string|minlen:10|maxlen:200',
        ], ['name' => 'Имя', 'email' => 'Email', 'password' => 'Пароль']);

        if ($input['email'] !== '' && User::emailExists($input['email'])) {
            $v->addError('email', 'Пользователь с таким email уже существует');
        }

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/users/create');
        }

        User::create([
            'name'          => $input['name'],
            'email'         => $input['email'],
            'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
        ]);

        clear_old();
        Flash::success('Пользователь создан');
        $this->redirectAdmin('/users');
    }

    public function edit(string $id): void
    {
        $user = User::find((int) $id);
        if (!$user) {
            Flash::error('Пользователь не найден');
            $this->redirectAdmin('/users');
        }

        $this->view('users/form', [
            'user'   => $user,
            'action' => admin_url('/users/' . $user['id']),
        ], 'Редактирование пользователя');
        clear_old();
    }

    public function update(string $id): void
    {
        Csrf::check();

        $user = User::find((int) $id);
        if (!$user) {
            Flash::error('Пользователь не найден');
            $this->redirectAdmin('/users');
        }

        $input = [
            'name'     => (string) Request::post('name', ''),
            'email'    => (string) Request::post('email', ''),
            'password' => (string) Request::raw('password', ''),
        ];

        $rules = [
            'name'  => 'required|string|maxlen:100',
            'email' => 'required|email|maxlen:190',
        ];
        if ($input['password'] !== '') {
            $rules['password'] = 'string|minlen:10|maxlen:200';
        }

        $v = new Validator($input);
        $v->validate($rules, ['name' => 'Имя', 'email' => 'Email', 'password' => 'Пароль']);

        if ($input['email'] !== '' && User::emailExists($input['email'], (int) $user['id'])) {
            $v->addError('email', 'Этот email уже занят другим пользователем');
        }

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/users/' . $user['id'] . '/edit');
        }

        User::update((int) $user['id'], [
            'name'  => $input['name'],
            'email' => $input['email'],
        ]);

        if ($input['password'] !== '') {
            User::updatePassword((int) $user['id'], $input['password']);
        }

        clear_old();
        Flash::success('Изменения сохранены');
        $this->redirectAdmin('/users');
    }

    public function destroy(string $id): void
    {
        Csrf::check();

        $id = (int) $id;
        if ($id === Auth::id()) {
            Flash::error('Нельзя удалить самого себя');
            $this->redirectAdmin('/users');
        }

        if (User::paginate(1, 1)['total'] <= 1) {
            Flash::error('Нельзя удалить последнего пользователя');
            $this->redirectAdmin('/users');
        }

        User::delete($id);
        Flash::success('Пользователь удалён');
        $this->redirectAdmin('/users');
    }
}
