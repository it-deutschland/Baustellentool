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
 * Manages customers.
 */
class CustomerController extends Controller
{
    use ControllerHelpers;

    public function index(): void
    {
        $this->requireAuth();
        $customers = $this->db()->fetchAll('SELECT * FROM `customers` ORDER BY `company` ASC');
        $this->render('customers/index', ['customers' => $customers]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->render('customers/create');
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $validator = Validator::make(Request::all(), [
            'company' => 'required|max:200',
            'email' => 'max:191',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the customer form.');
            $this->back();
        }

        $payload = $this->customerPayload();
        $payload['created_by'] = Auth::id();
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('customers')->insert($payload);
        $customerId = (int) $this->db()->lastInsertId();
        $this->recordLog('customer_created', 'customers', $customerId, 'customer', [], $payload);
        Session::flash('success', 'Customer created successfully.');
        $this->redirect('/customers');
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->render('customers/edit', ['customer' => $this->requireRecord('customers', $id)]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $customer = $this->requireRecord('customers', $id);
        $validator = Validator::make(Request::all(), [
            'company' => 'required|max:200',
            'email' => 'max:191',
        ]);
        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), Request::all());
            Session::flash('error', 'Please correct the customer form.');
            $this->back();
        }

        $update = $this->customerPayload();
        $update['updated_at'] = date('Y-m-d H:i:s');
        $this->db()->table('customers')->where('id', (int) $id)->update($update);
        $this->recordLog('customer_updated', 'customers', (int) $id, 'customer', $customer, $update);
        Session::flash('success', 'Customer updated successfully.');
        $this->redirect('/customers');
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->enforceRole('editor');
        $this->ensureCsrf();

        $customer = $this->requireRecord('customers', $id);
        $this->db()->table('customers')->where('id', (int) $id)->delete();
        $this->recordLog('customer_deleted', 'customers', (int) $id, 'customer', $customer);
        Session::flash('success', 'Customer deleted successfully.');
        $this->redirect('/customers');
    }

    /**
     * @return array<string, mixed>
     */
    private function customerPayload(): array
    {
        return [
            'company' => trim((string) Request::post('company')),
            'contact_name' => trim((string) Request::post('contact_name', '')) ?: null,
            'email' => trim((string) Request::post('email', '')) ?: null,
            'phone' => trim((string) Request::post('phone', '')) ?: null,
            'address' => trim((string) Request::post('address', '')) ?: null,
            'city' => trim((string) Request::post('city', '')) ?: null,
            'zip' => trim((string) Request::post('zip', '')) ?: null,
            'country' => trim((string) Request::post('country', 'Deutschland')) ?: 'Deutschland',
            'notes' => trim((string) Request::post('notes', '')) ?: null,
        ];
    }
}
