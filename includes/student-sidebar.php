<?php
/**
 * CampusEvents - Student Portal Sidebar & Topbar Component
 */
require_once __DIR__ . '/auth.php';
requireStudent();

$baseUrl = getBaseUrl();
$currentFile = basename($_SERVER['PHP_SELF']);
$student = getCurrentUser();
$unreadCount = getUnreadNotificationCount($student['user_id']);
?>
<div class="dashboard-layout">
  <!-- Student Sidebar -->
  <aside class="dashboard-sidebar">
    <div class="sidebar-header">
      <a href="<?= $baseUrl ?>index.php" class="brand-logo">
        <i class="fa-solid fa-graduation-cap"></i>
        <span>Campus<span class="accent">Events</span></span>
      </a>
      <span class="sidebar-role-badge student">Student</span>
    </div>

    <div class="sidebar-body">
      <div class="sidebar-group-label">Core Navigation</div>
      <ul class="sidebar-nav">
        <li>
          <a href="<?= $baseUrl ?>student/dashboard.php" class="<?= $currentFile === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>student/events.php" class="<?= $currentFile === 'events.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-days"></i>
            <span>Browse Events</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>student/registrations.php" class="<?= $currentFile === 'registrations.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-ticket"></i>
            <span>My Registrations</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>student/certificates.php" class="<?= $currentFile === 'certificates.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-award"></i>
            <span>My Certificates</span>
          </a>
        </li>
      </ul>

      <div class="sidebar-group-label">Participation</div>
      <ul class="sidebar-nav">
        <li>
          <a href="<?= $baseUrl ?>student/upload-photo.php" class="<?= $currentFile === 'upload-photo.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span>Upload Photos</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>student/feedback.php" class="<?= $currentFile === 'feedback.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-star-half-stroke"></i>
            <span>Give Feedback</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>student/notifications.php" class="<?= $currentFile === 'notifications.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bell"></i>
            <span>Notifications</span>
            <?php if ($unreadCount > 0): ?>
              <span class="sidebar-nav-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
          </a>
        </li>
      </ul>

      <div class="sidebar-group-label">Account</div>
      <ul class="sidebar-nav">
        <li>
          <a href="<?= $baseUrl ?>student/profile.php" class="<?= $currentFile === 'profile.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-gear"></i>
            <span>My Profile</span>
          </a>
        </li>
        <li>
          <a href="<?= $baseUrl ?>index.php">
            <i class="fa-solid fa-globe"></i>
            <span>Visit Website</span>
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
        <div class="sidebar-avatar">
          <?= strtoupper(substr($student['full_name'] ?? 'S', 0, 1)) ?>
        </div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name"><?= e($student['full_name']) ?></div>
          <div class="sidebar-user-role"><?= e($student['student_id'] ?? 'Student') ?></div>
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
        <h1 class="topbar-title"><?= e($pageTitle ?? 'Student Dashboard') ?></h1>
      </div>

      <div class="topbar-right">
        <a href="<?= $baseUrl ?>student/events.php" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-plus"></i> Explore Events
        </a>

        <div class="topbar-action-item">
          <a href="<?= $baseUrl ?>student/notifications.php" class="topbar-icon-btn" title="Notifications">
            <i class="fa-regular fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
              <span class="topbar-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
          </a>
        </div>

        <a href="<?= $baseUrl ?>student/profile.php" class="sidebar-user-card" style="background: var(--surface-alt); text-decoration: none; padding: 0.35rem 0.75rem;">
          <div class="sidebar-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
            <?= strtoupper(substr($student['full_name'] ?? 'S', 0, 1)) ?>
          </div>
          <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary-dark);">
            <?= e(explode(' ', $student['full_name'])[0]) ?>
          </span>
        </a>
      </div>
    </header>

    <div class="dashboard-content">
