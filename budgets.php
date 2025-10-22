<?php
require __DIR__ . '/includes/init.php';
require_login();

$pageTitle = 'Anggaran';
$pdo = getPDO();
$userId = (int)current_user()['id'];

$expenseCategories = get_user_categories($pdo, $userId, 'expense');
$budgets = get_budget_progress($pdo, $userId);
$editBudget = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!validate_csrf($token)) {
        add_flash('danger', 'Sesi tidak valid, silakan coba lagi.');
        redirect('budgets.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $budgetId = (int)($_POST['id'] ?? 0);
        $found = null;
        foreach ($budgets as $budget) {
            if ((int)$budget['id'] === $budgetId) {
                $found = $budget;
                break;
            }
        }
        if ($found) {
            $stmt = $pdo->prepare('DELETE FROM budgets WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $budgetId, 'user_id' => $userId]);
            add_flash('success', 'Anggaran dihapus.');
        } else {
            add_flash('danger', 'Anggaran tidak ditemukan.');
        }
        redirect('budgets.php');
    }

    $categoryId = (int)($_POST['category_id'] ?? 0);
    $monthlyLimit = (float)($_POST['monthly_limit'] ?? 0);

    $category = $categoryId ? find_category($pdo, $userId, $categoryId) : null;
    if (!$category || $category['type'] !== 'expense') {
        add_flash('danger', 'Pilih kategori pengeluaran yang valid.');
        redirect('budgets.php');
    }

    if ($monthlyLimit <= 0) {
        add_flash('danger', 'Batas anggaran harus lebih besar dari nol.');
        redirect('budgets.php');
    }

    if ($action === 'create') {
        $stmt = $pdo->prepare('SELECT id FROM budgets WHERE user_id = :user_id AND category_id = :category_id');
        $stmt->execute(['user_id' => $userId, 'category_id' => $categoryId]);
        if ($stmt->fetch()) {
            add_flash('danger', 'Kategori ini sudah memiliki anggaran.');
            redirect('budgets.php');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO budgets (user_id, category_id, monthly_limit) VALUES (:user_id, :category_id, :limit)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'limit' => $monthlyLimit,
        ]);
        add_flash('success', 'Anggaran baru berhasil dibuat.');
        redirect('budgets.php');
    }

    if ($action === 'update') {
        $budgetId = (int)($_POST['id'] ?? 0);
        $existingStmt = $pdo->prepare('SELECT * FROM budgets WHERE id = :id AND user_id = :user_id');
        $existingStmt->execute(['id' => $budgetId, 'user_id' => $userId]);
        $existing = $existingStmt->fetch();

        if (!$existing) {
            add_flash('danger', 'Anggaran tidak ditemukan.');
            redirect('budgets.php');
        }

        $stmt = $pdo->prepare(
            'UPDATE budgets SET category_id = :category_id, monthly_limit = :limit WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id' => $budgetId,
            'user_id' => $userId,
            'category_id' => $categoryId,
            'limit' => $monthlyLimit,
        ]);
        add_flash('success', 'Anggaran diperbarui.');
        redirect('budgets.php');
    }
}

if (isset($_GET['id'])) {
    $budgetId = (int)$_GET['id'];
    $stmt = $pdo->prepare(
        'SELECT b.*, c.name AS category_name FROM budgets b
         JOIN categories c ON c.id = b.category_id
         WHERE b.id = :id AND b.user_id = :user_id'
    );
    $stmt->execute(['id' => $budgetId, 'user_id' => $userId]);
    $editBudget = $stmt->fetch();
    if (!$editBudget) {
        add_flash('danger', 'Anggaran tidak ditemukan.');
        redirect('budgets.php');
    }
}

$budgets = get_budget_progress($pdo, $userId);

include __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><?php echo $editBudget ? 'Ubah Anggaran' : 'Tambah Anggaran'; ?></h5>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="action" value="<?php echo $editBudget ? 'update' : 'create'; ?>">
                    <?php if ($editBudget): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$editBudget['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Kategori Pengeluaran</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach ($expenseCategories as $category): ?>
                                <option value="<?php echo (int)$category['id']; ?>"
                                    <?php echo (($editBudget['category_id'] ?? null) == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="monthly_limit" class="form-label">Batas Bulanan (Rp)</label>
                        <input type="number" min="0" step="0.01" name="monthly_limit" id="monthly_limit"
                               class="form-control" value="<?php echo e((string)($editBudget['monthly_limit'] ?? '0')); ?>" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editBudget ? 'Simpan Perubahan' : 'Tambah Anggaran'; ?>
                        </button>
                    </div>
                    <?php if ($editBudget): ?>
                        <div class="d-grid mt-2">
                            <a class="btn btn-outline-secondary" href="budgets.php">Batal</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Daftar Anggaran</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Batas Bulanan</th>
                            <th>Terpakai</th>
                            <th>Persentase</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($budgets): ?>
                            <?php foreach ($budgets as $budget): ?>
                                <?php
                                $limit = (float)$budget['monthly_limit'];
                                $spent = (float)$budget['spent'];
                                $percentage = $limit > 0 ? min(100, round(($spent / $limit) * 100)) : 0;
                                $barClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($budget['category_name']); ?></td>
                                    <td><?php echo e(format_currency($limit)); ?></td>
                                    <td><?php echo e(format_currency($spent)); ?></td>
                                    <td style="min-width: 180px;">
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar <?php echo $barClass; ?>" role="progressbar"
                                                 style="width: <?php echo $percentage; ?>%"
                                                 aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="text-muted small mt-1"><?php echo $percentage; ?>%</div>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="budgets.php?id=<?php echo (int)$budget['id']; ?>">Edit</a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus anggaran ini?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$budget['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada anggaran tersimpan.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include __DIR__ . '/includes/footer.php';
