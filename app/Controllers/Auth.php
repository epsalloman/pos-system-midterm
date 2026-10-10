<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('user_id')) return redirect()->to('/dashboard');
        return view('auth/login');
    }

    public function attempt()
    {
        $rules = ['username' => 'required|max_length[50]', 'password' => 'required|max_length[255]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());

        $user = (new UserModel())->where('username', strtolower(trim((string) $this->request->getPost('username'))))
            ->where('is_active', 1)->first();
        if (! $user || ! password_verify((string) $this->request->getPost('password'), $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Username or password is incorrect.');
        }

        session()->regenerate(true);
        session()->set(['user_id' => $user['id'], 'username' => $user['username'], 'full_name' => $user['full_name']]);
        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'You have signed out.');
    }
}
