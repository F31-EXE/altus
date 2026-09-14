<?php

namespace App\Core;

/**
 * Простейшая валидация набора значений.
 * Правила:
 *   required, string, email, numeric, in:a,b,c
 *   min:N / max:N        — числовые границы значения
 *   minlen:N / maxlen:N  — длина строки в символах
 */
final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(array $rules, array $labels = []): bool
    {
        foreach ($rules as $field => $ruleStr) {
            $value = $this->data[$field] ?? null;
            $label = $labels[$field] ?? $field;

            foreach (explode('|', $ruleStr) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($name) {
                    case 'required':
                        if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                            $this->addError($field, "Поле «{$label}» обязательно");
                        }
                        break;

                    case 'string':
                        if ($value !== null && !is_string($value)) {
                            $this->addError($field, "Поле «{$label}» должно быть строкой");
                        }
                        break;

                    case 'email':
                        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, "Поле «{$label}» — некорректный email");
                        }
                        break;

                    case 'numeric':
                        if ($value !== null && $value !== '' && !is_numeric($value)) {
                            $this->addError($field, "Поле «{$label}» должно быть числом");
                        }
                        break;

                    case 'min':
                        if ($value !== null && $value !== '' && is_numeric($value) && $value + 0 < (float) $param) {
                            $this->addError($field, "Поле «{$label}» не меньше {$param}");
                        }
                        break;

                    case 'max':
                        if ($value !== null && $value !== '' && is_numeric($value) && $value + 0 > (float) $param) {
                            $this->addError($field, "Поле «{$label}» не больше {$param}");
                        }
                        break;

                    case 'minlen':
                        if (is_string($value) && $value !== '' && mb_strlen($value) < (int) $param) {
                            $this->addError($field, "Поле «{$label}» — минимум {$param} символов");
                        }
                        break;

                    case 'maxlen':
                        if (is_string($value) && mb_strlen($value) > (int) $param) {
                            $this->addError($field, "Поле «{$label}» — максимум {$param} символов");
                        }
                        break;

                    case 'in':
                        $allowed = explode(',', (string) $param);
                        if ($value !== null && $value !== '' && !in_array((string) $value, $allowed, true)) {
                            $this->addError($field, "Поле «{$label}» имеет недопустимое значение");
                        }
                        break;
                }
            }
        }

        return $this->passes();
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /** Плоский список всех сообщений. */
    public function allMessages(): array
    {
        return array_merge(...array_values($this->errors ?: [[]]));
    }
}
