<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Customer model.
 */
class Customer extends Model
{
    protected static string $table = 'customers';

    /**
     * Get all projects for the customer.
     *
     * @return array<int, Project>
     */
    public function getProjects(): array
    {
        return Project::where('customer_id', (int) ($this->id ?? 0));
    }

    /**
     * Get the formatted multi-line address.
     */
    public function getFullAddress(): string
    {
        $lines = array_filter([
            (string) ($this->company ?? ''),
            (string) ($this->address ?? ''),
            trim(sprintf('%s %s', (string) ($this->zip ?? ''), (string) ($this->city ?? ''))),
            (string) ($this->country ?? ''),
        ], static fn (string $value): bool => $value !== '');

        return implode(PHP_EOL, $lines);
    }
}
