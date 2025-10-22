<?php
require __DIR__ . '/includes/init.php';
require_login();

$pageTitle = 'Dashboard';
$pdo = getPDO();
$userId = (int)current_user()['id'];

$cards = get_overview_cards($pdo, $userId);
$recentTransactions = get_recent_transactions($pdo, $userId, 6);
$trends = get_monthly_trends($pdo, $userId, 6);
$budgets = get_budget_progress($pdo, $userId);
$accounts = $cards['accounts'] ?? [];

$accountBreakdown = array_map(static function (array $account): array {
    $currentBalance = (float)$account['starting_balance'] + (float)$account['total_income'] - (float)$account['total_expense'];

    return [
        'name' => $account['name'],
        'balance' => $currentBalance,
    ];
}, $accounts);

$trendLabels = [];
$trendIncome = [];
$trendExpense = [];

foreach ($trends as $row) {
    $dateObj = DateTime::createFromFormat('Y-m', $row['period'] ?? '');
    $trendLabels[] = $dateObj ? $dateObj->format('M Y') : $row['period'];
    $trendIncome[] = $row['income'];
    $trendExpense[] = $row['expense'];
}

include __DIR__ . '/includes/header.php';
?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 dashboard-metrics mb-3">
    <div class="col">
        <div class="metric-card">
            <span class="metric-label">Total Saldo</span>
            <span class="metric-value"><?php echo e(format_currency($cards['totalBalance'] ?? 0)); ?></span>
            <span class="metric-sub">Akumulasi seluruh akun aktif.</span>
        </div>
    </div>
    <div class="col">
        <div class="metric-card">
            <span class="metric-label">Pemasukan Bulan Ini</span>
            <span class="metric-value text-success"><?php echo e(format_currency($cards['monthlyIncome'] ?? 0)); ?></span>
            <span class="metric-sub">Periode berjalan.</span>
        </div>
    </div>
    <div class="col">
        <div class="metric-card">
            <span class="metric-label">Pengeluaran Bulan Ini</span>
            <span class="metric-value text-danger"><?php echo e(format_currency($cards['monthlyExpense'] ?? 0)); ?></span>
            <span class="metric-sub">
                Arus kas bersih
                <span class="<?php echo ($cards['netCashflow'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                    <?php echo e(format_currency($cards['netCashflow'] ?? 0)); ?>
                </span>
            </span>
        </div>
    </div>
</div>

<div class="row g-3 dashboard-section">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Tren Pemasukan vs Pengeluaran</h5>
                    <span class="badge-soft">6 bulan terakhir</span>
                </div>
                <canvas id="cashflowChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Ringkasan Akun</h5>
                <ul class="list-group list-group-flush">
                    <?php if ($accountBreakdown): ?>
                        <?php foreach ($accountBreakdown as $account): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="fw-semibold"><?php echo e($account['name']); ?></span>
                                <span><?php echo e(format_currency((float)$account['balance'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item px-0 text-muted">Belum ada akun keuangan.</li>
                    <?php endif; ?>
                </ul>
                <a href="accounts.php" class="btn btn-primary w-100 mt-3">Kelola Akun</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 dashboard-section mt-2">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Transaksi Terbaru</h5>
                    <a href="transactions.php" class="text-decoration-none small">Lihat semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Kategori</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentTransactions): ?>
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <tr>
                                    <td><?php echo e(format_datetime($transaction['transaction_date'])); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($transaction['description'] ?: 'Transaksi'); ?></div>
                                        <div class="text-muted small"><?php echo e($transaction['account_name']); ?></div>
                                    </td>
                                    <td><?php echo e($transaction['category_name']); ?></td>
                                    <td class="text-end <?php echo $transaction['type'] === 'income' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo e(format_currency((float)$transaction['amount'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada transaksi.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Monitoring Anggaran</h5>
                    <a href="budgets.php" class="text-decoration-none small">Atur anggaran</a>
                </div>
                <?php if ($budgets): ?>
                    <?php foreach ($budgets as $budget): ?>
                        <?php
                        $limit = (float)$budget['monthly_limit'];
                        $spent = (float)$budget['spent'];
                        $percentage = $limit > 0 ? min(100, round(($spent / $limit) * 100)) : 0;
                        $barClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold"><?php echo e($budget['category_name']); ?></span>
                                <span class="text-muted small"><?php echo e(format_currency($spent)); ?> / <?php echo e(format_currency($limit)); ?></span>
                            </div>
                            <div class="progress mt-2" style="height: 10px;">
                                <div class="progress-bar <?php echo $barClass; ?>" role="progressbar"
                                     style="width: <?php echo $percentage; ?>%;"
                                     aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted">Belum ada anggaran, tambahkan untuk mulai memantau pengeluaran.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    window.dashboardTrends = {
        labels: <?php echo json_encode($trendLabels); ?>,
        income: <?php echo json_encode($trendIncome); ?>,
        expense: <?php echo json_encode($trendExpense); ?>
    };
</script>
<?php
include __DIR__ . '/includes/footer.php';
