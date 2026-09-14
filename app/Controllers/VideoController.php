<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Video;

final class VideoController extends Controller
{
    public function __construct()
    {
        Auth::require();
    }

    public function index(): void
    {
        $page = (int) Request::get('page', 1);
        $data = Video::paginateOrdered($page, 24);

        $this->view('videos/index', [
            'videos' => $data,
        ], 'Видео');
        clear_old();
    }

    public function create(): void
    {
        $this->view('videos/form', [
            'video'  => null,
            'action' => admin_url('/videos'),
        ], 'Новое видео');
        clear_old();
    }

    public function store(): void
    {
        Csrf::check();

        $input = $this->collect();
        $v = $this->validator($input);

        $file = Request::file('video');
        if (!$file) {
            $v->addError('video', 'Загрузите видеофайл');
        }

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/videos/create');
        }

        try {
            $videoPath = (new Upload(config('uploads')))->store($file, 'videos', 'video');
        } catch (\RuntimeException $e) {
            $this->withInput($input, ['video' => [$e->getMessage()]]);
            $this->redirectAdmin('/videos/create');
        }

        Video::create([
            'video_path' => $videoPath,
            'title'      => $input['title'] !== '' ? $input['title'] : null,
            'subtitle'   => $input['subtitle'] !== '' ? $input['subtitle'] : null,
            'sort_order' => (int) $input['sort_order'],
        ]);

        clear_old();
        Flash::success('Видео добавлено');
        $this->redirectAdmin('/videos');
    }

    public function edit(string $id): void
    {
        $video = Video::find((int) $id);
        if (!$video) {
            Flash::error('Видео не найдено');
            $this->redirectAdmin('/videos');
        }

        $this->view('videos/form', [
            'video'  => $video,
            'action' => admin_url('/videos/' . $video['id']),
        ], 'Редактирование видео');
        clear_old();
    }

    public function update(string $id): void
    {
        Csrf::check();

        $video = Video::find((int) $id);
        if (!$video) {
            Flash::error('Видео не найдено');
            $this->redirectAdmin('/videos');
        }

        $input = $this->collect();
        $v = $this->validator($input);

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/videos/' . $video['id'] . '/edit');
        }

        $videoPath = $video['video_path'];
        $file = Request::file('video');
        if ($file) {
            try {
                $upload = new Upload(config('uploads'));
                $new = $upload->store($file, 'videos', 'video');
                $upload->delete($video['video_path']);
                $videoPath = $new;
            } catch (\RuntimeException $e) {
                $this->withInput($input, ['video' => [$e->getMessage()]]);
                $this->redirectAdmin('/videos/' . $video['id'] . '/edit');
            }
        }

        Video::update((int) $video['id'], [
            'video_path' => $videoPath,
            'title'      => $input['title'] !== '' ? $input['title'] : null,
            'subtitle'   => $input['subtitle'] !== '' ? $input['subtitle'] : null,
            'sort_order' => (int) $input['sort_order'],
        ]);

        clear_old();
        Flash::success('Изменения сохранены');
        $this->redirectAdmin('/videos');
    }

    public function destroy(string $id): void
    {
        Csrf::check();

        $video = Video::find((int) $id);
        if ($video) {
            (new Upload(config('uploads')))->delete($video['video_path']);
            Video::delete((int) $video['id']);
            Flash::success('Видео удалено');
        }
        $this->redirectAdmin('/videos');
    }

    /** @return array<string,string> */
    private function collect(): array
    {
        return [
            'title'      => (string) Request::post('title', ''),
            'subtitle'   => (string) Request::post('subtitle', ''),
            'sort_order' => (string) Request::post('sort_order', '0'),
        ];
    }

    private function validator(array $input): Validator
    {
        $v = new Validator($input);
        $v->validate([
            'title'      => 'string|maxlen:200',
            'subtitle'   => 'string|maxlen:255',
            'sort_order' => 'numeric',
        ], [
            'title'      => 'Заголовок',
            'subtitle'   => 'Подзаголовок',
            'sort_order' => 'Порядок',
        ]);
        return $v;
    }
}
