<?php
declare(strict_types=1);

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function attempt_login(string $email, string $password): bool
{
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'logged_in_at' => date('Y-m-d H:i:s'),
        ];

        if (function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
        }

        return true;
    }

    return false;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
