<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight input validator.
 */
class Validator
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @var array<string, string|array<int, string>>
     */
    private array $rules = [];

    /**
     * @var array<string, array<int, string>>
     */
    private array $errors = [];

    private bool $validated = false;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     */
    public static function make(array $data, array $rules): static
    {
        $validator = new static($data);
        $validator->rules = $rules;

        return $validator;
    }

    public function validate(): bool
    {
        $this->errors = [];
        $this->validated = true;

        foreach ($this->rules as $field => $rules) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_array($rules) ? $rules : explode('|', $rules);

            foreach ($ruleList as $rule) {
                $this->applyRule((string) $field, $value, (string) $rule);
            }
        }

        return $this->errors === [];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        if (!$this->validated) {
            $this->validate();
        }

        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors()[$field][0] ?? null;
    }

    public function passes(): bool
    {
        return $this->validate();
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
        $name = strtolower(trim($name));

        if ($name === '') {
            return;
        }

        if ($value === null || $value === '') {
            if ($name === 'required') {
                $this->addError($field, sprintf('The %s field is required.', $field));
            }

            return;
        }

        match ($name) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ?: $this->addError($field, sprintf('The %s field must be a valid email address.', $field)),
            'numeric' => is_numeric($value) ?: $this->addError($field, sprintf('The %s field must be numeric.', $field)),
            'alpha' => preg_match('/^[\p{L}]+$/u', (string) $value) === 1 ?: $this->addError($field, sprintf('The %s field may only contain letters.', $field)),
            'alphanumeric' => preg_match('/^[\p{L}\p{N}]+$/u', (string) $value) === 1 ?: $this->addError($field, sprintf('The %s field may only contain letters and numbers.', $field)),
            'confirmed' => $this->confirmField($field, $value),
            'min' => $this->checkMin($field, $value, $parameter),
            'max' => $this->checkMax($field, $value, $parameter),
            'unique' => $this->checkUnique($field, $value, $parameter),
            'exists' => $this->checkExists($field, $value, $parameter),
            default => null,
        };
    }

    private function confirmField(string $field, mixed $value): void
    {
        $confirmationField = $field . '_confirmation';
        if (($this->data[$confirmationField] ?? null) !== $value) {
            $this->addError($field, sprintf('The %s confirmation does not match.', $field));
        }
    }

    private function checkMin(string $field, mixed $value, ?string $parameter): void
    {
        $limit = (int) $parameter;
        if (is_numeric($value)) {
            if ((float) $value < $limit) {
                $this->addError($field, sprintf('The %s field must be at least %d.', $field, $limit));
            }

            return;
        }

        $length = is_array($value) ? count($value) : mb_strlen((string) $value);
        if ($length < $limit) {
            $this->addError($field, sprintf('The %s field must be at least %d characters.', $field, $limit));
        }
    }

    private function checkMax(string $field, mixed $value, ?string $parameter): void
    {
        $limit = (int) $parameter;
        if (is_numeric($value)) {
            if ((float) $value > $limit) {
                $this->addError($field, sprintf('The %s field may not be greater than %d.', $field, $limit));
            }

            return;
        }

        $length = is_array($value) ? count($value) : mb_strlen((string) $value);
        if ($length > $limit) {
            $this->addError($field, sprintf('The %s field may not be greater than %d characters.', $field, $limit));
        }
    }

    private function checkUnique(string $field, mixed $value, ?string $parameter): void
    {
        [$table, $column] = $this->parseTableColumn($parameter, $field);
        $row = Database::getInstance()->fetch(
            sprintf('SELECT COUNT(*) AS aggregate FROM `%s` WHERE `%s` = :value', $table, $column),
            [':value' => $value]
        );

        if ((int) ($row['aggregate'] ?? 0) > 0) {
            $this->addError($field, sprintf('The %s field has already been taken.', $field));
        }
    }

    private function checkExists(string $field, mixed $value, ?string $parameter): void
    {
        [$table, $column] = $this->parseTableColumn($parameter, $field);
        $row = Database::getInstance()->fetch(
            sprintf('SELECT COUNT(*) AS aggregate FROM `%s` WHERE `%s` = :value', $table, $column),
            [':value' => $value]
        );

        if ((int) ($row['aggregate'] ?? 0) === 0) {
            $this->addError($field, sprintf('The selected %s is invalid.', $field));
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseTableColumn(?string $parameter, string $field): array
    {
        $parameter ??= '';
        [$table, $column] = array_pad(explode('.', $parameter, 2), 2, null);
        $sanitize = static fn (string $value): string => preg_replace('/[^a-zA-Z0-9_]/', '', $value) ?: '';
        $defaultField = $sanitize($field);

        return [
            $table !== null && $table !== '' ? $sanitize($table) : $defaultField,
            $column !== null && $column !== '' ? $sanitize($column) : $defaultField,
        ];
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field] ??= [];
        if (!in_array($message, $this->errors[$field], true)) {
            $this->errors[$field][] = $message;
        }
    }
}
