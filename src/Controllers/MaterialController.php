<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ControllerHelpers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;

/**
 * Manages material inventory.
 */
class MaterialController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();

        $materials = $this->db()->fetchAll('SELECT * FROM `materials` ORDER BY `category`, `name`');
        $grouped = [];
        foreach ($materials as $material) {
            $grouped[(string) $material['category']][] = $material;
        }

        $this->render('materials/index', ['materials' => $grouped]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->render('materials/create');
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $validator = Validator::make(Request::all(), [
            'category' => 'required|max:100',
            'name' => 'required|max:200',
            'unit' => 'required|max:20',
            'stock' => 'required|numeric',
            'min_stock' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the material form.');
            $this->back();
        }

        $payload = $this->materialPayload();
        $payload['created_by'] = Auth::id();
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('materials')->insert($payload);
        $materialId = (int) $this->db()->lastInsertId();
        $this->recordLog('material_created', 'materials', $materialId, 'material', [], $payload);
        Session::flash('success', 'Material created successfully.');
        $this->redirect('/materials');
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->render('materials/edit', ['material' => $this->requireRecord('materials', $id)]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $material = $this->requireRecord('materials', $id);
        $validator = Validator::make(Request::all(), [
            'category' => 'required|max:100',
            'name' => 'required|max:200',
            'unit' => 'required|max:20',
            'stock' => 'required|numeric',
            'min_stock' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the material form.');
            $this->back();
        }

        $update = $this->materialPayload();
        $update['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('materials')->where('id', (int) $id)->update($update);
        $this->recordLog('material_updated', 'materials', (int) $id, 'material', $material, $update);
        Session::flash('success', 'Material updated successfully.');
        $this->redirect('/materials');
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $material = $this->requireRecord('materials', $id);
        $this->db()->table('materials')->where('id', (int) $id)->delete();
        $this->recordLog('material_deleted', 'materials', (int) $id, 'material', $material);
        Session::flash('success', 'Material deleted successfully.');
        $this->redirect('/materials');
    }

    /**
     * @return array<string, mixed>
     */
    private function materialPayload(): array
    {
        return [
            'category' => trim((string) Request::post('category')),
            'name' => trim((string) Request::post('name')),
            'article_no' => trim((string) Request::post('article_no', '')) ?: null,
            'unit' => trim((string) Request::post('unit', 'Stk')) ?: 'Stk',
            'description' => trim((string) Request::post('description', '')) ?: null,
            'stock' => (float) Request::post('stock', 0),
            'min_stock' => (float) Request::post('min_stock', 0),
            'price' => Request::post('price') === '' ? null : (float) Request::post('price', 0),
            'supplier' => trim((string) Request::post('supplier', '')) ?: null,
            'location' => trim((string) Request::post('location', '')) ?: null,
        ];
    }
}
