<?php

namespace App\Controllers;

use App\Models\UserModel;

class Users extends BaseController
{
    public function index()
    {
        helper(['form', 'url']);

        $userModel = new UserModel();
        $users = $userModel->where('is_active', 1)->findAll();

        return view('users', ['users' => $users]);
    }

    public function new()
    {
        helper(['form', 'url']);

        return view('users_new');
    }

    public function create()
    {
        $data = $this->userDataFromRequest();
        $data['password'] = (string) $this->request->getPost('password');

        $rules = [
            'username'  => 'required|min_length[3]|max_length[50]|alpha_numeric|is_unique[users.username]',
            'full_name' => 'required|min_length[2]|max_length[100]',
            'password'  => 'required|min_length[10]|max_length[255]',
        ];

        if (! $this->validateData($data, $rules, $this->validationMessages())) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $avatar = $this->request->getFile('avatar');
        if ($avatar !== null && $avatar->getError() !== UPLOAD_ERR_NO_FILE) {
            $avatarRules = ['avatar' => 'uploaded[avatar]|is_image[avatar]|mime_in[avatar,image/jpg,image/jpeg,image/png]|ext_in[avatar,jpg,jpeg,png]|max_size[avatar,2048]'];
            if (! $this->validate($avatarRules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            $avatarFilename = $this->prepareAvatar($avatar);
            if ($avatarFilename === null) return redirect()->back()->withInput()->with('error', 'The image could not be prepared. Confirm that PHP GD is enabled.');
            $data['avatar'] = $avatarFilename;
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['is_active'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');

        $userModel = new UserModel();
        $userModel->insert($data);

        return redirect()->to('/users')
            ->with('success', 'User added successfully.');
    }

    public function edit(int $id)
    {
        helper(['form', 'url']);

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if ($user === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'User record not found.'
            );
        }

        return view('users_edit', ['user' => $user]);
    }

    public function update(int $id)
    {
        $userModel = new UserModel();
        $user = $userModel->find($id);

        if ($user === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'User record not found.'
            );
        }

        $data = $this->userDataFromRequest();

        $rules = [
            'username'  => "required|min_length[3]|max_length[50]|alpha_numeric|is_unique[users.username,id,{$id}]",
            'full_name' => 'required|min_length[2]|max_length[100]',
        ];
        $newPassword = (string) $this->request->getPost('password');
        if ($newPassword !== '') {
            $data['password'] = $newPassword;
            $rules['password'] = 'min_length[10]|max_length[255]';
        }

        if (! $this->validateData($data, $rules, $this->validationMessages())) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $avatar = $this->request->getFile('avatar');
        $hasAvatarUpload = $avatar !== null && $avatar->getError() !== UPLOAD_ERR_NO_FILE;

        if ($hasAvatarUpload) {
            $avatarRules = [
                'avatar' => [
                    'label' => 'Profile picture',
                    'rules' => [
                        'uploaded[avatar]',
                        'is_image[avatar]',
                        'mime_in[avatar,image/jpg,image/jpeg,image/png]',
                        'ext_in[avatar,jpg,jpeg,png]',
                        'max_size[avatar,2048]',
                    ],
                ],
            ];

            if (! $this->validateData([], $avatarRules)) {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', $this->validator->getErrors());
            }

            $avatarFilename = $this->prepareAvatar($avatar);

            if ($avatarFilename === null) {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', [
                        'avatar' => 'The image could not be prepared. Confirm that the PHP GD extension is enabled.',
                    ]);
            }

            $data['avatar'] = $avatarFilename;
        }

        if ($newPassword !== '') $data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);

        $userModel->update($id, $data);

        if (isset($data['avatar']) && ! empty($user['avatar'])) {
            $oldAvatar = FCPATH . 'uploads/avatars/' . basename((string) $user['avatar']);

            if (is_file($oldAvatar)) {
                unlink($oldAvatar);
            }
        }

        return redirect()->to('/users')
            ->with('success', 'User updated successfully.');
    }

    public function archive(int $id)
    {
        $userModel = new UserModel();
        if (! $userModel->find($id)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('User record not found.');
        if ((int) $id === (int) session()->get('user_id')) return redirect()->to('/users')->with('error', 'You cannot archive your own account while signed in.');
        $userModel->update($id, ['is_active' => 0]);
        return redirect()->to('/users')->with('success', 'Staff account archived. Past sales remain in history.');
    }

    public function delete(int $id)
    {
        $userModel = new UserModel();
        if (! $userModel->find($id)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('User record not found.');
        if ((int) $id === (int) session()->get('user_id')) return redirect()->to('/users')->with('error', 'You cannot delete your own signed-in account.');
        $hasSales = db_connect()->table('sales')->where('sold_by', $id)->countAllResults() > 0;
        if ($hasSales) {
            $userModel->update($id, ['is_active' => 0]);
            return redirect()->to('/users')->with('success', 'Staff member has sales history, so the account was archived to preserve those records.');
        }
        $userModel->delete($id);
        return redirect()->to('/users')->with('success', 'Staff account deleted.');
    }

    private function userDataFromRequest(): array
    {
        return [
            'username'  => strtolower(trim((string) $this->request->getPost('username'))),
            'full_name' => trim((string) $this->request->getPost('full_name')),
        ];
    }

    private function prepareAvatar(\CodeIgniter\HTTP\Files\UploadedFile $avatar): ?string
    {
        $uploadDirectory = FCPATH . 'uploads/avatars';

        if (! is_dir($uploadDirectory)
            && ! mkdir($uploadDirectory, 0775, true)
            && ! is_dir($uploadDirectory)) {
            return null;
        }

        $extension = $avatar->guessExtension();

        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return null;
        }

        $randomBase = bin2hex(random_bytes(16));
        $originalFilename = $randomBase . '.' . $extension;
        $preparedFilename = 'thumb_' . $randomBase . '.' . $extension;
        $originalPath = $uploadDirectory . DIRECTORY_SEPARATOR . $originalFilename;
        $preparedPath = $uploadDirectory . DIRECTORY_SEPARATOR . $preparedFilename;

        try {
            $avatar->move($uploadDirectory, $originalFilename);

            service('image')
                ->withFile($originalPath)
                ->fit(240, 240, 'center')
                ->save($preparedPath);

            if (is_file($originalPath)) {
                unlink($originalPath);
            }

            return $preparedFilename;
        } catch (\Throwable $exception) {
            if (is_file($originalPath)) {
                unlink($originalPath);
            }

            if (is_file($preparedPath)) {
                unlink($preparedPath);
            }

            log_message('error', 'Avatar preparation failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function validationMessages(): array
    {
        return [
            'username' => [
                'required'      => 'Username is required.',
                'min_length'    => 'Username must contain at least 3 characters.',
                'max_length'    => 'Username cannot exceed 50 characters.',
                'alpha_numeric' => 'Username may contain letters and numbers only.',
                'is_unique'     => 'That username is already in use.',
            ],
            'full_name' => [
                'required'   => 'Full name is required.',
                'min_length' => 'Full name must contain at least 2 characters.',
                'max_length' => 'Full name cannot exceed 100 characters.',
            ],
        ];
    }
}
