<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Document;
use Throwable;

/**
 * Handles document management.
 */
class DocumentController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();

        $type = trim((string) Request::get('type', ''));
        $params = [];
        $where = '';
        if ($type !== '') {
            $where = 'WHERE d.`type` = :type';
            $params[':type'] = $type;
        }

        $documents = $this->db()->fetchAll(
            'SELECT d.*, p.`title` AS `project_title`, t.`name` AS `template_name`
             FROM `documents` d
             INNER JOIN `projects` p ON p.`id` = d.`project_id`
             LEFT JOIN `templates` t ON t.`id` = d.`template_id`
             ' . $where . '
             ORDER BY d.`updated_at` DESC',
            $params
        );

        $this->render('documents/index', ['documents' => $documents, 'filterType' => $type]);
    }

    public function create(string $pid): void
    {
        $this->requireAuth();

        $this->render('documents/create', [
            'project' => $this->requireRecord('projects', $pid),
            'templates' => $this->db()->fetchAll('SELECT * FROM `templates` ORDER BY `type`, `name`'),
        ]);
    }

    public function store(string $pid): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $project = $this->requireRecord('projects', $pid);
        $validator = Validator::make(Request::all(), [
            'title' => 'required|max:200',
            'type' => 'required|max:50',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the document form.');
            $this->back();
        }

        $templateId = $this->nullableInt(Request::post('template_id'));
        $type = trim((string) Request::post('type', 'other')) ?: 'other';
        $postedFields = Request::post('fields', []);
        $content = '';

        if (is_array($postedFields) && is_array($postedFields[$type] ?? null)) {
            $content = $this->buildStructuredDocumentContent($type, $postedFields[$type]);
        }

        if ($templateId !== null) {
            $template = $this->findRecord('templates', $templateId);
            if ($template !== null && $content === '') {
                $content = (string) ($template['content'] ?? '');
            }
        }
        if ($content === '') {
            $content = $this->buildStructuredDocumentContent($type, []);
        }

        $payload = [
            'project_id' => (int) $project['id'],
            'created_by' => Auth::id(),
            'template_id' => $templateId,
            'type' => $type,
            'title' => trim((string) Request::post('title')),
            'content' => $content,
            'version' => 1,
            'status' => trim((string) Request::post('status', 'draft')) ?: 'draft',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db()->table('documents')->insert($payload);
        $documentId = (int) $this->db()->lastInsertId();
        $this->recordLog('document_created', 'documents', $documentId, 'document', [], $payload);
        Session::flash('success', 'Document created successfully.');
        $this->redirect('/documents/' . $documentId);
    }

    public function show(string $id): void
    {
        $this->requireAuth();

        $document = $this->loadDocument((int) $id);
        if ($document === null) {
            Response::notFound();
        }

        $this->render('documents/show', [
            'document' => $document,
            'project' => ['title' => (string) ($document['project_title'] ?? '')],
            'contentHtml' => $this->renderDocumentContentHtml($document),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();

        $document = $this->loadDocument((int) $id);
        if ($document === null) {
            Response::notFound();
        }

        $this->render('documents/edit', [
            'document' => $document,
            'project' => ['title' => (string) ($document['project_title'] ?? '')],
            'typeOptions' => Document::getTypeOptions(),
            'formDefinitions' => $this->getDocumentFormDefinitions(),
            'fieldValues' => $this->extractStructuredDocumentFields((string) ($document['content'] ?? ''), (string) ($document['type'] ?? 'other')),
            'legacyContent' => $this->isStructuredDocumentContent((string) ($document['content'] ?? '')) ? '' : (string) ($document['content'] ?? ''),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->ensureCsrf();

        $document = $this->requireRecord('documents', $id);
        $validator = Validator::make(Request::all(), [
            'title' => 'required|max:200',
            'type' => 'required|max:50',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the document form.');
            $this->back();
        }

        $type = trim((string) Request::post('type', 'other')) ?: 'other';
        $allFields = Request::post('fields', []);
        $typeFields = [];
        if (is_array($allFields) && is_array($allFields[$type] ?? null)) {
            $typeFields = $allFields[$type];
        }

        $content = $this->buildStructuredDocumentContent($type, $typeFields);
        if ($content === '' && trim((string) Request::post('content', '')) !== '') {
            $content = (string) Request::post('content', '');
        }

        $update = [
            'title' => trim((string) Request::post('title')),
            'type' => $type,
            'content' => $content,
            'status' => trim((string) Request::post('status', 'draft')) ?: 'draft',
            'version' => ((int) ($document['version'] ?? 1)) + 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db()->table('documents')->where('id', (int) $id)->update($update);
        $this->recordLog('document_updated', 'documents', (int) $id, 'document', $document, $update);
        Session::flash('success', 'Document updated successfully.');
        $this->redirect('/documents/' . (int) $id . '/edit');
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $document = $this->requireRecord('documents', $id);
        $this->db()->table('documents')->where('id', (int) $id)->delete();
        $this->recordLog('document_deleted', 'documents', (int) $id, 'document', $document);
        Session::flash('success', 'Document deleted successfully.');
        $this->redirect('/documents');
    }

    public function exportPdf(string $id): void
    {
        $this->requireAuth();

        $document = $this->loadDocument((int) $id);
        if ($document === null) {
            Response::notFound();
        }

        try {
            if (class_exists('App\\Services\\PdfService')) {
                $pdfService = new \App\Services\PdfService();
                if (method_exists($pdfService, 'streamDocument')) {
                    $pdfService->streamDocument($document);
                    return;
                }
                if (method_exists($pdfService, 'stream')) {
                    $pdfService->stream((string) ($document['title'] ?? 'document'), $this->buildPrintHtml($document));
                    return;
                }
            }
        } catch (Throwable) {
        }

        $this->renderHtml($this->buildPrintHtml($document));
    }

    public function printView(string $id): void
    {
        $this->requireAuth();

        $document = $this->loadDocument((int) $id);
        if ($document === null) {
            Response::notFound();
        }

        $this->renderHtml($this->buildPrintHtml($document));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadDocument(int $documentId): ?array
    {
        $document = $this->db()->fetch(
            'SELECT d.*, p.`title` AS `project_title`, p.`project_number`, t.`name` AS `template_name`
             FROM `documents` d
             INNER JOIN `projects` p ON p.`id` = d.`project_id`
             LEFT JOIN `templates` t ON t.`id` = d.`template_id`
             WHERE d.`id` = :id
             LIMIT 1',
            [':id' => $documentId]
        );

        return $document === false ? null : $document;
    }

    /**
     * @param array<string, mixed> $document
     */
    private function buildPrintHtml(array $document): string
    {
        $content = $this->renderDocumentContentHtml($document);

        return '<!doctype html><html><head><meta charset="utf-8"><title>'
            . htmlspecialchars((string) ($document['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8')
            . '</title><style>body{font-family:Arial,sans-serif;max-width:960px;margin:40px auto;color:#222}header{border-bottom:1px solid #ccc;margin-bottom:24px;padding-bottom:12px}pre{white-space:pre-wrap;background:#f7f7f7;padding:16px;border-radius:8px}.rsa-form-table{width:100%;border-collapse:collapse}.rsa-form-table th,.rsa-form-table td{border:1px solid #d9d9d9;padding:10px;vertical-align:top}.rsa-form-table th{width:34%;background:#f5f7fa;text-align:left}.rsa-form-empty{color:#777}</style></head><body>'
            . '<header><h1>' . htmlspecialchars((string) ($document['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') . '</h1>'
            . '<p>Project: ' . htmlspecialchars((string) ($document['project_title'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p></header>'
            . '<main>' . $content . '</main></body></html>';
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function getDocumentFormDefinitions(): array
    {
        return [
            'vra' => [
                ['key' => 'authority', 'label' => 'Anordnende Behörde', 'type' => 'text'],
                ['key' => 'contact_person', 'label' => 'Sachbearbeitung', 'type' => 'text'],
                ['key' => 'street', 'label' => 'Straße / Abschnitt', 'type' => 'text'],
                ['key' => 'location', 'label' => 'Ort / Lage', 'type' => 'text'],
                ['key' => 'period', 'label' => 'Zeitraum', 'type' => 'text'],
                ['key' => 'legal_basis', 'label' => 'Rechtsgrundlage', 'type' => 'text'],
                ['key' => 'measures', 'label' => 'Anordnung / Maßnahmen', 'type' => 'textarea'],
            ],
            'signlist' => [
                ['key' => 'street', 'label' => 'Straße / Abschnitt', 'type' => 'text'],
                ['key' => 'direction', 'label' => 'Fahrtrichtung / Bereich', 'type' => 'text'],
                ['key' => 'sign_codes', 'label' => 'Verkehrszeichen (Nummern)', 'type' => 'textarea'],
                ['key' => 'additional_signs', 'label' => 'Zusatzzeichen', 'type' => 'textarea'],
                ['key' => 'remarks', 'label' => 'Bemerkungen', 'type' => 'textarea'],
            ],
            'materiallist' => [
                ['key' => 'project_reference', 'label' => 'Projektbezug', 'type' => 'text'],
                ['key' => 'delivery_date', 'label' => 'Lieferdatum', 'type' => 'date'],
                ['key' => 'items', 'label' => 'Materialpositionen', 'type' => 'textarea'],
                ['key' => 'storage_location', 'label' => 'Lagerort', 'type' => 'text'],
                ['key' => 'remarks', 'label' => 'Bemerkungen', 'type' => 'textarea'],
            ],
            'dailyreport' => [
                ['key' => 'report_date', 'label' => 'Berichtsdatum', 'type' => 'date'],
                ['key' => 'weather', 'label' => 'Wetter', 'type' => 'text'],
                ['key' => 'crew', 'label' => 'Personal / Kolonne', 'type' => 'textarea'],
                ['key' => 'work_performed', 'label' => 'Ausgeführte Arbeiten', 'type' => 'textarea'],
                ['key' => 'incidents', 'label' => 'Besonderheiten / Vorkommnisse', 'type' => 'textarea'],
            ],
            'sitecheck' => [
                ['key' => 'inspection_date', 'label' => 'Kontrolldatum', 'type' => 'date'],
                ['key' => 'inspector', 'label' => 'Kontrollierende Person', 'type' => 'text'],
                ['key' => 'location', 'label' => 'Kontrollort', 'type' => 'text'],
                ['key' => 'defects', 'label' => 'Festgestellte Mängel', 'type' => 'textarea'],
                ['key' => 'actions', 'label' => 'Sofortmaßnahmen', 'type' => 'textarea'],
                ['key' => 'safe_to_operate', 'label' => 'Verkehrssicherheit gegeben', 'type' => 'checkbox'],
            ],
            'acceptance' => [
                ['key' => 'acceptance_date', 'label' => 'Abnahmedatum', 'type' => 'date'],
                ['key' => 'client', 'label' => 'Auftraggeber', 'type' => 'text'],
                ['key' => 'contractor', 'label' => 'Auftragnehmer', 'type' => 'text'],
                ['key' => 'result', 'label' => 'Abnahmeergebnis', 'type' => 'select', 'options' => ['accepted' => 'Abgenommen', 'accepted_with_defects' => 'Mit Mängeln', 'rejected' => 'Nicht abgenommen']],
                ['key' => 'defect_log', 'label' => 'Mängelliste / Auflagen', 'type' => 'textarea'],
            ],
            'report' => [
                ['key' => 'summary', 'label' => 'Zusammenfassung', 'type' => 'textarea'],
                ['key' => 'scope', 'label' => 'Leistungsumfang', 'type' => 'textarea'],
                ['key' => 'timeline', 'label' => 'Zeitlicher Ablauf', 'type' => 'textarea'],
                ['key' => 'costs', 'label' => 'Kosten / Abrechnung', 'type' => 'textarea'],
                ['key' => 'recommendations', 'label' => 'Empfehlungen', 'type' => 'textarea'],
            ],
            'other' => [
                ['key' => 'subject', 'label' => 'Betreff', 'type' => 'text'],
                ['key' => 'details', 'label' => 'Inhalt', 'type' => 'textarea'],
            ],
        ];
    }

    private function buildStructuredDocumentContent(string $type, array $rawFields): string
    {
        $definitions = $this->getDocumentFormDefinitions();
        $normalizedType = isset($definitions[$type]) ? $type : 'other';
        $fields = [];

        foreach ($definitions[$normalizedType] as $definition) {
            $key = (string) ($definition['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $value = $rawFields[$key] ?? '';
            $fieldType = (string) ($definition['type'] ?? 'text');

            if ($fieldType === 'checkbox') {
                $fields[$key] = in_array((string) $value, ['1', 'true', 'on', 'yes'], true) ? '1' : '0';
                continue;
            }

            $fields[$key] = trim((string) $value);
        }

        return json_encode(
            [
                'format' => 'rsa21_form_v1',
                'type' => $normalizedType,
                'fields' => $fields,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeStructuredDocumentContent(string $content): ?array
    {
        if (trim($content) === '' || !str_starts_with(ltrim($content), '{')) {
            return null;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return null;
        }

        if (($decoded['format'] ?? null) !== 'rsa21_form_v1') {
            return null;
        }
        if (!is_string($decoded['type'] ?? null) || !is_array($decoded['fields'] ?? null)) {
            return null;
        }

        return $decoded;
    }

    private function isStructuredDocumentContent(string $content): bool
    {
        return $this->decodeStructuredDocumentContent($content) !== null;
    }

    /**
     * @return array<string, string>
     */
    private function extractStructuredDocumentFields(string $content, string $type): array
    {
        $decoded = $this->decodeStructuredDocumentContent($content);
        if ($decoded === null || (string) ($decoded['type'] ?? '') !== $type) {
            return [];
        }

        $values = [];
        foreach ((array) ($decoded['fields'] ?? []) as $key => $value) {
            $values[(string) $key] = trim((string) $value);
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $document
     */
    private function renderDocumentContentHtml(array $document): string
    {
        $content = (string) ($document['content'] ?? '');
        $decoded = $this->decodeStructuredDocumentContent($content);
        $typeLabels = Document::getTypeOptions();

        if ($decoded !== null) {
            $type = (string) ($decoded['type'] ?? 'other');
            $definitions = $this->getDocumentFormDefinitions()[$type] ?? $this->getDocumentFormDefinitions()['other'];
            $fields = (array) ($decoded['fields'] ?? []);

            $html = '<section><p><strong>Dokumenttyp:</strong> ' . htmlspecialchars($typeLabels[$type] ?? 'Sonstiges', ENT_QUOTES, 'UTF-8') . '</p><table class="table table-bordered rsa-form-table">';
            foreach ($definitions as $definition) {
                $key = (string) ($definition['key'] ?? '');
                $label = (string) ($definition['label'] ?? $key);
                $fieldType = (string) ($definition['type'] ?? 'text');
                $raw = trim((string) ($fields[$key] ?? ''));

                if ($fieldType === 'checkbox') {
                    $valueHtml = in_array($raw, ['1', 'true', 'on', 'yes'], true) ? 'Ja' : 'Nein';
                } elseif ($fieldType === 'select') {
                    $options = is_array($definition['options'] ?? null) ? $definition['options'] : [];
                    $valueHtml = htmlspecialchars((string) ($options[$raw] ?? $raw), ENT_QUOTES, 'UTF-8');
                } elseif ($raw === '') {
                    $valueHtml = '<span class="rsa-form-empty">—</span>';
                } else {
                    $valueHtml = nl2br(htmlspecialchars($raw, ENT_QUOTES, 'UTF-8'));
                }

                $html .= '<tr><th>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</th><td>' . $valueHtml . '</td></tr>';
            }
            $html .= '</table></section>';

            return $html;
        }

        if ($content !== '' && str_starts_with(ltrim($content), '{')) {
            $decodedJson = json_decode($content, true);
            if (is_array($decodedJson)) {
                return '<pre>' . htmlspecialchars(json_encode($decodedJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', ENT_QUOTES, 'UTF-8') . '</pre>';
            }
        }

        if (trim($content) === '') {
            return '<p class="rsa-form-empty">Für dieses Dokument ist noch kein Inhalt vorhanden.</p>';
        }

        return $content;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
