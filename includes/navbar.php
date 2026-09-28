<?php
/**
 * CampusEvents - Public Navbar Component
 */
require_once __DIR__ . '/auth.php';

$baseUrl = getBaseUrl();
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<header class="navbar">
  <div class="container navbar-inner">
    <a href="<?= $baseUrl ?>index.php" class="brand-logo">
      <i class="fa-solid fa-graduation-cap"></i>
      <span>Campus<span class="accent">Events</span></span>
    </a>

    <ul class="nav-links">
      <li><a href="<?= $baseUrl ?>index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
      <li><a href="<?= $baseUrl ?>events.php" class="<?= $currentPage === 'events.php' ? 'active' : '' ?>">Events</a></li>
      <li><a href="<?= $baseUrl ?>about.php" class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">About</a></li>
      <li><a href="<?= $baseUrl ?>gallery.php" class="<?= $currentPage === 'gallery.php' ? 'active' : '' ?>">Gallery</a></li>
      <li><a href="<?= $baseUrl ?>feedback.php" class="<?= $currentPage === 'feedback.php' ? 'active' : '' ?>">Feedback</a></li>
    </ul>

    <div class="nav-actions">
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="<?= $baseUrl ?>admin/dashboard.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-gauge-high"></i> Admin Portal
          </a>
        <?php else: ?>
          <a href="<?= $baseUrl ?>student/dashboard.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-user"></i> Student Portal
          </a>
        <?php endif; ?>
        <a href="<?= $baseUrl ?>auth/logout.php" class="btn btn-secondary btn-sm" title="Logout">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
      <?php else: ?>
        <a href="<?= $baseUrl ?>auth/login.php" class="btn btn-secondary btn-sm">Login</a>
        <a href="<?= $baseUrl ?>auth/register.php" class="btn btn-primary btn-sm">Register</a>
      <?php endif; ?>

      <button class="mobile-toggle" type="button" aria-label="Toggle navigation">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>
</header>
