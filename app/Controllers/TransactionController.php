<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Flash;
use App\Helpers\Validation;
use App\Models\Category;
use App\Models\Transaction;

final class TransactionController extends Controller
{
    public function index(): void
    {
        \App\Helpers\auth_require_login();
        $userId = (int)$_SESSION['user_id'];

        $filters = [
            'from'           => Validation::date((string)$this->request->getInput('from', '')) ?: '',
            'to'             => Validation::date((string)$this->request->getInput('to', '')) ?: '',
            'type'           => (string)$this->request->getInput('type', ''),
            'category_id'    => (string)$this->request->getInput('category_id', ''),
            'payment_method' => (string)$this->request->getInput('payment_method', ''),
        ];
        if (!in_array($filters['type'], ['income','expense',''], true)) {
            $filters['type'] = '';
        }
        if (!in_array($filters['payment_method'], ['cash','transfer',''], true)) {
            $filters['payment_method'] = '';
        }

        $txMdl  = new Transaction($this->db);
        $catMdl = new Category($this->db);

        $list  = $txMdl->listFiltered($userId, $filters);
        $total = $txMdl->summaryFiltered($userId, $filters);
        $categories = $catMdl->all();

        $this->render('transactions/index', [
            'title'      => 'Transaksi — Keuangan',
            'rows'       => $list,
            'categories' => $categories,
            'filters'    => $filters,
            'total'      => $total,
            'csrf'       => \App\Helpers\csrf_token(),
        ]);
    }

    /**
     * Render the add/edit form. $id set => edit; null => create.
     */
    public function form(): void
    {
        \App\Helpers\auth_require_login();
        $userId = (int)$_SESSION['user_id'];
        $id     = (int)$this->request->getInput('id', 0);

        $txMdl  = new Transaction($this->db);
        $catMdl = new Category($this->db);
        $tx     = null;
        if ($id > 0) {
            $tx = $txMdl->find($id, $userId);
            if (!$tx) {
                Flash::error('Transaksi tidak ditemukan.');
                $this->redirect('/transactions');
                return;
            }
        }

        $categories = $catMdl->all();

        $this->render('transactions/form', [
            'title'      => ($id ? 'Edit' : 'Tambah') . ' Transaksi — Keuangan',
            'tx'         => $tx,
            'categories' => $categories,
            'csrf'       => \App\Helpers\csrf_token(),
        ]);
    }

    public function create(): void
    {
        \App\Helpers\auth_require_login();
        if (!$this->checkCsrf()) {
            return;
        }
        $userId = (int)$_SESSION['user_id'];
        $error  = $this->validateFromPost();
        if ($error !== null) {
            Flash::error($error);
            $this->redirect('/transactions/new');
            return;
        }
        [$categoryId, $type, $amount, $description, $date, $paymentMethod] = $this->extractFields();

        // Category is free to use for any transaction type now.

        $txMdl = new Transaction($this->db);
        $txMdl->create($userId, $categoryId, $type, $amount, $description, $date, $paymentMethod);
        Flash::success('Transaksi ditambahkan.');
        $this->redirect('/transactions');
    }

    public function update(): void
    {
        \App\Helpers\auth_require_login();
        if (!$this->checkCsrf()) {
            return;
        }
        $userId = (int)$_SESSION['user_id'];
        $id     = (int)$this->request->postInput('id', 0);
        if ($id <= 0) {
            Flash::error('ID tidak valid.');
            $this->redirect('/transactions');
            return;
        }
        $error = $this->validateFromPost();
        if ($error !== null) {
            Flash::error($error);
            $this->redirect('/transactions/edit?id=' . $id);
            return;
        }
        [$categoryId, $type, $amount, $description, $date, $paymentMethod] = $this->extractFields();

        // Category is free to use for any transaction type now.

        $txMdl = new Transaction($this->db);
        $ok    = $txMdl->update($id, $userId, $categoryId, $type, $amount, $description, $date, $paymentMethod);
        Flash::success($ok ? 'Transaksi diperbarui.' : 'Tidak ada perubahan.');
        $this->redirect('/transactions');
    }

    public function delete(): void
    {
        \App\Helpers\auth_require_login();
        if (!$this->checkCsrf()) {
            return;
        }
        $userId = (int)$_SESSION['user_id'];
        $id     = (int)$this->request->postInput('id', 0);
        if ($id <= 0) {
            $this->redirect('/transactions');
            return;
        }
        $txMdl = new Transaction($this->db);
        $ok    = $txMdl->delete($id, $userId);
        Flash::success($ok ? 'Transaksi dihapus.' : 'Transaksi tidak ditemukan.');
        $this->redirect('/transactions');
    }

    private function checkCsrf(): bool
    {
        $supplied = (string)$this->request->postInput('_csrf', '');
        if (!\App\Helpers\csrf_check($supplied)) {
            Flash::error('Sesi tidak valid, silakan muat ulang halaman.');
            $this->redirect('/transactions');
            return false;
        }
        return true;
    }

    /**
     * Returns null on success, or an error string.
     */
    private function validateFromPost(): ?string
    {
        $type = (string)$this->request->postInput('type', '');
        if (!in_array($type, ['income','expense'], true)) {
            return 'Tipe harus income atau expense.';
        }
        $amountRaw = (string)$this->request->postInput('amount', '');
        $amount    = Validation::amount($amountRaw);
        if ($amount === null) {
            return 'Nominal tidak valid.';
        }
        $date = Validation::date((string)$this->request->postInput('tx_date', ''));
        if ($date === null) {
            return 'Tanggal tidak valid (YYYY-MM-DD).';
        }
        $pm = (string)$this->request->postInput('payment_method', '');
        if (!in_array($pm, ['cash', 'transfer'], true)) {
            return 'Sumber pembayaran tidak valid.';
        }
        return null;
    }

    /**
     * Pull validated fields from POST. Caller must run validateFromPost() first.
     * @return array{int, string, string, ?string, string}
     */
    private function extractFields(): array
    {
        $type    = (string)$this->request->postInput('type', '');
        $catId   = (int)$this->request->postInput('category_id', 0);
        $amount  = Validation::amount((string)$this->request->postInput('amount', '')) ?? '0';
        $descRaw = (string)$this->request->postInput('description', '');
        $desc    = trim($descRaw) === '' ? null : Validation::text($descRaw, 255);
        $date    = (string)$this->request->postInput('tx_date', '');
        $pm      = (string)$this->request->postInput('payment_method', 'cash');
        if (!in_array($pm, ['cash', 'transfer'], true)) {
            $pm = 'cash';
        }
        return [$catId, $type, $amount, $desc, $date, $pm];
    }
}
