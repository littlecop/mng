<?php
require __DIR__ . '/includes/init.php';
require_login();

$pageTitle = 'Kelola Akun';
$pdo = getPDO();
$user = current_user();
$userId = (int)$user['id'];

$editAccount = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!validate_csrf($token)) {
        add_flash('danger', 'Sesi tidak valid, silakan ulangi.');
        redirect('accounts.php');
    }

    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'other';
    $startingBalance = (float)($_POST['starting_balance'] ?? 0);

    if ($action === 'create') {
        if ($name === '') {
            add_flash('danger', 'Nama akun wajib diisi.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO accounts (user_id, name, type, starting_balance) VALUES (:user_id, :name, :type, :balance)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'name' => $name,
                'type' => $type,
                'balance' => $startingBalance,
            ]);
            add_flash('success', 'Akun baru berhasil ditambahkan.');
        }
        redirect('accounts.php');
    }

    if ($action === 'update') {
        $accountId = (int)($_POST['id'] ?? 0);
        $account = find_account($pdo, $userId, $accountId);

        if (!$account) {
            add_flash('danger', 'Akun tidak ditemukan.');
            redirect('accounts.php');
        }

        if ($name === '') {
            add_flash('danger', 'Nama akun wajib diisi.');
            redirect('accounts.php?id=' . $accountId);
        }

        $stmt = $pdo->prepare(
            'UPDATE accounts SET name = :name, type = :type, starting_balance = :balance WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id' => $accountId,
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'balance' => $startingBalance,
        ]);

        add_flash('success', 'Data akun diperbarui.');
        redirect('accounts.php');
    }

    if ($action === 'delete') {
        $accountId = (int)($_POST['id'] ?? 0);
        $account = find_account($pdo, $userId, $accountId);
        if (!$account) {
            add_flash('danger', 'Akun tidak ditemukan.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM accounts WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $accountId, 'user_id' => $userId]);
            add_flash('success', 'Akun berhasil dihapus.');
        }
        redirect('accounts.php');
    }
}

if (isset($_GET['id'])) {
    $accountId = (int)$_GET['id'];
    $editAccount = find_account($pdo, $userId, $accountId);
    if (!$editAccount) {
        add_flash('danger', 'Akun tidak ditemukan.');
        redirect('accounts.php');
    }
}

$overview = get_overview_cards($pdo, $userId);
$accounts = $overview['accounts'] ?? [];

include __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><?php echo $editAccount ? 'Ubah Akun' : 'Tambah Akun'; ?></h5>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="action" value="<?php echo $editAccount ? 'update' : 'create'; ?>">
                    <?php if ($editAccount): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$editAccount['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Akun</label>
                        <input type="text" name="name" id="name" class="form-control"
                               value="<?php echo e($editAccount['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Jenis</label>
                        <select name="type" id="type" class="form-select">
                            <?php
                            $types = [
                                'cash' => 'Kas',
                                'bank' => 'Bank',
                                'credit' => 'Kartu Kredit',
                                'investment' => 'Investasi',
                                'other' => 'Lainnya',
                            ];
                            foreach ($types as $value => $label): ?>
                                <option value="<?php echo $value; ?>"
                                    <?php echo (($editAccount['type'] ?? '') === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="starting_balance" class="form-label">Saldo Awal</label>
                        <input type="number" step="0.01" name="starting_balance" id="starting_balance" class="form-control"
                               value="<?php echo e((string)($editAccount['starting_balance'] ?? '0')); ?>">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editAccount ? 'Simpan Perubahan' : 'Tambah Akun'; ?>
                        </button>
                    </div>
                    <?php if ($editAccount): ?>
                        <div class="d-grid mt-2">
                            <a class="btn btn-outline-secondary" href="accounts.php">Batal</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Daftar Akun</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Jenis</th>
                            <th>Saldo Awal</th>
                            <th>Saldo Saat Ini</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($accounts): ?>
                            <?php foreach ($accounts as $account): ?>
                                <?php
                                $currentBalance = (float)$account['starting_balance'] + (float)$account['total_income'] - (float)$account['total_expense'];
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($account['name']); ?></td>
                                    <td class="text-capitalize"><?php echo e($account['type']); ?></td>
                                    <td><?php echo e(format_currency((float)$account['starting_balance'])); ?></td>
                                    <td><?php echo e(format_currency($currentBalance)); ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="accounts.php?id=<?php echo (int)$account['id']; ?>">Edit</a>
                                        <form method="post" class="d-inline"
                                              onsubmit="return confirm('Hapus akun ini? Transaksi terkait juga akan terhapus.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$account['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada akun.</td>
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
