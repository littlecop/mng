<?php
require __DIR__ . '/includes/init.php';
require_login();

$pageTitle = 'Kategori';
$pdo = getPDO();
$userId = (int)current_user()['id'];

$editCategory = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!validate_csrf($token)) {
        add_flash('danger', 'Sesi tidak valid, silakan coba lagi.');
        redirect('categories.php');
    }

    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'expense';
    $color = $_POST['color'] ?? '#4a90e2';

    if ($action === 'create') {
        if ($name === '') {
            add_flash('danger', 'Nama kategori wajib diisi.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO categories (user_id, name, type, color) VALUES (:user_id, :name, :type, :color)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'name' => $name,
                'type' => $type,
                'color' => $color,
            ]);
            add_flash('success', 'Kategori baru ditambahkan.');
        }
        redirect('categories.php');
    }

    if ($action === 'update') {
        $categoryId = (int)($_POST['id'] ?? 0);
        $category = find_category($pdo, $userId, $categoryId);
        if (!$category) {
            add_flash('danger', 'Kategori tidak ditemukan.');
            redirect('categories.php');
        }

        if ($name === '') {
            add_flash('danger', 'Nama kategori wajib diisi.');
            redirect('categories.php?id=' . $categoryId);
        }

        $stmt = $pdo->prepare(
            'UPDATE categories
             SET name = :name, type = :type, color = :color
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id' => $categoryId,
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'color' => $color,
        ]);

        add_flash('success', 'Kategori diperbarui.');
        redirect('categories.php');
    }

    if ($action === 'delete') {
        $categoryId = (int)($_POST['id'] ?? 0);
        $category = find_category($pdo, $userId, $categoryId);
        if (!$category) {
            add_flash('danger', 'Kategori tidak ditemukan.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $categoryId, 'user_id' => $userId]);
            add_flash('success', 'Kategori dihapus.');
        }
        redirect('categories.php');
    }
}

if (isset($_GET['id'])) {
    $categoryId = (int)$_GET['id'];
    $editCategory = find_category($pdo, $userId, $categoryId);
    if (!$editCategory) {
        add_flash('danger', 'Kategori tidak ditemukan.');
        redirect('categories.php');
    }
}

$incomeCategories = get_user_categories($pdo, $userId, 'income');
$expenseCategories = get_user_categories($pdo, $userId, 'expense');

include __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><?php echo $editCategory ? 'Ubah Kategori' : 'Tambah Kategori'; ?></h5>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="action" value="<?php echo $editCategory ? 'update' : 'create'; ?>">
                    <?php if ($editCategory): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$editCategory['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Kategori</label>
                        <input type="text" name="name" id="name" class="form-control"
                               value="<?php echo e($editCategory['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Jenis</label>
                        <select name="type" id="type" class="form-select">
                            <option value="income" <?php echo (($editCategory['type'] ?? '') === 'income') ? 'selected' : ''; ?>>Pemasukan</option>
                            <option value="expense" <?php echo (($editCategory['type'] ?? 'expense') === 'expense') ? 'selected' : ''; ?>>Pengeluaran</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="color" class="form-label">Warna</label>
                        <input type="color" class="form-control form-control-color" name="color" id="color"
                               value="<?php echo e($editCategory['color'] ?? '#4a90e2'); ?>">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editCategory ? 'Simpan Perubahan' : 'Tambah Kategori'; ?>
                        </button>
                    </div>
                    <?php if ($editCategory): ?>
                        <div class="d-grid mt-2">
                            <a class="btn btn-outline-secondary" href="categories.php">Batal</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Kategori Pemasukan</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Warna</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($incomeCategories): ?>
                            <?php foreach ($incomeCategories as $category): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($category['name']); ?></td>
                                    <td>
                                        <span class="badge rounded-pill" style="background-color: <?php echo e($category['color']); ?>;">&nbsp;</span>
                                        <span class="text-muted small"><?php echo e($category['color']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="categories.php?id=<?php echo (int)$category['id']; ?>">Edit</a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus kategori ini?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$category['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Belum ada kategori pemasukan.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Kategori Pengeluaran</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Warna</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($expenseCategories): ?>
                            <?php foreach ($expenseCategories as $category): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($category['name']); ?></td>
                                    <td>
                                        <span class="badge rounded-pill" style="background-color: <?php echo e($category['color']); ?>;">&nbsp;</span>
                                        <span class="text-muted small"><?php echo e($category['color']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="categories.php?id=<?php echo (int)$category['id']; ?>">Edit</a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus kategori ini?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$category['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Belum ada kategori pengeluaran.</td>
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
