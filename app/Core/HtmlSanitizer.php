<?php

namespace App\Core;

/**
 * Санитайзер HTML по белому списку (без внешних зависимостей, на DOMDocument).
 * Применяется к тексту блога из редактора перед сохранением в БД.
 *
 * Убирает: <script>/<style>/<iframe>/<object>/<form> и прочие опасные теги,
 * все обработчики on*, javascript:/data:-ссылки, атрибуты style/id/class.
 */
final class HtmlSanitizer
{
    /** Тег => список разрешённых атрибутов. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [], 'span' => [], 'div' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'sub' => [], 'sup' => [], 'small' => [], 'mark' => [], 'code' => [], 'pre' => [],
        'blockquote' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'figure' => [], 'figcaption' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [],
        'tr' => [], 'td' => ['colspan', 'rowspan'], 'th' => ['colspan', 'rowspan'], 'caption' => [],
    ];

    /** Теги, которые вырезаются вместе с содержимым. */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'noscript', 'template', 'link', 'meta', 'base', 'svg', 'math'];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root__">' . $html . '</div>',
            LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementsByTagName('div')->item(0);
        if (!$root) {
            return '';
        }

        self::sanitizeChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }
        return trim($out);
    }

    private static function sanitizeChildren(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment) {
                $child->parentNode->removeChild($child);
                continue;
            }

            if ($child instanceof \DOMText) {
                continue; // текст безопасен — при сериализации экранируется
            }

            if (!($child instanceof \DOMElement)) {
                $child->parentNode->removeChild($child); // CDATA, PI и т.п.
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $child->parentNode->removeChild($child);
                continue;
            }

            if (!isset(self::ALLOWED[$tag])) {
                // Неизвестный тег — разворачиваем: содержимое остаётся, обёртка убирается.
                self::sanitizeChildren($child);
                self::unwrap($child);
                continue;
            }

            self::cleanAttributes($child, $tag);
            self::sanitizeChildren($child);
        }
    }

    private static function cleanAttributes(\DOMElement $el, string $tag): void
    {
        $allowed = self::ALLOWED[$tag];

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);

            if (!in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->nodeName);
                continue;
            }

            if (($name === 'href' || $name === 'src') && !self::safeUrl($attr->nodeValue)) {
                $el->removeAttribute($attr->nodeName);
            }
        }

        // Внешние ссылки — принудительно безопасный rel.
        if ($tag === 'a' && $el->getAttribute('target') !== '') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function safeUrl(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }
        // Относительные, якоря, mailto/tel и явные http(s).
        if (preg_match('#^(https?:)?//#i', $url) || $url[0] === '/' || $url[0] === '#' || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return true;
        }
        // Любая другая схема (javascript:, data:, vbscript: ...) — запрещена.
        return !preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url);
    }

    private static function unwrap(\DOMElement $el): void
    {
        $parent = $el->parentNode;
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }
}
