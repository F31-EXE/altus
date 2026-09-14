<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\HtmlSanitizer;
use App\Core\Request;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Post;

final class BlogController extends Controller
{
    public function __construct()
    {
        Auth::require();
    }

    public function index(): void
    {
        $page = (int) Request::get('page', 1);
        $data = Post::paginateLatest($page, 20);

        $this->view('blog/index', [
            'posts' => $data,
        ], 'Блог');
        clear_old();
    }

    public function create(): void
    {
        $this->view('blog/form', [
            'post'   => null,
            'action' => admin_url('/blog'),
        ], 'Новая новость');
        clear_old();
    }

    public function store(): void
    {
        Csrf::check();

        $input = $this->collect();
        $v = $this->validator($input);

        $file = Request::file('preview_image');
        if (!$file) {
            $v->addError('preview_image', 'Загрузите картинку превью');
        }

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/blog/create');
        }

        try {
            $previewPath = (new Upload(config('uploads')))->store($file, 'blog', 'image');
        } catch (\RuntimeException $e) {
            $this->withInput($input, ['preview_image' => [$e->getMessage()]]);
            $this->redirectAdmin('/blog/create');
        }

        $slugSource = $input['slug'] !== '' ? $input['slug'] : $input['title'];

        Post::create([
            'title'              => $input['title'],
            'slug'               => Post::uniqueSlug($slugSource),
            'excerpt'            => $input['excerpt'] !== '' ? $input['excerpt'] : null,
            'preview_image_path' => $previewPath,
            'body'               => $input['body'],
            'is_published'       => $input['is_published'],
        ]);

        clear_old();
        Flash::success('Новость создана');
        $this->redirectAdmin('/blog');
    }

    public function edit(string $id): void
    {
        $post = Post::find((int) $id);
        if (!$post) {
            Flash::error('Новость не найдена');
            $this->redirectAdmin('/blog');
        }

        $this->view('blog/form', [
            'post'   => $post,
            'action' => admin_url('/blog/' . $post['id']),
        ], 'Редактирование новости');
        clear_old();
    }

    public function update(string $id): void
    {
        Csrf::check();

        $post = Post::find((int) $id);
        if (!$post) {
            Flash::error('Новость не найдена');
            $this->redirectAdmin('/blog');
        }

        $input = $this->collect();
        $v = $this->validator($input);

        if (!$v->passes()) {
            $this->withInput($input, $v->errors());
            $this->redirectAdmin('/blog/' . $post['id'] . '/edit');
        }

        $previewPath = $post['preview_image_path'];
        $file = Request::file('preview_image');
        if ($file) {
            try {
                $upload = new Upload(config('uploads'));
                $new = $upload->store($file, 'blog', 'image');
                $upload->delete($post['preview_image_path']);
                $previewPath = $new;
            } catch (\RuntimeException $e) {
                $this->withInput($input, ['preview_image' => [$e->getMessage()]]);
                $this->redirectAdmin('/blog/' . $post['id'] . '/edit');
            }
        }

        $slugSource = $input['slug'] !== '' ? $input['slug'] : $input['title'];
        $oldBodyImages = $this->bodyImagePaths($post['body']);

        Post::update((int) $post['id'], [
            'title'              => $input['title'],
            'slug'               => Post::uniqueSlug($slugSource, (int) $post['id']),
            'excerpt'            => $input['excerpt'] !== '' ? $input['excerpt'] : null,
            'preview_image_path' => $previewPath,
            'body'               => $input['body'],
            'is_published'       => $input['is_published'],
        ]);

        // Картинки, которые убрали из тела при этом сохранении
        $removed = array_diff($oldBodyImages, $this->bodyImagePaths($input['body']));
        $this->purgeBodyImages($removed, (int) $post['id']);

        clear_old();
        Flash::success('Изменения сохранены');
        $this->redirectAdmin('/blog');
    }

    public function destroy(string $id): void
    {
        Csrf::check();

        $post = Post::find((int) $id);
        if ($post) {
            $bodyImages = $this->bodyImagePaths($post['body']);
            (new Upload(config('uploads')))->delete($post['preview_image_path']);
            Post::delete((int) $post['id']);
            $this->purgeBodyImages($bodyImages, (int) $post['id']);
            Flash::success('Новость удалена');
        }
        $this->redirectAdmin('/blog');
    }

    /**
     * Загрузка картинки из тела редактора (TinyMCE).
     * Ожидает multipart-поле `file` и заголовок X-CSRF-Token.
     * Отвечает JSON { "location": "<url>" }.
     */
    public function uploadImage(): void
    {
        if (!Csrf::verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            $this->jsonError('Недействительный CSRF-токен', 403);
        }

        $file = Request::file('file');
        if (!$file) {
            $this->jsonError('Файл не получен', 400);
        }

        try {
            $path = (new Upload(config('uploads')))->store($file, 'blog', 'image');
        } catch (\RuntimeException $e) {
            $this->jsonError($e->getMessage(), 422);
        }

        $this->json(['location' => asset($path)]);
    }

    /** @return array<string,string|bool> */
    private function collect(): array
    {
        $slugRaw = (string) Request::post('slug', '');

        return [
            'title'        => (string) Request::post('title', ''),
            'slug'         => $slugRaw !== '' ? slugify($slugRaw) : '',
            'excerpt'      => (string) Request::post('excerpt', ''),
            'body'         => HtmlSanitizer::clean((string) Request::raw('body', '')),
            'is_published' => Request::post('is_published') === '1' ? 1 : 0,
        ];
    }

    private function validator(array $input): Validator
    {
        $v = new Validator($input);
        $v->validate([
            'title'   => 'required|string|maxlen:200',
            'excerpt' => 'string|maxlen:500',
            'body'    => 'required|string|maxlen:200000',
        ], [
            'title'   => 'Заголовок',
            'excerpt' => 'Краткое описание',
            'body'    => 'Текст новости',
        ]);
        return $v;
    }

    /**
     * Достаёт из HTML относительные пути картинок, лежащих в uploads/blog/.
     * Внешние ссылки и картинки из других каталогов игнорируются.
     *
     * @return string[] уникальные пути вида "uploads/blog/xxx.png"
     */
    private function bodyImagePaths(string $html): array
    {
        if ($html === '' || !preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
            return [];
        }

        $base = rtrim((string) config('app.base_url'), '/');
        $found = [];

        foreach ($m[1] as $src) {
            $src = html_entity_decode($src, ENT_QUOTES | ENT_HTML5);
            if (str_starts_with($src, $base)) {
                $src = substr($src, strlen($base));
            }
            $path = ltrim((string) (parse_url($src, PHP_URL_PATH) ?: $src), '/');

            if (str_starts_with($path, 'uploads/blog/') && !str_contains($path, '..')) {
                $found[$path] = true;
            }
        }

        return array_keys($found);
    }

    /**
     * Удаляет файлы картинок, если они больше нигде не используются
     * (ни в теле другой новости, ни как чьё-то превью).
     *
     * @param iterable<string> $paths
     */
    private function purgeBodyImages(iterable $paths, int $exceptPostId): void
    {
        $upload = new Upload(config('uploads'));
        foreach ($paths as $path) {
            if (!Post::imageReferencedElsewhere($path, $exceptPostId)) {
                $upload->delete($path);
            }
        }
    }
}
