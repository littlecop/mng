<?php
require __DIR__ . '/includes/init.php';

$pageTitle = 'Masuk';
$hideSidebar = true;

if (is_logged_in()) {
    redirect('dashboard.php');
}

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? null;

    if (!validate_csrf($token)) {
        add_flash('danger', 'Sesi tidak valid, silakan coba lagi.');
        redirect('index.php');
    }

    if ($email === '' || $password === '') {
        $errors[] = 'Email dan kata sandi wajib diisi.';
    }

    if (!$errors) {
        if (attempt_login($email, $password)) {
            add_flash('success', 'Selamat datang kembali!');
            redirect('dashboard.php');
        }

        $errors[] = 'Email atau kata sandi tidak cocok.';
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
    <span class="badge-soft mb-3">MNG Finance Admin</span>
    <h1>Masuk Admin Panel</h1>
    <p class="text-muted mb-4">Kelola arus kas, kategori, dan anggaran secara terpusat.</p>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control form-control-lg"
                   placeholder="admin@example.com" value="<?php echo e($email); ?>" required>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Kata Sandi</label>
            <input type="password" name="password" id="password" class="form-control form-control-lg"
                   placeholder="Masukkan kata sandi" required>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Masuk</button>
        </div>
    </form>
    <p class="mt-4 mb-0 text-muted small">
        Gunakan akun default <strong>admin@example.com</strong> · <strong>admin123</strong>.
        Segera ganti melalui database atau modul pengguna Anda sendiri.
    </p>
</div>
<?php
include __DIR__ . '/includes/footer.php';
