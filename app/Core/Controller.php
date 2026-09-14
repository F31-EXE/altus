<?php

namespace App\Core;

abstract class Controller
{
    /** Рендер view внутри общего лейаута. */
    protected function view(string $template, array $data = [], ?string $title = null): void
    {
        $data['title'] = $title ?? config('app.name');
        $data['__template'] = APP_PATH . '/Views/' . $template . '.php';

        extract($data, EXTR_SKIP);
        require APP_PATH . '/Views/layout.php';
    }

    /** Рендер view без лейаута (страница логина и т.п.). */
    protected function viewBare(string $template, array $data = [], ?string $title = null): void
    {
        $data['title'] = $title ?? config('app.name');
        extract($data, EXTR_SKIP);
        require APP_PATH . '/Views/' . $template . '.php';
    }

    /** Рендер view внутри публичного (клиентского) лейаута. */
    protected function viewPublic(string $template, array $data = [], ?string $title = null): void
    {
        // На публичных страницах может выводиться HTML из блога — жёсткий CSP как второй рубеж.
        header(
            "Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
            . "media-src 'self'; style-src 'self'; script-src 'self'; "
            . "object-src 'none'; base-uri 'self'; frame-ancestors 'none'"
        );

        $data['title'] = $title ?? config('app.name');
        $data['__template'] = APP_PATH . '/Views/' . $template . '.php';

        extract($data, EXTR_SKIP);
        require APP_PATH . '/Views/site/layout.php';
    }

    /**
     * Рендер страницы публичного сайта.
     *
     * CSP собственный, а не тот, что у демо-витрины: на публичных страницах
     * выводится HTML из блога, и заодно есть карта Яндекса, которую приходится
     * разрешать отдельно. Всё остальное - со своего домена.
     */
    protected function viewFront(string $template, array $data = [], ?string $title = null): void
    {
        header(
            "Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
            . "media-src 'self'; style-src 'self'; script-src 'self'; font-src 'self'; "
            . "frame-src https://yandex.ru https://*.yandex.ru; "
            . "object-src 'none'; base-uri 'self'; frame-ancestors 'none'"
        );

        $data['title'] = $title ?? 'Альтус';
        $data['__template'] = APP_PATH . '/Views/' . $template . '.php';

        extract($data, EXTR_SKIP);
        require APP_PATH . '/Views/front/layout.php';
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . base_url($path));
        exit;
    }

    /** Редирект внутри админки: путь задаётся без префикса, '/services'. */
    protected function redirectAdmin(string $path): never
    {
        header('Location: ' . admin_url($path));
        exit;
    }

    /** Сохранить старый ввод и ошибки во flash-подобное хранилище для повторного показа формы. */
    protected function withInput(array $input, array $errors = []): void
    {
        $_SESSION['_old'] = $input;
        $_SESSION['_errors'] = $errors;
    }

    protected function jsonError(string $message, int $code = 400): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function json(array $payload, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
