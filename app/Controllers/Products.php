<?php

namespace App\Controllers;

use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Products extends BaseController
{
    private ProductModel $products;

    public function __construct() { $this->products = new ProductModel(); }

    public function index()
    {
        helper(['form', 'url']);
        return view('products/index', ['products' => $this->products->where('is_active', 1)->orderBy('name')->findAll()]);
    }

    public function new() { helper(['form', 'url']); return view('products/form', ['product' => []]); }

    public function create() { return $this->saveProduct(); }

    public function edit(int $id)
    {
        helper(['form', 'url']);
        $product = $this->products->find($id);
        if (! $product || ! $product['is_active']) throw PageNotFoundException::forPageNotFound('Product not found.');
        return view('products/form', ['product' => $product]);
    }

    public function update(int $id) { return $this->saveProduct($id); }

    private function saveProduct(?int $id = null)
    {
        $rules = [
            'name' => 'required|min_length[2]|max_length[100]',
            'price' => 'required|decimal|greater_than_equal_to[0]',
            'stock_quantity' => 'required|integer|greater_than_equal_to[0]',
        ];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $data = ['name' => trim((string) $this->request->getPost('name')), 'price' => $this->request->getPost('price'), 'stock_quantity' => (int) $this->request->getPost('stock_quantity')];
        $file = $this->request->getFile('image');
        if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $imageRules = ['image' => 'uploaded[image]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png,image/webp]|ext_in[image,jpg,jpeg,png,webp]|max_size[image,3072]'];
            if (! $this->validate($imageRules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            $filename = $this->prepareProductImage($file);
            if (! $filename) return redirect()->back()->withInput()->with('error', 'Image could not be prepared. Confirm PHP GD is enabled.');
            $data['image'] = $filename;
        }
        if ($id) {
            $old = $this->products->find($id);
            $this->products->update($id, $data);
            if (isset($data['image']) && $old && $old['image']) $this->removeImage($old['image']);
        } else {
            $this->products->insert($data);
        }
        return redirect()->to('/products')->with('success', 'Product saved successfully.');
    }

    private function prepareProductImage(\CodeIgniter\HTTP\Files\UploadedFile $file): ?string
    {
        $directory = FCPATH . 'uploads/products';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) return null;
        $extension = $file->guessExtension();
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) return null;
        $base = bin2hex(random_bytes(16));
        $source = $directory . DIRECTORY_SEPARATOR . $base . '.' . $extension;
        $thumb = 'display_' . $base . '.' . $extension;
        try {
            $file->move($directory, $base . '.' . $extension);
            service('image')->withFile($source)->fit(640, 480, 'center')->save($directory . DIRECTORY_SEPARATOR . $thumb);
            unlink($source);
            return $thumb;
        } catch (\Throwable $exception) {
            if (is_file($source)) unlink($source);
            if (is_file($directory . DIRECTORY_SEPARATOR . $thumb)) unlink($directory . DIRECTORY_SEPARATOR . $thumb);
            log_message('error', 'Product image preparation failed: {message}', ['message' => $exception->getMessage()]);
            return null;
        }
    }

    private function removeImage(string $filename): void
    {
        $path = FCPATH . 'uploads/products/' . basename($filename);
        if (is_file($path)) unlink($path);
    }

    public function archive(int $id)
    {
        $product = $this->products->find($id);
        if (! $product) throw PageNotFoundException::forPageNotFound('Product not found.');
        $this->products->update($id, ['is_active' => 0]);
        return redirect()->to('/products')->with('success', 'Product archived. Past sales remain in history.');
    }
}
