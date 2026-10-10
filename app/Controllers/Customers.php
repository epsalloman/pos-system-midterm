<?php

namespace App\Controllers;

use App\Models\CustomerModel;

class Customers extends BaseController
{
    public function index()
    {
        helper(['form', 'url']);

        $customerModel = new CustomerModel();
        $customers = $customerModel->findAll();

        return view('customers', ['customers' => $customers]);
    }

    public function new()
    {
        helper(['form', 'url']);

        return view('customers_new');
    }

    public function create()
    {
        $data = $this->customerDataFromRequest();

        $rules = [
            'full_name' => 'required|min_length[2]|max_length[100]',
            'email'     => 'required|valid_email|max_length[100]|is_unique[customers.email]',
            'phone'     => 'permit_empty|regex_match[/^09[0-9]{9}$/]',
        ];

        if (! $this->validateData($data, $rules, $this->validationMessages())) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data['created_at'] = date('Y-m-d H:i:s');

        $customerModel = new CustomerModel();
        $customerModel->insert($data);

        return redirect()->to('/customers')
            ->with('success', 'Customer added successfully.');
    }

    public function edit(int $id)
    {
        helper(['form', 'url']);

        $customerModel = new CustomerModel();
        $customer = $customerModel->find($id);

        if ($customer === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Customer record not found.'
            );
        }

        return view('customers_edit', ['customer' => $customer]);
    }

    public function update(int $id)
    {
        $customerModel = new CustomerModel();
        $customer = $customerModel->find($id);

        if ($customer === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Customer record not found.'
            );
        }

        $data = $this->customerDataFromRequest();

        $rules = [
            'full_name' => 'required|min_length[2]|max_length[100]',
            'email'     => "required|valid_email|max_length[100]|is_unique[customers.email,id,{$id}]",
            'phone'     => 'permit_empty|regex_match[/^09[0-9]{9}$/]',
        ];

        if (! $this->validateData($data, $rules, $this->validationMessages())) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $customerModel->update($id, $data);

        return redirect()->to('/customers')
            ->with('success', 'Customer updated successfully.');
    }

    public function delete(int $id)
    {
        $customerModel = new CustomerModel();
        if (! $customerModel->find($id)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Customer record not found.');
        $customerModel->delete($id);
        return redirect()->to('/customers')->with('success', 'Customer deleted. Related sales will remain as walk-in sales.');
    }

    private function customerDataFromRequest(): array
    {
        return [
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'email'     => strtolower(trim((string) $this->request->getPost('email'))),
            'phone'     => trim((string) $this->request->getPost('phone')),
        ];
    }

    private function validationMessages(): array
    {
        return [
            'full_name' => [
                'required'   => 'Full name is required.',
                'min_length' => 'Full name must contain at least 2 characters.',
                'max_length' => 'Full name cannot exceed 100 characters.',
            ],
            'email' => [
                'required'    => 'Email is required.',
                'valid_email' => 'Enter a valid email address.',
                'max_length'  => 'Email cannot exceed 100 characters.',
                'is_unique'   => 'That email address is already assigned to another customer.',
            ],
            'phone' => [
                'regex_match' => 'Phone must be an 11-digit Philippine mobile number beginning with 09.',
            ],
        ];
    }
}
