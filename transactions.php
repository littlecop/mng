<?php
require __DIR__ . '/includes/init.php';
require_login();

$pageTitle = 'Transaksi';
$pdo = getPDO();
$userId = (int)current_user()['id'];

$accounts = get_user_accounts($pdo, $userId);
$incomeCategories = get_user_categories($pdo, $userId, 'income');
$expenseCategories = get_user_categories($pdo, $userId, 'expense');
$editTransaction = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!validate_csrf($token)) {
        add_flash('danger', 'Sesi tidak valid, silakan coba lagi.');
        redirect('transactions.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $transactionId = (int)($_POST['id'] ?? 0);
        $transaction = find_transaction($pdo, $userId, $transactionId);
        if ($transaction) {
            $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $transactionId, 'user_id' => $userId]);
            add_flash('success', 'Transaksi dihapus.');
        } else {
            add_flash('danger', 'Transaksi tidak ditemukan.');
        }
        redirect('transactions.php');
    }

    $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
    $type = $_POST['type'] ?? 'expense';
    $accountId = (int)($_POST['account_id'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    $account = $accountId ? find_account($pdo, $userId, $accountId) : null;
    $category = $categoryId ? find_category($pdo, $userId, $categoryId) : null;

    if (!$account) {
        add_flash('danger', 'Akun tidak valid.');
        redirect('transactions.php');
    }

    if (!$category || $category['type'] !== $type) {
        add_flash('danger', 'Kategori tidak sesuai dengan jenis transaksi.');
        redirect('transactions.php');
    }

    if ($amount <= 0) {
        add_flash('danger', 'Jumlah harus lebih besar dari nol.');
        redirect('transactions.php');
    }

    if ($action === 'create') {
        $stmt = $pdo->prepare(
            'INSERT INTO transactions (user_id, account_id, category_id, transaction_date, amount, type, description)
             VALUES (:user_id, :account_id, :category_id, :transaction_date, :amount, :type, :description)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'transaction_date' => $transactionDate,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
        ]);
        add_flash('success', 'Transaksi berhasil ditambahkan.');
        redirect('transactions.php');
    }

    if ($action === 'update') {
        $transactionId = (int)($_POST['id'] ?? 0);
        $existing = find_transaction($pdo, $userId, $transactionId);
        if (!$existing) {
            add_flash('danger', 'Transaksi tidak ditemukan.');
            redirect('transactions.php');
        }

        $stmt = $pdo->prepare(
            'UPDATE transactions
             SET account_id = :account_id,
                 category_id = :category_id,
                 transaction_date = :transaction_date,
                 amount = :amount,
                 type = :type,
                 description = :description
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'id' => $transactionId,
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'transaction_date' => $transactionDate,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
        ]);
        add_flash('success', 'Transaksi diperbarui.');
        redirect('transactions.php');
    }
}

if (isset($_GET['id'])) {
    $transactionId = (int)$_GET['id'];
    $editTransaction = find_transaction($pdo, $userId, $transactionId);
    if (!$editTransaction) {
        add_flash('danger', 'Transaksi tidak ditemukan.');
        redirect('transactions.php');
    }
}

$transactions = get_transactions($pdo, $userId);

include __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><?php echo $editTransaction ? 'Ubah Transaksi' : 'Catat Transaksi'; ?></h5>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="action" value="<?php echo $editTransaction ? 'update' : 'create'; ?>">
                    <?php if ($editTransaction): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$editTransaction['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">Tanggal</label>
                        <input type="date" name="transaction_date" id="transaction_date" class="form-control"
                               value="<?php echo e($editTransaction['transaction_date'] ?? date('Y-m-d')); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Jenis</label>
                        <select name="type" id="type" class="form-select">
                            <option value="income" <?php echo (($editTransaction['type'] ?? '') === 'income') ? 'selected' : ''; ?>>Pemasukan</option>
                            <option value="expense" <?php echo (($editTransaction['type'] ?? 'expense') === 'expense') ? 'selected' : ''; ?>>Pengeluaran</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="account_id" class="form-label">Akun</label>
                        <select name="account_id" id="account_id" class="form-select" required>
                            <option value="">Pilih akun</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo (int)$account['id']; ?>"
                                    <?php echo (($editTransaction['account_id'] ?? null) == $account['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($account['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">Pilih kategori</option>
                            <?php if ($incomeCategories): ?>
                                <optgroup label="Pemasukan">
                                    <?php foreach ($incomeCategories as $category): ?>
                                        <option value="<?php echo (int)$category['id']; ?>"
                                            data-type="income"
                                            <?php echo (($editTransaction['category_id'] ?? null) == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo e($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <?php if ($expenseCategories): ?>
                                <optgroup label="Pengeluaran">
                                    <?php foreach ($expenseCategories as $category): ?>
                                        <option value="<?php echo (int)$category['id']; ?>"
                                            data-type="expense"
                                            <?php echo (($editTransaction['category_id'] ?? null) == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo e($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Jumlah</label>
                        <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control"
                               value="<?php echo e((string)($editTransaction['amount'] ?? '0')); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea name="description" id="description" class="form-control" rows="3"
                                  placeholder="Opsional"><?php echo e($editTransaction['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editTransaction ? 'Simpan Perubahan' : 'Simpan Transaksi'; ?>
                        </button>
                    </div>
                    <?php if ($editTransaction): ?>
                        <div class="d-grid mt-2">
                            <a class="btn btn-outline-secondary" href="transactions.php">Batal</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Riwayat Transaksi</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Kategori</th>
                            <th>Akun</th>
                            <th class="text-end">Jumlah</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($transactions): ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo e(format_datetime($transaction['transaction_date'])); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($transaction['description'] ?: 'Transaksi'); ?></div>
                                    </td>
                                    <td><?php echo e($transaction['category_name']); ?></td>
                                    <td><?php echo e($transaction['account_name']); ?></td>
                                    <td class="text-end <?php echo $transaction['type'] === 'income' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo e(format_currency((float)$transaction['amount'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="transactions.php?id=<?php echo (int)$transaction['id']; ?>">Edit</a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus transaksi ini?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$transaction['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada transaksi tercatat.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.transactionFormState = {
        currentType: '<?php echo e($editTransaction['type'] ?? 'expense'); ?>'
    };
</script>
<?php
include __DIR__ . '/includes/footer.php';
