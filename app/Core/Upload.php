<?php

namespace App\Core;

/**
 * Загрузка файлов в public/uploads/<подкаталог>.
 */
final class Upload
{
    public function __construct(private array $cfg) {}

    /** Наборы разрешённых типов по «виду» загрузки. */
    private const KINDS = [
        'image' => [
            'ext_key'   => 'image_ext',
            'size_key'  => 'max_size',
            'mimes'     => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'mismatch'  => 'Содержимое файла не соответствует изображению',
        ],
        'doc' => [
            'ext_key'   => 'doc_ext',
            'size_key'  => 'max_size',
            'mimes'     => ['image/jpeg', 'image/png', 'application/pdf'],
            'mismatch'  => 'Содержимое файла не соответствует PDF или изображению',
        ],
        'video' => [
            'ext_key'   => 'video_ext',
            'size_key'  => 'video_max_size',
            'mimes'     => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-m4v'],
            'mismatch'  => 'Содержимое файла не соответствует видео',
        ],
    ];

    /**
     * Сохраняет один файл. Возвращает относительный путь вида "uploads/services/xxx.jpg"
     * либо кидает \RuntimeException с человекочитаемым текстом.
     *
     * @param array  $file     элемент из $_FILES
     * @param string $subdir   services|reviews|blog|works|videos
     * @param string $kind     image|doc|video — какой набор типов разрешён
     */
    public function store(array $file, string $subdir, string $kind = 'image'): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->errorText($file['error'] ?? UPLOAD_ERR_NO_FILE));
        }

        $spec = self::KINDS[$kind] ?? self::KINDS['image'];
        $maxSize = $this->cfg[$spec['size_key']] ?? $this->cfg['max_size'];

        if ($file['size'] > $maxSize) {
            $mb = round($maxSize / 1024 / 1024, 1);
            throw new \RuntimeException("Файл больше {$mb} МБ");
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = $this->cfg[$spec['ext_key']];
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('Недопустимый тип файла. Разрешены: ' . implode(', ', $allowed));
        }

        // Проверка реального содержимого
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $spec['mimes'], true)) {
            throw new \RuntimeException($spec['mismatch']);
        }

        $dir = rtrim($this->cfg['dir'], '/\\') . DIRECTORY_SEPARATOR . $subdir;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Не удалось создать каталог для загрузки');
        }

        $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        $target = $dir . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException('Не удалось сохранить файл');
        }

        return 'uploads/' . $subdir . '/' . $name;
    }

    /** Удаляет ранее сохранённый файл по относительному пути. */
    public function delete(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }
        $base = dirname(rtrim($this->cfg['dir'], '/\\')); // .../public
        $full = $base . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function errorText(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файл слишком большой',
            UPLOAD_ERR_PARTIAL    => 'Файл загружен частично',
            UPLOAD_ERR_NO_FILE    => 'Файл не выбран',
            UPLOAD_ERR_NO_TMP_DIR => 'Нет временного каталога на сервере',
            UPLOAD_ERR_CANT_WRITE => 'Ошибка записи на диск',
            default               => 'Ошибка загрузки файла',
        };
    }
}
