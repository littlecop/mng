<?php
require __DIR__ . '/includes/init.php';
require_login();

$pageTitle = 'Laporan';
$pdo = getPDO();
$userId = (int)current_user()['id'];

$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-t');

try {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
} catch (Exception $e) {
    add_flash('danger', 'Rentang tanggal tidak valid.');
    redirect('reports.php');
}

$summaryStmt = $pdo->prepare(
    'SELECT
        COALESCE(SUM(CASE WHEN type = "income" THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END), 0) AS total_expense
     FROM transactions
     WHERE user_id = :user_id
       AND transaction_date BETWEEN :start AND :end'
);
$summaryStmt->execute([
    'user_id' => $userId,
    'start' => $start->format('Y-m-d'),
    'end' => $end->format('Y-m-d'),
]);
$summary = $summaryStmt->fetch() ?: ['total_income' => 0, 'total_expense' => 0];
$net = (float)$summary['total_income'] - (float)$summary['total_expense'];

$expenseStmt = $pdo->prepare(
    'SELECT c.name, SUM(t.amount) AS total
     FROM transactions t
     JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = :user_id
       AND t.type = "expense"
       AND t.transaction_date BETWEEN :start AND :end
     GROUP BY c.id, c.name
     ORDER BY total DESC'
);
$expenseStmt->execute([
    'user_id' => $userId,
    'start' => $start->format('Y-m-d'),
    'end' => $end->format('Y-m-d'),
]);
$expenseBreakdown = $expenseStmt->fetchAll();

$incomeStmt = $pdo->prepare(
    'SELECT c.name, SUM(t.amount) AS total
     FROM transactions t
     JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = :user_id
       AND t.type = "income"
       AND t.transaction_date BETWEEN :start AND :end
     GROUP BY c.id, c.name
     ORDER BY total DESC'
);
$incomeStmt->execute([
    'user_id' => $userId,
    'start' => $start->format('Y-m-d'),
    'end' => $end->format('Y-m-d'),
]);
$incomeBreakdown = $incomeStmt->fetchAll();

$byMonthStmt = $pdo->prepare(
    'SELECT DATE_FORMAT(transaction_date, "%Y-%m") AS period,
            SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) AS income,
            SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) AS expense
     FROM transactions
     WHERE user_id = :user_id
       AND transaction_date BETWEEN :start AND :end
     GROUP BY period
     ORDER BY period'
);
$byMonthStmt->execute([
    'user_id' => $userId,
    'start' => $start->format('Y-m-d'),
    'end' => $end->format('Y-m-d'),
]);
$monthlyRows = $byMonthStmt->fetchAll();

$monthlyLabels = [];
$monthlyIncome = [];
$monthlyExpense = [];

foreach ($monthlyRows as $row) {
    $dateObj = DateTime::createFromFormat('Y-m', $row['period'] ?? '');
    $monthlyLabels[] = $dateObj ? $dateObj->format('M Y') : $row['period'];
    $monthlyIncome[] = (float)$row['income'];
    $monthlyExpense[] = (float)$row['expense'];
}

include __DIR__ . '/includes/header.php';
?>
<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="get">
            <div class="col-md-4">
                <label for="start" class="form-label">Mulai</label>
                <input type="date" name="start" id="start" class="form-control"
                       value="<?php echo e($start->format('Y-m-d')); ?>">
            </div>
            <div class="col-md-4">
                <label for="end" class="form-label">Selesai</label>
                <input type="date" name="end" id="end" class="form-control"
                       value="<?php echo e($end->format('Y-m-d')); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Terapkan</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label">Total Pemasukan</div>
            <div class="value text-success"><?php echo e(format_currency((float)$summary['total_income'])); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label">Total Pengeluaran</div>
            <div class="value text-danger"><?php echo e(format_currency((float)$summary['total_expense'])); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label">Arus Kas Bersih</div>
            <div class="value <?php echo $net >= 0 ? 'text-success' : 'text-danger'; ?>">
                <?php echo e(format_currency($net)); ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Tren Arus Kas</h5>
                    <span class="badge-soft">
                        <?php echo e($start->format('d M Y')); ?> - <?php echo e($end->format('d M Y')); ?>
                    </span>
                </div>
                <canvas id="reportTrendChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Pengeluaran per Kategori</h5>
                <canvas id="expensePieChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Detail Pemasukan</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-end">Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($incomeBreakdown): ?>
                            <?php foreach ($incomeBreakdown as $row): ?>
                                <tr>
                                    <td><?php echo e($row['name']); ?></td>
                                    <td class="text-end text-success"><?php echo e(format_currency((float)$row['total'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">Tidak ada pemasukan pada periode ini.</td>
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
                <h5 class="card-title mb-3">Detail Pengeluaran</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-end">Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($expenseBreakdown): ?>
                            <?php foreach ($expenseBreakdown as $row): ?>
                                <tr>
                                    <td><?php echo e($row['name']); ?></td>
                                    <td class="text-end text-danger"><?php echo e(format_currency((float)$row['total'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">Tidak ada pengeluaran pada periode ini.</td>
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
    window.reportChartData = {
        labels: <?php echo json_encode($monthlyLabels); ?>,
        income: <?php echo json_encode($monthlyIncome); ?>,
        expense: <?php echo json_encode($monthlyExpense); ?>,
        expenseBreakdown: <?php echo json_encode(array_map(static function (array $row) {
            return [
                'label' => $row['name'],
                'value' => (float)$row['total'],
            ];
        }, $expenseBreakdown)); ?>
    };
</script>
<?php
include __DIR__ . '/includes/footer.php';
