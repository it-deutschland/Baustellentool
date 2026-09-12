<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * PDF generation service – pure PHP, no external libraries.
 * Produces valid PDF 1.4 documents using a lightweight built-in renderer.
 * For advanced layouts the browser print / window.print() path is preferred.
 *
 * @license MIT
 */
class PdfService
{
    private array $pages    = [];
    private array $fonts    = [];
    private string $buffer  = '';
    private array  $offsets = [];
    private int    $objNum  = 0;
    private float  $pageW;
    private float  $pageH;
    private string $pageSize;
    private string $orientation;

    // Font metrics (Helvetica, approx.)
    private const CHAR_WIDTH = 0.6;   // em units per char at 1pt

    public function __construct(string $size = 'A4', string $orientation = 'P')
    {
        $this->pageSize    = $size;
        $this->orientation = $orientation;
        [$this->pageW, $this->pageH] = $this->getDimensions($size, $orientation);
    }

    // ── Public API ───────────────────────────────────────────

    /**
     * Generate a complete document PDF from an array of sections.
     *
     * @param array $doc ['title'=>string, 'author'=>string, 'sections'=>[['type'=>'heading'|'text'|'table'|'hline', 'content'=>...]]]
     */
    public function generate(array $doc): string
    {
        $this->buffer  = '';
        $this->pages   = [];
        $this->offsets = [];
        $this->objNum  = 0;

        $this->writePdfHeader();

        // Add pages
        $renderer = new PdfPageRenderer($this->pageW, $this->pageH);
        $pages = $renderer->layout($doc['sections'] ?? []);

        foreach ($pages as $pageContent) {
            $this->addPage($pageContent);
        }

        $this->writePdfFooter($doc['title'] ?? 'Dokument', $doc['author'] ?? APP_NAME);

        return $this->buffer;
    }

    /**
     * Stream PDF to browser.
     */
    public function stream(array $doc, string $filename = 'dokument.pdf'): void
    {
        $pdf = $this->generate($doc);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0');
        echo $pdf;
        exit;
    }

    /**
     * Save PDF to file.
     */
    public function save(array $doc, string $path): bool
    {
        $pdf = $this->generate($doc);
        return (bool) file_put_contents($path, $pdf);
    }

    // ── PDF internals ────────────────────────────────────────

    private function writePdfHeader(): void
    {
        $this->out('%PDF-1.4');
        $this->out('%âãÏÓ'); // binary hint
    }

    private function addPage(string $content): void
    {
        // Content stream object
        $stream = $this->compressStream($content);
        $this->startObj();
        $this->out('<<');
        $this->out('/Filter /FlateDecode');
        $this->out('/Length ' . strlen($stream));
        $this->out('>>');
        $this->out('stream');
        $this->buffer .= $stream;
        $this->out('endstream');
        $this->out('endobj');
        $contentObjId = $this->objNum;

        // Page object
        $this->startObj();
        $this->out('<<');
        $this->out('/Type /Page');
        $this->out('/Parent 2 0 R');
        $this->out('/MediaBox [0 0 ' . round($this->pageW, 2) . ' ' . round($this->pageH, 2) . ']');
        $this->out('/Contents ' . $contentObjId . ' 0 R');
        $this->out('/Resources <<');
        $this->out('/Font << /F1 4 0 R /F2 5 0 R >>');
        $this->out('>>');
        $this->out('>>');
        $this->out('endobj');
        $this->pages[] = $this->objNum;
    }

    private function writePdfFooter(string $title, string $author): void
    {
        // Object 1: catalog (written last, referenced by xref)
        // We use a simple sequential structure.

        // Obj 2: pages
        $this->startObj();
        $kidsStr = implode(' 0 R ', $this->pages) . ' 0 R';
        $this->out('<<');
        $this->out('/Type /Pages');
        $this->out('/Kids [' . $kidsStr . ']');
        $this->out('/Count ' . count($this->pages));
        $this->out('>>');
        $this->out('endobj');
        $pagesId = $this->objNum;

        // Obj 3: catalog
        $this->startObj();
        $this->out('<<');
        $this->out('/Type /Catalog');
        $this->out('/Pages ' . $pagesId . ' 0 R');
        $this->out('>>');
        $this->out('endobj');
        $catalogId = $this->objNum;

        // Obj 4: base font Helvetica
        $this->startObj();
        $this->out('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $this->out('endobj');

        // Obj 5: Helvetica-Bold
        $this->startObj();
        $this->out('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');
        $this->out('endobj');

        // XRef table
        $xrefOffset = strlen($this->buffer);
        $this->out('xref');
        $this->out('0 ' . ($this->objNum + 1));
        $this->out('0000000000 65535 f ');
        foreach ($this->offsets as $offset) {
            $this->out(str_pad((string) $offset, 10, '0', STR_PAD_LEFT) . ' 00000 n ');
        }

        // Trailer
        $this->out('trailer');
        $this->out('<< /Size ' . ($this->objNum + 1) . ' /Root ' . $catalogId . ' 0 R >>');
        $this->out('startxref');
        $this->out((string) $xrefOffset);
        $this->out('%%EOF');
    }

    private function startObj(): void
    {
        $this->objNum++;
        $this->offsets[$this->objNum] = strlen($this->buffer);
        $this->out($this->objNum . ' 0 obj');
    }

    private function out(string $s): void
    {
        $this->buffer .= $s . "\n";
    }

    private function compressStream(string $data): string
    {
        if (function_exists('gzcompress')) {
            return gzcompress($data, 6);
        }
        return $data;
    }

    private function getDimensions(string $size, string $orientation): array
    {
        $sizes = [
            'A4'  => [595.28, 841.89],
            'A3'  => [841.89, 1190.55],
            'A2'  => [1190.55, 1683.78],
            'Letter' => [612.0, 792.0],
        ];
        [$w, $h] = $sizes[$size] ?? $sizes['A4'];
        if ($orientation === 'L') {
            return [$h, $w];
        }
        return [$w, $h];
    }
}

/**
 * Handles page layout and content stream generation.
 */
class PdfPageRenderer
{
    private float $pageW;
    private float $pageH;
    private float $marginL = 56.7;  // 2cm
    private float $marginR = 56.7;
    private float $marginT = 70.0;
    private float $marginB = 56.7;
    private float $contentW;
    private float $y;
    private int   $pageNum = 0;
    private array $pages   = [];
    private string $current = '';

    public function __construct(float $pageW, float $pageH)
    {
        $this->pageW    = $pageW;
        $this->pageH    = $pageH;
        $this->contentW = $pageW - $this->marginL - $this->marginR;
    }

    /** @return string[] list of content streams per page */
    public function layout(array $sections): array
    {
        $this->newPage();

        foreach ($sections as $section) {
            match ($section['type'] ?? 'text') {
                'heading'  => $this->addHeading($section['content'] ?? '', (int)($section['level'] ?? 1)),
                'text'     => $this->addText($section['content'] ?? ''),
                'hline'    => $this->addHLine(),
                'table'    => $this->addTable($section['headers'] ?? [], $section['rows'] ?? []),
                'spacer'   => $this->addSpacer((float)($section['height'] ?? 10)),
                default    => null,
            };
        }

        $this->pages[] = $this->current;
        return $this->pages;
    }

    private function newPage(): void
    {
        if ($this->current !== '') {
            $this->pages[] = $this->current;
        }
        $this->current = '';
        $this->y = $this->pageH - $this->marginT;
        $this->pageNum++;
        // Page border
        $this->cmd(sprintf(
            '%.2f %.2f %.2f %.2f re S',
            $this->marginL - 5, $this->marginB - 5,
            $this->contentW + 10, $this->pageH - $this->marginT - $this->marginB + 10
        ));
    }

    private function checkSpace(float $needed): void
    {
        if ($this->y - $needed < $this->marginB) {
            $this->newPage();
        }
    }

    private function addHeading(string $text, int $level): void
    {
        $fontSize = match($level) { 1 => 16, 2 => 13, default => 11 };
        $spaceAbove = $level === 1 ? 14 : 8;
        $spaceBelow = 6;
        $lineH = $fontSize * 1.4;
        $this->checkSpace($spaceAbove + $lineH + $spaceBelow);
        $this->y -= $spaceAbove;
        $this->textLine($text, $this->marginL, $this->y, $fontSize, true);
        $this->y -= $lineH + $spaceBelow;
        if ($level <= 2) {
            $this->cmd(sprintf(
                '%.2f %.2f %.2f %.2f re f',
                $this->marginL, $this->y + 2, $this->contentW, 1
            ));
        }
    }

    private function addText(string $text, float $fontSize = 10): void
    {
        $lineH    = $fontSize * 1.5;
        $maxChars = (int) floor($this->contentW / ($fontSize * 0.5));
        $lines    = $this->wrapText($text, $maxChars);
        foreach ($lines as $line) {
            $this->checkSpace($lineH);
            $this->textLine($line, $this->marginL, $this->y, $fontSize, false);
            $this->y -= $lineH;
        }
    }

    private function addHLine(): void
    {
        $this->checkSpace(4);
        $this->cmd(sprintf(
            '%.2f %.2f m %.2f %.2f l S',
            $this->marginL, $this->y,
            $this->marginL + $this->contentW, $this->y
        ));
        $this->y -= 6;
    }

    private function addSpacer(float $h): void
    {
        $this->y -= $h;
    }

    private function addTable(array $headers, array $rows): void
    {
        $cols   = count($headers);
        $colW   = $cols > 0 ? $this->contentW / $cols : $this->contentW;
        $rowH   = 16.0;
        $pad    = 4.0;
        $fSize  = 9;

        // Header row
        $this->checkSpace($rowH * 2);
        $x = $this->marginL;
        // Header background
        $this->cmd(sprintf('0.2 0.4 0.8 rg'));
        $this->cmd(sprintf('%.2f %.2f %.2f %.2f re f', $x, $this->y - $rowH, $this->contentW, $rowH));
        $this->cmd(sprintf('0 0 0 rg'));
        foreach ($headers as $i => $h) {
            $this->textLine((string)$h, $x + $pad, $this->y - $rowH + $pad, $fSize, true, [1,1,1]);
            $x += $colW;
        }
        $this->y -= $rowH;

        // Data rows
        $alt = false;
        foreach ($rows as $row) {
            $this->checkSpace($rowH + 2);
            $x = $this->marginL;
            if ($alt) {
                $this->cmd(sprintf('0.95 0.95 0.95 rg'));
                $this->cmd(sprintf('%.2f %.2f %.2f %.2f re f', $x, $this->y - $rowH, $this->contentW, $rowH));
                $this->cmd(sprintf('0 0 0 rg'));
            }
            foreach (array_values($row) as $i => $cell) {
                $this->textLine((string)$cell, $x + $pad, $this->y - $rowH + $pad + 2, $fSize, false);
                $x += $colW;
            }
            // Row border
            $this->cmd(sprintf(
                '0.8 0.8 0.8 RG %.2f %.2f m %.2f %.2f l S 0 0 0 RG',
                $this->marginL, $this->y - $rowH,
                $this->marginL + $this->contentW, $this->y - $rowH
            ));
            $this->y -= $rowH;
            $alt = !$alt;
        }
        $this->y -= 4;
    }

    private function textLine(
        string $text, float $x, float $y, float $size,
        bool $bold = false, array $rgb = [0, 0, 0]
    ): void {
        $font   = $bold ? '/F2' : '/F1';
        $r = $rgb[0]; $g = $rgb[1]; $b = $rgb[2];
        $safe   = $this->pdfString($text);
        $this->cmd(sprintf(
            'BT %s %.2f Tf %.4f %.4f %.4f rg %.2f %.2f Td (%s) Tj ET',
            $font, $size, $r, $g, $b, $x, $y, $safe
        ));
    }

    private function pdfString(string $s): string
    {
        // Encode to Latin-1 (WinAnsiEncoding), escape special chars
        $s = mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '\\r', '\\n'], $s);
    }

    private function cmd(string $s): void
    {
        $this->current .= $s . "\n";
    }

    private function wrapText(string $text, int $maxChars): array
    {
        $lines = [];
        foreach (explode("\n", $text) as $para) {
            $words = explode(' ', $para);
            $line  = '';
            foreach ($words as $word) {
                if (strlen($line . ' ' . $word) > $maxChars && $line !== '') {
                    $lines[] = $line;
                    $line    = $word;
                } else {
                    $line = $line === '' ? $word : $line . ' ' . $word;
                }
            }
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        return $lines ?: [''];
    }
}
