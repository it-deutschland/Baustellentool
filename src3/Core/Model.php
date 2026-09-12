<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Base model with dynamic attributes.
 */
abstract class Model
{
    protected static string $table;

    protected static string $primaryKey = 'id';

    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function __unset(string $name): void
    {
        unset($this->attributes[$name]);
    }

    public static function find(int $id): static|null
    {
        $row = Database::getInstance()
            ->table(static::tableName())
            ->where(static::$primaryKey, $id)
            ->first();

        return $row !== null ? static::hydrate($row) : null;
    }

    public static function findBy(string $col, mixed $val): static|null
    {
        $row = Database::getInstance()
            ->table(static::tableName())
            ->where($col, $val)
            ->first();

        return $row !== null ? static::hydrate($row) : null;
    }

    /**
     * @return array<int, static>
     */
    public static function all(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $rows = Database::getInstance()
            ->table(static::tableName())
            ->orderBy($orderBy, $dir)
            ->get();

        return array_map(static fn (array $row): static => static::hydrate($row), $rows);
    }

    /**
     * @return array<int, static>
     */
    public static function where(string $col, mixed $val): array
    {
        $rows = Database::getInstance()
            ->table(static::tableName())
            ->where($col, $val)
            ->get();

        return array_map(static fn (array $row): static => static::hydrate($row), $rows);
    }

    /**
     * @param array<string, mixed> $conditions
     *
     * @return array{data: array<int, static>, total: int, pages: int, current: int}
     */
    public static function paginate(int $page, int $perPage = 20, array $conditions = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $countBuilder = Database::getInstance()->table(static::tableName());
        $dataBuilder = Database::getInstance()->table(static::tableName());

        foreach ($conditions as $column => $value) {
            $countBuilder->where((string) $column, $value);
            $dataBuilder->where((string) $column, $value);
        }

        $total = $countBuilder->count();
        $pages = max(1, (int) ceil($total / $perPage));
        $current = min($page, $pages);

        $rows = $dataBuilder
            ->limit($perPage)
            ->offset(($current - 1) * $perPage)
            ->get();

        return [
            'data' => array_map(static fn (array $row): static => static::hydrate($row), $rows),
            'total' => $total,
            'pages' => $pages,
            'current' => $current,
        ];
    }

    public function save(): bool
    {
        $database = Database::getInstance();
        $primaryKey = static::$primaryKey;
        $data = $this->attributes;

        if (isset($data[$primaryKey]) && $data[$primaryKey] !== null && $data[$primaryKey] !== '') {
            $id = $data[$primaryKey];
            unset($data[$primaryKey]);

            return $database
                ->table(static::tableName())
                ->where($primaryKey, $id)
                ->update($data);
        }

        $insertId = $database->table(static::tableName())->insert($data);
        $this->attributes[$primaryKey] = $insertId;

        return $insertId > 0;
    }

    public function delete(): bool
    {
        $primaryKey = static::$primaryKey;
        $id = $this->attributes[$primaryKey] ?? null;

        if ($id === null) {
            throw new RuntimeException('Cannot delete a model without a primary key value.');
        }

        return Database::getInstance()
            ->table(static::tableName())
            ->where($primaryKey, $id)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            $this->attributes[(string) $key] = $value;
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected static function hydrate(array $attributes): static
    {
        return new static($attributes);
    }

    protected static function tableName(): string
    {
        if (!isset(static::$table) || static::$table === '') {
            throw new RuntimeException('Model table name is not defined.');
        }

        return static::$table;
    }
}
