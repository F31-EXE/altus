<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Review;

final class ReviewController extends Controller
{
    public function __construct()
    {
        Auth::require();
    }

    public function index(): void
    {
        $page = (int) Request::get('page', 1);
        $data = Review::paginateOrdered($page, 20);

        $this->view('reviews/index', [
            'reviews' => $data,
        ], 'Отзывы');
        clear_old();
    }

    public function create(): void
    {
        $this->view('reviews/form', [
            'review' => null,
            'action' => admin_url('/reviews'),
        ], 'Новый отзыв');
        clear_old();
    }

    public function store(): void
    {
        Csrf::check();

        $input = $this->collect();
        $v = $this->validator($input);

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/reviews/create');
        }

        $documentPath = null;
        $file = Request::file('document');
        if ($file) {
            try {
                $documentPath = (new Upload(config('uploads')))->store($file, 'reviews', 'doc');
            } catch (\RuntimeException $e) {
                $this->withInput($input, ['document' => [$e->getMessage()]]);
                $this->redirectAdmin('/reviews/create');
            }
        }

        Review::create([
            'author'        => $input['author'],
            'body'          => $input['body'],
            'document_path' => $documentPath,
            'sort_order'    => (int) $input['sort_order'],
        ]);

        clear_old();
        Flash::success('Отзыв добавлен');
        $this->redirectAdmin('/reviews');
    }

    public function edit(string $id): void
    {
        $review = Review::find((int) $id);
        if (!$review) {
            Flash::error('Отзыв не найден');
            $this->redirectAdmin('/reviews');
        }

        $this->view('reviews/form', [
            'review' => $review,
            'action' => admin_url('/reviews/' . $review['id']),
        ], 'Редактирование отзыва');
        clear_old();
    }

    public function update(string $id): void
    {
        Csrf::check();

        $review = Review::find((int) $id);
        if (!$review) {
            Flash::error('Отзыв не найден');
            $this->redirectAdmin('/reviews');
        }

        $input = $this->collect();
        $v = $this->validator($input);

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/reviews/' . $review['id'] . '/edit');
        }

        $upload = new Upload(config('uploads'));
        $documentPath = $review['document_path'];

        $file = Request::file('document');
        if ($file) {
            try {
                $new = $upload->store($file, 'reviews', 'doc');
                $upload->delete($review['document_path']);
                $documentPath = $new;
            } catch (\RuntimeException $e) {
                $this->withInput($input, ['document' => [$e->getMessage()]]);
                $this->redirectAdmin('/reviews/' . $review['id'] . '/edit');
            }
        } elseif (Request::post('remove_document') === '1') {
            $upload->delete($review['document_path']);
            $documentPath = null;
        }

        Review::update((int) $review['id'], [
            'author'        => $input['author'],
            'body'          => $input['body'],
            'document_path' => $documentPath,
            'sort_order'    => (int) $input['sort_order'],
        ]);

        clear_old();
        Flash::success('Изменения сохранены');
        $this->redirectAdmin('/reviews');
    }

    public function destroy(string $id): void
    {
        Csrf::check();

        $review = Review::find((int) $id);
        if ($review) {
            (new Upload(config('uploads')))->delete($review['document_path']);
            Review::delete((int) $review['id']);
            Flash::success('Отзыв удалён');
        }
        $this->redirectAdmin('/reviews');
    }

    /** @return array<string,string> */
    private function collect(): array
    {
        return [
            'author'     => (string) Request::post('author', ''),
            'body'       => (string) Request::post('body', ''),
            'sort_order' => (string) Request::post('sort_order', '0'),
        ];
    }

    private function validator(array $input): Validator
    {
        $v = new Validator($input);
        $v->validate([
            'author'     => 'required|string|maxlen:200',
            'body'       => 'required|string|maxlen:5000',
            'sort_order' => 'numeric',
        ], [
            'author'     => 'ФИО / компания',
            'body'       => 'Текст отзыва',
            'sort_order' => 'Порядок',
        ]);
        return $v;
    }
}
