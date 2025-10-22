<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Dashboard';
$hideSidebar = $hideSidebar ?? false;
$user = current_user();
$flashMessages = get_flash_messages();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> - MNG Finance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="<?php echo $hideSidebar ? 'layout-auth' : 'layout-dashboard'; ?>">
<?php if (!$hideSidebar) { ?>
<div class="dashboard-wrapper d-flex">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="dashboard-main flex-grow-1 d-flex flex-column">
        <header class="dashboard-topbar d-flex align-items-center justify-content-between">
            <div>
                <h1 class="h4 mb-0"><?php echo e($pageTitle); ?></h1>
                <p class="text-muted small mb-0">Panel kontrol keuangan perusahaan Anda</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <div class="fw-semibold"><?php echo e($user['name'] ?? ''); ?></div>
                    <small class="text-muted"><?php echo e($user['email'] ?? ''); ?></small>
                </div>
                <a class="btn btn-outline-secondary btn-sm" href="logout.php">Keluar</a>
            </div>
        </header>
        <main class="dashboard-content flex-grow-1">
            <?php foreach ($flashMessages as $flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo e($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
<?php } else { ?>
<div class="auth-wrapper container py-5">
    <?php foreach ($flashMessages as $flash): ?>
        <div class="alert alert-<?php echo e($flash['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo e($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
<?php } ?>
