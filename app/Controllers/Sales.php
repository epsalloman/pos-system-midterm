<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\ProductModel;

class Sales extends BaseController
{
    public function new()
    {
        helper(['form', 'url']);
        return view('sales/new', [
            'products' => (new ProductModel())->where('is_active', 1)->where('stock_quantity >', 0)->orderBy('name')->findAll(),
            'customers' => (new CustomerModel())->orderBy('full_name')->findAll(),
        ]);
    }

    public function create()
    {
        $rules = ['product_id' => 'required|is_natural_no_zero', 'quantity' => 'required|is_natural_no_zero', 'customer_id' => 'permit_empty|is_natural_no_zero'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $db = db_connect();
        $productId = (int) $this->request->getPost('product_id');
        $quantity = (int) $this->request->getPost('quantity');
        $db->transBegin();
        try {
            $product = $db->query('SELECT id, price, stock_quantity FROM products WHERE id = ? AND is_active = 1 FOR UPDATE', [$productId])->getRowArray();
            if (! $product || (int) $product['stock_quantity'] < $quantity) {
                $db->transRollback();
                return redirect()->back()->withInput()->with('error', 'Sale rejected: requested quantity exceeds available stock.');
            }
            $db->table('products')->set('stock_quantity', 'stock_quantity - ' . $quantity, false)->where('id', $productId)->where('stock_quantity >=', $quantity)->update();
            if ($db->affectedRows() !== 1) {
                $db->transRollback();
                return redirect()->back()->withInput()->with('error', 'Stock changed during checkout. Please try again.');
            }
            $customerId = $this->request->getPost('customer_id') ?: null;
            $db->query(
                'INSERT INTO sales (product_id, customer_id, sold_by, quantity, total_price, created_at)
                 SELECT id, ?, ?, ?, CAST(price * ? AS DECIMAL(10,2)), NOW() FROM products WHERE id = ?',
                [$customerId, (int) session()->get('user_id'), $quantity, $quantity, $productId]
            );
            if ($db->transStatus() === false) throw new \RuntimeException('Database transaction failed.');
            $db->transCommit();
            return redirect()->to('/sales')->with('success', 'Sale recorded and inventory updated.');
        } catch (\Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Sale transaction failed: {message}', ['message' => $exception->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Sale could not be saved. Please try again.');
        }
    }

    public function index()
    {
        $rows = db_connect()->table('sales s')
            ->select('s.id, p.name AS product, c.full_name AS customer, u.full_name AS staff, s.quantity, s.total_price, s.created_at')
            ->join('products p', 'p.id = s.product_id')->join('customers c', 'c.id = s.customer_id', 'left')
            ->join('users u', 'u.id = s.sold_by')->orderBy('s.created_at', 'DESC')->get()->getResultArray();
        return view('sales/index', ['sales' => $rows]);
    }
}
