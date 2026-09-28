<?php
/**
 * CampusEvents - Admin Portal Sidebar & Topbar Component
 */
require_once __DIR__ . '/auth.php';
requireAdmin();

$baseUrl = getBaseUrl();
$currentFile = basename($_SERVER['PHP_SELF']);
$admin = getCurrentUser();
$unreadCount = getUnreadNotificationCount($admin['user_id']);
?>
<div class="dashboard-layout">
  <!-- Admin Sidebar -->
  <aside class="dashboard-sidebar">
    <div class="sidebar-header">
      <a href="<?= $baseUrl ?>index.php" class="brand-logo">
        <i class="fa-solid fa-graduation-cap"></i>
        <span>Campus<span class="accent">Events</span></span>
      </a>
      <span class="sidebar-role-badge admin">Admin</span>
    </div>

    <div class="sidebar-body">
      <div class="sidebar-group-label">Overview</div>
      <ul class="sidebar-nav">
        <li>
          <a href="<?= $baseUrl ?>admin/dashboard.php" class="<?= $currentFile === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>admin/reports.php" class="<?= $currentFile === 'reports.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Reports & Stats</span>
          </a>
        </li>
      </ul>

      <div class="sidebar-group-label">Event Operations</div>
      <ul class="sidebar-nav">
        <li>
          <a href="<?= $baseUrl ?>admin/events.php" class="<?= $currentFile === 'events.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Manage Events</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>admin/registrations.php" class="<?= $currentFile === 'registrations.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users-viewfinder"></i>
            <span>Registrations</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>admin/certificates.php" class="<?= $currentFile === 'certificates.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-award"></i>
            <span>Certificates</span>
          </a>
        </li>
      </ul>

      <div class="sidebar-group-label">Community & Moderation</div>
      <ul class="sidebar-nav">
        <li>
          <a href="<?= $baseUrl ?>admin/students.php" class="<?= $currentFile === 'students.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-graduate"></i>
            <span>Student Directory</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>admin/gallery.php" class="<?= $currentFile === 'gallery.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-images"></i>
            <span>Photo Moderation</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>admin/feedback.php" class="<?= $currentFile === 'feedback.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-comments"></i>
            <span>Feedback Moderation</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>admin/notifications.php" class="<?= $currentFile === 'notifications.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bell"></i>
            <span>System Alerts</span>
            <?php if ($unreadCount > 0): ?>
              <span class="sidebar-nav-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
          </a>
        </li>
      </ul>

      <div class="sidebar-group-label">System</div>
      <ul class="sidebar-nav">
        <li>
          <a href="<?= $baseUrl ?>admin/profile.php" class="<?= $currentFile === 'profile.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Admin Settings</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>index.php">
            <i class="fa-solid fa-globe"></i>
            <span>Public Site</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>auth/logout.php" style="color: #fca5a5;">
            <i class="fa-solid fa-arrow-right-from-bracket" style="color: #ef4444;"></i>
            <span>Log Out</span>
          </a>
        </li>
      </ul>
    </div>

    <div class="sidebar-footer">
      <div class="sidebar-user-card">
        <div class="sidebar-avatar" style="background: #ef4444;">
          <?= strtoupper(substr($admin['full_name'] ?? 'A', 0, 1)) ?>
        </div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name"><?= e($admin['full_name']) ?></div>
          <div class="sidebar-user-role">Coordinator</div>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main Content Area -->
  <div class="dashboard-main">
    <!-- Topbar -->
    <header class="dashboard-topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle-btn" type="button" aria-label="Toggle sidebar">
          <i class="fa-solid fa-bars"></i>
        </button>
        <h1 class="topbar-title"><?= e($pageTitle ?? 'Admin Dashboard') ?></h1>
      </div>

      <div class="topbar-right">
        <a href="<?= $baseUrl ?>admin/events.php?action=create" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-plus"></i> Create New Event
        </a>

        <div class="topbar-action-item">
          <a href="<?= $baseUrl ?>admin/notifications.php" class="topbar-icon-btn" title="Alerts">
            <i class="fa-regular fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
              <span class="topbar-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
          </a>
        </div>

        <a href="<?= $baseUrl ?>admin/profile.php" class="sidebar-user-card" style="background: var(--surface-alt); text-decoration: none; padding: 0.35rem 0.75rem;">
          <div class="sidebar-avatar" style="width: 32px; height: 32px; font-size: 0.8rem; background: #ef4444;">
            <?= strtoupper(substr($admin['full_name'] ?? 'A', 0, 1)) ?>
          </div>
          <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary-dark);">
            <?= e(explode(' ', $admin['full_name'])[0]) ?>
          </span>
        </a>
      </div>
    </header>

    <div class="dashboard-content">
