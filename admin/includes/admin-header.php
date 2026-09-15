<?php
/**
 * Admin - Header Template
 */
require_once __DIR__ . '/../permissions.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle ?? 'Admin'); ?> - FF Reichenau Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.png?v=2" alt="FF Reichenau" class="sidebar-logo">
            <span class="sidebar-title">FF Reichenau</span>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="sidebar-link <?php echo ($activePage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="reports.php" class="sidebar-link <?php echo ($activePage ?? '') === 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-newspaper"></i> Berichte
            </a>
            <a href="members.php" class="sidebar-link <?php echo ($activePage ?? '') === 'members' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Mannschaft
            </a>
            <a href="ranks.php" class="sidebar-link <?php echo ($activePage ?? '') === 'ranks' ? 'active' : ''; ?>">
                <i class="fas fa-medal"></i> Dienstgrade
            </a>
            <?php if (userHasPermission('orgchart.manage')): ?>
                <a href="orgchart.php" class="sidebar-link <?php echo ($activePage ?? '') === 'orgchart' ? 'active' : ''; ?>">
                    <i class="fas fa-sitemap"></i> Organigramm
                </a>
            <?php endif; ?>

            <div class="sidebar-divider"></div>

            <?php if (userHasPermission('users.manage')): ?>
                <a href="users.php" class="sidebar-link <?php echo ($activePage ?? '') === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i> Benutzer
                </a>
            <?php endif; ?>
            <?php if (userHasPermission('deploy.manage')): ?>
                <a href="deploy.php" class="sidebar-link <?php echo ($activePage ?? '') === 'deploy' ? 'active' : ''; ?>">
                    <i class="fab fa-github"></i> Deployment
                </a>
            <?php endif; ?>
            <a href="settings.php" class="sidebar-link <?php echo ($activePage ?? '') === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Einstellungen
            </a>
            <a href="../index.php" class="sidebar-link" target="_blank">
                <i class="fas fa-external-link-alt"></i> Website ansehen
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <i class="fas fa-user-circle"></i>
                <span><?php echo e($_SESSION['admin_user_name'] ?? 'Admin'); ?></span>
            </div>
            <a href="logout.php" class="sidebar-link logout-link">
                <i class="fas fa-sign-out-alt"></i> Abmelden
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="admin-page-title"><?php echo e($pageTitle ?? 'Dashboard'); ?></h1>
        </header>

        <!-- Flash Messages -->
        <?php foreach (getFlashes() as $flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo e($flash['message']); ?>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endforeach; ?>

        <div class="admin-content">
