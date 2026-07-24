<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Flash;
use App\Helpers\Validation;
use App\Models\Category;

final class CategoryController extends Controller
{
    public function index(): void
    {
        \App\Helpers\auth_require_login();
        $catMdl = new Category($this->db);

        $byType = [
            'income'  => $catMdl->findByType('income'),
            'expense' => $catMdl->findByType('expense'),
        ];

        $this->render('categories/index', [
            'title'    => 'Kategori — Keuangan',
            'byType'   => $byType,
            'csrf'     => \App\Helpers\csrf_token(),
        ]);
    }

    public function create(): void
    {
        \App\Helpers\auth_require_login();
        $supplied = (string)$this->request->postInput('_csrf', '');
        if (!\App\Helpers\csrf_check($supplied)) {
            Flash::error('Sesi tidak valid.');
            $this->redirect('/categories');
            return;
        }
        $type = (string)$this->request->postInput('type', '');
        $name = trim((string)$this->request->postInput('name', ''));
        if (!in_array($type, ['income','expense'], true)) {
            Flash::error('Tipe tidak valid.');
            $this->redirect('/categories');
            return;
        }
        $name = Validation::text($name, 64);
        if ($name === '') {
            Flash::error('Nama kategori wajib diisi (1-64 karakter).');
            $this->redirect('/categories');
            return;
        }
        try {
            $catMdl = new Category($this->db);
            $catMdl->create($type, $name);
            Flash::success('Kategori ditambahkan.');
        } catch (\PDOException $e) {
            // Duplicate name (UNIQUE).
            Flash::error('Kategori sudah ada.');
        }
        $this->redirect('/categories');
    }

    public function delete(): void
    {
        \App\Helpers\auth_require_login();
        $supplied = (string)$this->request->postInput('_csrf', '');
        if (!\App\Helpers\csrf_check($supplied)) {
            Flash::error('Sesi tidak valid.');
            $this->redirect('/categories');
            return;
        }
        $id     = (int)$this->request->postInput('id', 0);
        $catMdl = new Category($this->db);
        $ok     = $catMdl->safeDelete($id);
        if (!$ok) {
            Flash::error('Kategori tidak dapat dihapus (mungkin bawaan atau masih dipakai).');
        } else {
            Flash::success('Kategori dihapus.');
        }
        $this->redirect('/categories');
    }
}
