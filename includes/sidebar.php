<?php
declare(strict_types=1);

$current = basename($_SERVER['PHP_SELF']);

$navItems = [
    ['label' => 'Dashboard', 'icon' => 'speedometer2', 'href' => 'dashboard.php'],
    ['label' => 'Transaksi', 'icon' => 'repeat', 'href' => 'transactions.php'],
    ['label' => 'Akun', 'icon' => 'wallet', 'href' => 'accounts.php'],
    ['label' => 'Kategori', 'icon' => 'tags', 'href' => 'categories.php'],
    ['label' => 'Anggaran', 'icon' => 'pie-chart', 'href' => 'budgets.php'],
    ['label' => 'Laporan', 'icon' => 'bar-chart', 'href' => 'reports.php'],
];
?>
<aside class="dashboard-sidebar">
    <div class="sidebar-brand">
        <span class="brand-logo">MNG</span>
        <span class="brand-name">Finance Admin</span>
    </div>
    <nav>
        <ul class="nav flex-column">
            <?php foreach ($navItems as $item): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current === $item['href'] ? 'active' : ''; ?>" href="<?php echo $item['href']; ?>">
                        <span class="nav-icon bi bi-<?php echo $item['icon']; ?>"></span>
                        <span><?php echo $item['label']; ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
