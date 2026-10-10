<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = session()->get('user_id');
        if (! $userId) {
            return redirect()->to('/login')->with('error', 'Please sign in to access the POS.');
        }
        $activeUser = (new UserModel())->select('id')->where('id', $userId)->where('is_active', 1)->first();
        if (! $activeUser) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'This staff account is no longer active.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
