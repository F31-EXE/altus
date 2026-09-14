<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\About;

final class AboutController extends Controller
{
    public function __construct()
    {
        Auth::require();
    }

    public function index(): void
    {
        $page = (int) Request::get('page', 1);
        $data = About::paginateOrdered($page, 24);

        $this->view('about/index', [
            'items' => $data,
        ], 'О нас');
        clear_old();
    }

    public function create(): void
    {
        $this->view('about/form', [
            'item'   => null,
            'action' => admin_url('/about'),
        ], 'Новый блок «О нас»');
        clear_old();
    }

    public function store(): void
    {
        Csrf::check();

        $input = $this->collect();
        $v = $this->validator($input);

        $file = Request::file('image');
        if (!$file) {
            $v->addError('image', 'Загрузите фотографию');
        }

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/about/create');
        }

        try {
            $imagePath = (new Upload(config('uploads')))->store($file, 'about', 'image');
        } catch (\RuntimeException $e) {
            $this->withInput($input, ['image' => [$e->getMessage()]]);
            $this->redirectAdmin('/about/create');
        }

        About::create([
            'image_path'  => $imagePath,
            'title'       => $input['title'] !== '' ? $input['title'] : null,
            'description' => $input['description'] !== '' ? $input['description'] : null,
            'sort_order'  => (int) $input['sort_order'],
        ]);

        clear_old();
        Flash::success('Блок добавлен');
        $this->redirectAdmin('/about');
    }

    public function edit(string $id): void
    {
        $item = About::find((int) $id);
        if (!$item) {
            Flash::error('Блок не найден');
            $this->redirectAdmin('/about');
        }

        $this->view('about/form', [
            'item'   => $item,
            'action' => admin_url('/about/' . $item['id']),
        ], 'Редактирование блока «О нас»');
        clear_old();
    }

    public function update(string $id): void
    {
        Csrf::check();

        $item = About::find((int) $id);
        if (!$item) {
            Flash::error('Блок не найден');
            $this->redirectAdmin('/about');
        }

        $input = $this->collect();
        $v = $this->validator($input);

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/about/' . $item['id'] . '/edit');
        }

        $imagePath = $item['image_path'];
        $file = Request::file('image');
        if ($file) {
            try {
                $upload = new Upload(config('uploads'));
                $new = $upload->store($file, 'about', 'image');
                $upload->delete($item['image_path']);
                $imagePath = $new;
            } catch (\RuntimeException $e) {
                $this->withInput($input, ['image' => [$e->getMessage()]]);
                $this->redirectAdmin('/about/' . $item['id'] . '/edit');
            }
        }

        About::update((int) $item['id'], [
            'image_path'  => $imagePath,
            'title'       => $input['title'] !== '' ? $input['title'] : null,
            'description' => $input['description'] !== '' ? $input['description'] : null,
            'sort_order'  => (int) $input['sort_order'],
        ]);

        clear_old();
        Flash::success('Изменения сохранены');
        $this->redirectAdmin('/about');
    }

    public function destroy(string $id): void
    {
        Csrf::check();

        $item = About::find((int) $id);
        if ($item) {
            (new Upload(config('uploads')))->delete($item['image_path']);
            About::delete((int) $item['id']);
            Flash::success('Блок удалён');
        }
        $this->redirectAdmin('/about');
    }

    /** @return array<string,string> */
    private function collect(): array
    {
        return [
            'title'       => (string) Request::post('title', ''),
            'description' => (string) Request::post('description', ''),
            'sort_order'  => (string) Request::post('sort_order', '0'),
        ];
    }

    private function validator(array $input): Validator
    {
        $v = new Validator($input);
        $v->validate([
            'title'       => 'string|maxlen:200',
            'description' => 'string|maxlen:5000',
            'sort_order'  => 'numeric',
        ], [
            'title'       => 'Заголовок',
            'description' => 'Описание',
            'sort_order'  => 'Порядок',
        ]);
        return $v;
    }
}
