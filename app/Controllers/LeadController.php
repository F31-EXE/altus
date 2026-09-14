<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Validator;
use App\Models\Lead;

/**
 * Приём заявок с контактной формы сайта.
 *
 * Заявка и уходит письмом, и ложится в базу. Только письмо было бы ненадёжно:
 * почта с виртуального хостинга до Яндекса доходит не всегда (SPF, DKIM,
 * спам-фильтр), а потерянная заявка это потерянный клиент. Запись в базу
 * остаётся в любом случае, письмо идёт сверх неё. Если письмо не ушло,
 * заявка помечается и это видно в админке.
 *
 * Форма шлёт JSON, поэтому обычный csrf_field() из формы сюда не подходит:
 * токен приходит заголовком X-CSRF-Token, как это уже сделано для загрузки
 * картинок в редакторе блога.
 */
final class LeadController extends Controller
{
    /** Не больше стольких заявок с одного адреса за окно ниже. */
    private const MAX_PER_WINDOW = 5;
    private const WINDOW_MINUTES = 30;

    public function store(): void
    {
        if (!Csrf::verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            $this->jsonError('Страница устарела. Обновите её и отправьте ещё раз.', 419);
        }

        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload)) {
            $this->jsonError('Не удалось разобрать данные формы.', 400);
        }

        /* Ловушка для роботов: поле спрятано от человека, но заполняется
           автоматическим заполнителем. Заполнено - молча отвечаем успехом,
           чтобы робот не подбирал обход, но ничего не сохраняем. */
        if (trim((string) ($payload['website'] ?? '')) !== '') {
            $this->json(['ok' => true]);
        }

        $input = [
            'name'    => trim((string) ($payload['name'] ?? '')),
            'phone'   => trim((string) ($payload['phone'] ?? '')),
            'contact' => trim((string) ($payload['contact'] ?? '')),
            'message' => trim((string) ($payload['message'] ?? '')),
            'consent' => !empty($payload['consent']),
        ];

        $v = new Validator($input);
        $v->validate([
            'name'    => 'required|string|minlen:2|maxlen:200',
            'phone'   => 'required|string|minlen:6|maxlen:50',
            'contact' => 'string|maxlen:200',
            'message' => 'required|string|minlen:5|maxlen:5000',
        ], [
            'name'    => 'Имя',
            'phone'   => 'Телефон',
            'contact' => 'Мессенджер или почта',
            'message' => 'Что нужно сделать',
        ]);

        /* Согласие на обработку данных обязательно: без него мы не вправе
           сохранять заявку. Проверяем на сервере, а не только галочкой. */
        if (!$input['consent']) {
            $v->addError('consent', 'Нужно согласие на обработку персональных данных');
        }

        if (!$v->passes()) {
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

        if ($ip !== '' && Lead::recentFromIp($ip, self::WINDOW_MINUTES) >= self::MAX_PER_WINDOW) {
            $this->jsonError('Слишком много заявок подряд. Позвоните нам или попробуйте позже.', 429);
        }

        $id = Lead::create([
            'name'    => $input['name'],
            'phone'   => $input['phone'],
            'contact' => $input['contact'] !== '' ? $input['contact'] : null,
            'message' => $input['message'],
            'ip'      => $ip !== '' ? $ip : null,
            'mailed'  => 0,
        ]);

        if ($this->sendMail($id, $input)) {
            Lead::update($id, ['mailed' => 1]);
        }

        $this->json(['ok' => true]);
    }

    /**
     * Письмо с заявкой на почту компании.
     *
     * Отправитель ставится на своём домене, а не на почте получателя: письмо
     * с чужим адресом в From почти гарантированно уходит в спам. Адрес
     * заявителя идёт в Reply-To, поэтому «Ответить» в почтовом клиенте
     * отвечает клиенту, а не самому себе.
     */
    private function sendMail(int $id, array $input): bool
    {
        $to = (string) config('mail.to');
        if ($to === '') {
            return false;
        }

        $host = parse_url((string) config('app.base_url'), PHP_URL_HOST) ?: 'localhost';
        $from = (string) config('mail.from', 'noreply@' . $host);

        $subject = 'Заявка с сайта № ' . $id . ': ' . $input['name'];

        $lines = [
            'Заявка № ' . $id . ' с сайта ' . config('app.base_url'),
            '',
            'Имя:      ' . $input['name'],
            'Телефон:  ' . $input['phone'],
            'Связь:    ' . ($input['contact'] !== '' ? $input['contact'] : 'не указана'),
            '',
            'Что нужно сделать:',
            $input['message'],
            '',
            '--',
            'Отправлено формой на сайте. Заявка также сохранена в панели управления.',
        ];

        $headers = [
            'From: Сайт Альтус <' . $from . '>',
            'Content-Type: text/plain; charset=UTF-8',
            'MIME-Version: 1.0',
        ];

        /* Отвечать надо клиенту. Кладём в Reply-To только настоящий адрес
           почты: телефон или ник из мессенджера в этом заголовке бессмысленны
           и делают письмо подозрительным для фильтра. */
        if (filter_var($input['contact'], FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $input['contact'];
        }

        $ok = @mail(
            $to,
            '=?UTF-8?B?' . base64_encode($subject) . '?=',
            implode("\r\n", $lines),
            implode("\r\n", $headers)
        );

        if (!$ok) {
            error_log('Заявка ' . $id . ': письмо на ' . $to . ' не отправлено');
        }

        return (bool) $ok;
    }
}
