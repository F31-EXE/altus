<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Service;

final class ServiceController extends Controller
{
    public function __construct()
    {
        Auth::require();
    }

    public function index(): void
    {
        $page = (int) Request::get('page', 1);
        $data = Service::paginateOrdered($page, 20);

        $this->view('services/index', [
            'services' => $data,
        ], 'Наши услуги');
        clear_old();
    }

    public function create(): void
    {
        $this->view('services/form', [
            'service' => null,
            'action'  => admin_url('/services'),
        ], 'Новая услуга');
        clear_old();
    }

    public function store(): void
    {
        Csrf::check();

        $input = $this->collect();
        $v = $this->validator($input);

        // Фото при создании обязательно
        $file = Request::file('image');
        if (!$file) {
            $v->addError('image', 'Загрузите фото услуги');
        }

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/services/create');
        }

        try {
            $imagePath = (new Upload(config('uploads')))->store($file, 'services', 'image');
        } catch (\RuntimeException $e) {
            $this->withInput($input, ['image' => [$e->getMessage()]]);
            $this->redirectAdmin('/services/create');
        }

        Service::create([
            'title'       => $input['title'],
            'price'       => (float) $input['price'],
            'description' => $input['description'],
            'image_path'  => $imagePath,
            'sort_order'  => (int) $input['sort_order'],
        ]);

        clear_old();
        Flash::success('Услуга добавлена');
        $this->redirectAdmin('/services');
    }

    public function edit(string $id): void
    {
        $service = Service::find((int) $id);
        if (!$service) {
            Flash::error('Услуга не найдена');
            $this->redirectAdmin('/services');
        }

        $this->view('services/form', [
            'service' => $service,
            'action'  => admin_url('/services/' . $service['id']),
        ], 'Редактирование услуги');
        clear_old();
    }

    public function update(string $id): void
    {
        Csrf::check();

        $service = Service::find((int) $id);
        if (!$service) {
            Flash::error('Услуга не найдена');
            $this->redirectAdmin('/services');
        }

        $input = $this->collect();
        $v = $this->validator($input);

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/services/' . $service['id'] . '/edit');
        }

        $imagePath = $service['image_path'];
        $file = Request::file('image');
        if ($file) {
            try {
                $upload = new Upload(config('uploads'));
                $new = $upload->store($file, 'services', 'image');
                $upload->delete($service['image_path']); // удаляем старое только после успешной загрузки
                $imagePath = $new;
            } catch (\RuntimeException $e) {
                $this->withInput($input, ['image' => [$e->getMessage()]]);
                $this->redirectAdmin('/services/' . $service['id'] . '/edit');
            }
        }

        Service::update((int) $service['id'], [
            'title'       => $input['title'],
            'price'       => (float) $input['price'],
            'description' => $input['description'],
            'image_path'  => $imagePath,
            'sort_order'  => (int) $input['sort_order'],
        ]);

        clear_old();
        Flash::success('Изменения сохранены');
        $this->redirectAdmin('/services');
    }

    public function destroy(string $id): void
    {
        Csrf::check();

        $service = Service::find((int) $id);
        if ($service) {
            (new Upload(config('uploads')))->delete($service['image_path']);
            Service::delete((int) $service['id']);
            Flash::success('Услуга удалена');
        }
        $this->redirectAdmin('/services');
    }

    /** @return array<string,string> */
    private function collect(): array
    {
        return [
            'title'       => (string) Request::post('title', ''),
            'price'       => (string) Request::post('price', ''),
            'description' => (string) Request::post('description', ''),
            'sort_order'  => (string) Request::post('sort_order', '0'),
        ];
    }

    private function validator(array $input): Validator
    {
        $v = new Validator($input);
        $v->validate([
            'title'       => 'required|string|maxlen:200',
            'price'       => 'required|numeric|min:0|max:99999999',
            'description' => 'required|string|maxlen:5000',
            'sort_order'  => 'numeric',
        ], [
            'title'       => 'Заголовок',
            'price'       => 'Цена',
            'description' => 'Описание',
            'sort_order'  => 'Порядок',
        ]);
        return $v;
    }
}
