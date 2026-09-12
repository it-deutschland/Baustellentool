<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/**
 * Minimal fluent SQL builder.
 */
class QueryBuilder
{
    /**
     * @var list<string>
     */
    private array $selects = ['*'];

    /**
     * @var list<array{boolean: string, sql: string}>
     */
    private array $wheres = [];

    /**
     * @var array<string, mixed>
     */
    private array $bindings = [];

    /**
     * @var list<string>
     */
    private array $orders = [];

    private ?int $limitValue = null;

    private ?int $offsetValue = null;

    private int $bindingCounter = 0;

    public function __construct(
        private readonly Database $database,
        private readonly string $table
    ) {
    }

    public function select(string ...$cols): static
    {
        if ($cols !== []) {
            $this->selects = $cols;
        }

        return $this;
    }

    public function where(string $col, mixed $val, string $op = '='): static
    {
        return $this->addWhere('AND', $col, $val, $op);
    }

    public function orWhere(string $col, mixed $val, string $op = '='): static
    {
        return $this->addWhere('OR', $col, $val, $op);
    }

    public function orderBy(string $col, string $dir = 'ASC'): static
    {
        $direction = strtoupper($dir);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Invalid order direction supplied.');
        }

        $this->orders[] = $this->quoteIdentifier($col) . ' ' . $direction;

        return $this;
    }

    public function limit(int $n): static
    {
        $this->limitValue = max(0, $n);

        return $this;
    }

    public function offset(int $n): static
    {
        $this->offsetValue = max(0, $n);

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $sql = sprintf(
            'SELECT %s FROM %s%s%s%s',
            implode(', ', array_map(fn (string $column): string => $this->columnExpression($column), $this->selects)),
            $this->quoteIdentifier($this->table),
            $this->compileWheres(),
            $this->compileOrderBy(),
            $this->compileLimitOffset()
        );

        return $this->database->fetchAll($sql, $this->bindings);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $clone = clone $this;
        $clone->limit(1);

        return $clone->get()[0] ?? null;
    }

    public function count(): int
    {
        $sql = sprintf(
            'SELECT COUNT(*) AS aggregate FROM %s%s',
            $this->quoteIdentifier($this->table),
            $this->compileWheres()
        );
        $row = $this->database->fetch($sql, $this->bindings);

        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        if ($data === []) {
            throw new InvalidArgumentException('Insert data cannot be empty.');
        }

        $columns = array_keys($data);
        $placeholders = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $placeholder = ':insert_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . '_' . count($bindings);
            $placeholders[] = $placeholder;
            $bindings[$placeholder] = $value;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($this->table),
            implode(', ', array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns)),
            implode(', ', $placeholders)
        );

        $this->database->query($sql, $bindings);

        return (int) $this->database->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data): bool
    {
        if ($data === []) {
            throw new InvalidArgumentException('Update data cannot be empty.');
        }

        if ($this->wheres === []) {
            throw new InvalidArgumentException('Refusing to update without a where clause.');
        }

        $sets = [];
        $bindings = $this->bindings;

        foreach ($data as $column => $value) {
            $placeholder = ':update_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . '_' . count($bindings);
            $sets[] = $this->quoteIdentifier($column) . ' = ' . $placeholder;
            $bindings[$placeholder] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s%s%s',
            $this->quoteIdentifier($this->table),
            implode(', ', $sets),
            $this->compileWheres(),
            $this->compileLimitOffset(false)
        );

        return $this->database->execute($sql, $bindings);
    }

    public function delete(): bool
    {
        if ($this->wheres === []) {
            throw new InvalidArgumentException('Refusing to delete without a where clause.');
        }

        $sql = sprintf(
            'DELETE FROM %s%s%s',
            $this->quoteIdentifier($this->table),
            $this->compileWheres(),
            $this->compileLimitOffset(false)
        );

        return $this->database->execute($sql, $this->bindings);
    }

    private function addWhere(string $boolean, string $col, mixed $val, string $op): static
    {
        $allowedOperators = ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE'];
        $operator = strtoupper($op);

        if (!in_array($operator, $allowedOperators, true)) {
            throw new InvalidArgumentException('Invalid where operator supplied.');
        }

        $placeholder = ':where_' . $this->bindingCounter++;
        $this->bindings[$placeholder] = $val;
        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => $this->quoteIdentifier($col) . ' ' . $operator . ' ' . $placeholder,
        ];

        return $this;
    }

    private function compileWheres(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $segments = [];
        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0 ? ' WHERE ' : ' ' . $where['boolean'] . ' ';
            $segments[] = $prefix . $where['sql'];
        }

        return implode('', $segments);
    }

    private function compileOrderBy(): string
    {
        return $this->orders === [] ? '' : ' ORDER BY ' . implode(', ', $this->orders);
    }

    private function compileLimitOffset(bool $includeOffset = true): string
    {
        $sql = '';

        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($includeOffset && $this->offsetValue !== null) {
            if ($this->limitValue === null) {
                $sql .= ' LIMIT 18446744073709551615';
            }

            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $sql;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if ($identifier === '*') {
            return $identifier;
        }

        if (preg_match('/\s+AS\s+/i', $identifier) === 1) {
            [$column, $alias] = preg_split('/\s+AS\s+/i', $identifier) ?: [$identifier, $identifier];

            return $this->quoteIdentifier($column) . ' AS ' . $this->quoteIdentifier($alias);
        }

        $segments = array_map(
            static fn (string $segment): string => '`' . str_replace('`', '``', $segment) . '`',
            explode('.', $identifier)
        );

        return implode('.', $segments);
    }

    private function columnExpression(string $column): string
    {
        $trimmed = trim($column);

        if ($trimmed === '*' || str_contains($trimmed, '(')) {
            return $trimmed;
        }

        return $this->quoteIdentifier($trimmed);
    }
}
