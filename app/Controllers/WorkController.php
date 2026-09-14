<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Work;

final class WorkController extends Controller
{
    public function __construct()
    {
        Auth::require();
    }

    public function index(): void
    {
        $page = (int) Request::get('page', 1);
        $data = Work::paginateOrdered($page, 24);

        $this->view('works/index', [
            'works' => $data,
        ], 'Наши работы');
        clear_old();
    }

    public function create(): void
    {
        $this->view('works/form', [
            'work'   => null,
            'action' => admin_url('/works'),
        ], 'Новая работа');
        clear_old();
    }

    public function store(): void
    {
        Csrf::check();

        $input = $this->collect();
        $v = $this->validator($input);

        $file = Request::file('image');
        if (!$file) {
            $v->addError('image', 'Загрузите фотографию работы');
        }

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/works/create');
        }

        try {
            $imagePath = (new Upload(config('uploads')))->store($file, 'works', 'image');
        } catch (\RuntimeException $e) {
            $this->withInput($input, ['image' => [$e->getMessage()]]);
            $this->redirectAdmin('/works/create');
        }

        Work::create([
            'image_path'  => $imagePath,
            'description' => $input['description'],
            'sort_order'  => (int) $input['sort_order'],
        ]);

        clear_old();
        Flash::success('Работа добавлена');
        $this->redirectAdmin('/works');
    }

    public function edit(string $id): void
    {
        $work = Work::find((int) $id);
        if (!$work) {
            Flash::error('Работа не найдена');
            $this->redirectAdmin('/works');
        }

        $this->view('works/form', [
            'work'   => $work,
            'action' => admin_url('/works/' . $work['id']),
        ], 'Редактирование работы');
        clear_old();
    }

    public function update(string $id): void
    {
        Csrf::check();

        $work = Work::find((int) $id);
        if (!$work) {
            Flash::error('Работа не найдена');
            $this->redirectAdmin('/works');
        }

        $input = $this->collect();
        $v = $this->validator($input);

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/works/' . $work['id'] . '/edit');
        }

        $imagePath = $work['image_path'];
        $file = Request::file('image');
        if ($file) {
            try {
                $upload = new Upload(config('uploads'));
                $new = $upload->store($file, 'works', 'image');
                $upload->delete($work['image_path']);
                $imagePath = $new;
            } catch (\RuntimeException $e) {
                $this->withInput($input, ['image' => [$e->getMessage()]]);
                $this->redirectAdmin('/works/' . $work['id'] . '/edit');
            }
        }

        Work::update((int) $work['id'], [
            'image_path'  => $imagePath,
            'description' => $input['description'],
            'sort_order'  => (int) $input['sort_order'],
        ]);

        clear_old();
        Flash::success('Изменения сохранены');
        $this->redirectAdmin('/works');
    }

    public function destroy(string $id): void
    {
        Csrf::check();

        $work = Work::find((int) $id);
        if ($work) {
            (new Upload(config('uploads')))->delete($work['image_path']);
            Work::delete((int) $work['id']);
            Flash::success('Работа удалена');
        }
        $this->redirectAdmin('/works');
    }

    /** @return array<string,string> */
    private function collect(): array
    {
        return [
            'description' => (string) Request::post('description', ''),
            'sort_order'  => (string) Request::post('sort_order', '0'),
        ];
    }

    private function validator(array $input): Validator
    {
        $v = new Validator($input);
        $v->validate([
            'description' => 'required|string|maxlen:2000',
            'sort_order'  => 'numeric',
        ], [
            'description' => 'Описание',
            'sort_order'  => 'Порядок',
        ]);
        return $v;
    }
}
