<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Pagination helper for Bootstrap 5 markup.
 */
class Pagination
{
    private int $total;

    private int $perPage;

    private int $currentPage;

    public function __construct(int $total, int $perPage, int $currentPage)
    {
        $this->total = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->currentPage = max(1, $currentPage);
    }

    public function links(string $urlPattern = '?page={page}'): string
    {
        $totalPages = $this->totalPages();
        if ($totalPages <= 1) {
            return '';
        }

        $start = max(1, $this->currentPage - 2);
        $end = min($totalPages, $this->currentPage + 2);

        $html = '<nav aria-label="Pagination"><ul class="pagination">';
        $html .= $this->pageItem($urlPattern, max(1, $this->currentPage - 1), '&laquo;', $this->currentPage <= 1);

        for ($page = $start; $page <= $end; $page++) {
            $html .= $this->pageItem($urlPattern, $page, (string) $page, false, $page === $this->currentPage);
        }

        $html .= $this->pageItem($urlPattern, min($totalPages, $this->currentPage + 1), '&raquo;', $this->currentPage >= $totalPages);
        $html .= '</ul></nav>';

        return $html;
    }

    public function totalPages(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    private function pageItem(string $urlPattern, int $page, string $label, bool $disabled = false, bool $active = false): string
    {
        $classes = ['page-item'];
        if ($disabled) {
            $classes[] = 'disabled';
        }
        if ($active) {
            $classes[] = 'active';
        }

        $href = $disabled ? '#' : str_replace('{page}', (string) $page, $urlPattern);

        return sprintf(
            '<li class="%s"><a class="page-link" href="%s">%s</a></li>',
            implode(' ', $classes),
            htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $label
        );
    }
}
