<?php
declare(strict_types=1);

const RESET_SECRET = 'admin123';

require __DIR__ . '/config.php';

$pdo = getPDO();
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providedSecret = $_POST['secret'] ?? '';
    $email = trim($_POST['email'] ?? 'admin@example.com');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirm'] ?? '';

    if ($providedSecret === '' || !hash_equals(RESET_SECRET, $providedSecret)) {
        $errorMessage = 'Token rahasia tidak valid. Perbarui RESET_SECRET pada file sebelum digunakan.';
    } elseif ($email === '') {
        $errorMessage = 'Email tidak boleh kosong.';
    } elseif ($newPassword === '' || $confirmPassword === '') {
        $errorMessage = 'Kata sandi baru dan konfirmasi wajib diisi.';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = 'Konfirmasi kata sandi tidak cocok.';
    } elseif (strlen($newPassword) < 8) {
        $errorMessage = 'Kata sandi minimal 8 karakter.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errorMessage = 'Pengguna dengan email tersebut tidak ditemukan.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $update->execute([
                'hash' => $hash,
                'id' => $user['id'],
            ]);
            $successMessage = 'Kata sandi berhasil diperbarui. Hapus file reset_password.php setelah selesai.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body { background-color: #f3f4f6; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        .reset-card { max-width: 420px; margin: 5vh auto; padding: 1.75rem; border-radius: 1rem; background: #ffffff; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12); }
        .reset-card h1 { font-size: 1.4rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="reset-card">
    <h1>Reset Password Admin</h1>
    <p class="text-muted small">Ganti nilai <code>RESET_SECRET</code> di file ini sebelum digunakan, lalu hapus file setelah reset.</p>
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form method="post" class="vstack gap-3">
        <div>
            <label for="secret" class="form-label">Token Rahasia</label>
            <input type="text" name="secret" id="secret" class="form-control" required>
        </div>
        <div>
            <label for="email" class="form-label">Email Pengguna</label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? 'admin@example.com', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div>
            <label for="password" class="form-label">Kata Sandi Baru</label>
            <input type="password" name="password" id="password" class="form-control" required minlength="8">
        </div>
        <div>
            <label for="password_confirm" class="form-label">Konfirmasi Kata Sandi</label>
            <input type="password" name="password_confirm" id="password_confirm" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
    </form>
</div>
</body>
</html>
