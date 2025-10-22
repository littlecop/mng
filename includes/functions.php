<?php
declare(strict_types=1);

/**
 * Escapes output for safe HTML rendering.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function add_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash_messages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function validate_csrf(?string $token): bool
{
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function format_currency(float $amount): string
{
    return 'Rp ' . number_format($amount, 2, ',', '.');
}

function format_datetime(string $date): string
{
    return date('d M Y', strtotime($date));
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('index.php');
    }
}

function get_user_accounts(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE user_id = :user_id ORDER BY name');
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll();
}

function get_user_categories(PDO $pdo, int $userId, ?string $type = null): array
{
    if ($type) {
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE user_id = :user_id AND type = :type ORDER BY name');
        $stmt->execute(['user_id' => $userId, 'type' => $type]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE user_id = :user_id ORDER BY type, name');
        $stmt->execute(['user_id' => $userId]);
    }

    return $stmt->fetchAll();
}

function get_recent_transactions(PDO $pdo, int $userId, int $limit = 6): array
{
    $stmt = $pdo->prepare(
        'SELECT t.*, a.name AS account_name, c.name AS category_name
        FROM transactions t
        JOIN accounts a ON a.id = t.account_id
        JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = :user_id
        ORDER BY t.transaction_date DESC, t.created_at DESC
        LIMIT :limit'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function get_overview_cards(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT a.id, a.name, a.starting_balance,
            COALESCE(SUM(CASE WHEN t.type = "income" THEN t.amount ELSE 0 END), 0) AS total_income,
            COALESCE(SUM(CASE WHEN t.type = "expense" THEN t.amount ELSE 0 END), 0) AS total_expense
         FROM accounts a
         LEFT JOIN transactions t ON t.account_id = a.id AND t.user_id = :user_id
         WHERE a.user_id = :user_id
         GROUP BY a.id, a.name, a.starting_balance'
    );
    $stmt->execute(['user_id' => $userId]);
    $accounts = $stmt->fetchAll();

    $totalBalance = 0.0;
    foreach ($accounts as $account) {
        $totalBalance += $account['starting_balance'] + $account['total_income'] - $account['total_expense'];
    }

    $stmt = $pdo->prepare(
        'SELECT
            COALESCE(SUM(CASE WHEN type = "income" THEN amount ELSE 0 END), 0) AS monthly_income,
            COALESCE(SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END), 0) AS monthly_expense
         FROM transactions
         WHERE user_id = :user_id
           AND YEAR(transaction_date) = YEAR(CURDATE())
           AND MONTH(transaction_date) = MONTH(CURDATE())'
    );
    $stmt->execute(['user_id' => $userId]);
    $monthly = $stmt->fetch() ?: ['monthly_income' => 0, 'monthly_expense' => 0];

    return [
        'totalBalance' => $totalBalance,
        'monthlyIncome' => (float)$monthly['monthly_income'],
        'monthlyExpense' => (float)$monthly['monthly_expense'],
        'netCashflow' => (float)$monthly['monthly_income'] - (float)$monthly['monthly_expense'],
        'accounts' => $accounts,
    ];
}

function get_monthly_trends(PDO $pdo, int $userId, int $months = 6): array
{
    $stmt = $pdo->prepare(
        'SELECT DATE_FORMAT(transaction_date, "%Y-%m") AS period,
            SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) AS income,
            SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) AS expense
         FROM transactions
         WHERE user_id = :user_id
           AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
         GROUP BY period
         ORDER BY period'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':months', $months, PDO::PARAM_INT);
    $stmt->execute();
    $raw = $stmt->fetchAll();

    $data = [];
    foreach ($raw as $row) {
        $data[] = [
            'period' => $row['period'],
            'income' => (float)$row['income'],
            'expense' => (float)$row['expense'],
        ];
    }

    return $data;
}

function get_budget_progress(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT
            b.id,
            c.name AS category_name,
            b.monthly_limit,
            COALESCE(SUM(CASE WHEN t.type = "expense" THEN t.amount ELSE 0 END), 0) AS spent
         FROM budgets b
         JOIN categories c ON c.id = b.category_id
         LEFT JOIN transactions t ON t.category_id = b.category_id
             AND t.user_id = b.user_id
             AND YEAR(t.transaction_date) = YEAR(CURDATE())
             AND MONTH(t.transaction_date) = MONTH(CURDATE())
         WHERE b.user_id = :user_id
         GROUP BY b.id, category_name, b.monthly_limit'
    );
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll();
}

function get_transactions(PDO $pdo, int $userId, ?string $type = null): array
{
    $query = 'SELECT t.*, a.name AS account_name, c.name AS category_name
        FROM transactions t
        JOIN accounts a ON a.id = t.account_id
        JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = :user_id';

    $params = ['user_id' => $userId];

    if ($type) {
        $query .= ' AND t.type = :type';
        $params['type'] = $type;
    }

    $query .= ' ORDER BY t.transaction_date DESC, t.created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function find_account(PDO $pdo, int $userId, int $accountId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $accountId, 'user_id' => $userId]);
    $account = $stmt->fetch();

    return $account ?: null;
}

function find_category(PDO $pdo, int $userId, int $categoryId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $categoryId, 'user_id' => $userId]);
    $category = $stmt->fetch();

    return $category ?: null;
}

function find_transaction(PDO $pdo, int $userId, int $transactionId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $transactionId, 'user_id' => $userId]);
    $transaction = $stmt->fetch();

    return $transaction ?: null;
}
