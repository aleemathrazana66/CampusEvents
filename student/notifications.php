<?php
/**
 * CampusEvents - Student Notifications Inbox
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student = getCurrentUser();
$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$userId = (int)$student['user_id'];

// Handle Mark All Read
if (isset($_POST['action']) && $_POST['action'] === 'mark_all') {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    setFlashMessage('success', 'All notifications marked as read.');
    header("Location: {$baseUrl}student/notifications.php");
    exit;
}

// Fetch all notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/student-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Notifications Inbox</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Important updates regarding your registrations, reminders, and certificates.</p>
  </div>

  <?php if (!empty($notifications)): ?>
    <form action="<?= $baseUrl ?>student/notifications.php" method="POST">
      <input type="hidden" name="action" value="mark_all">
      <button type="submit" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-check-double"></i> Mark All as Read
      </button>
    </form>
  <?php endif; ?>
</div>

<div class="dash-card">
  <div class="dash-card-body" style="padding: 0;">
    <?php if (!empty($notifications)): ?>
      <div style="display: flex; flex-direction: column;">
        <?php foreach ($notifications as $n): 
          $iconMap = [
            'success' => 'fa-solid fa-circle-check',
            'warning' => 'fa-solid fa-triangle-exclamation',
            'reminder' => 'fa-regular fa-clock',
            'info' => 'fa-solid fa-circle-info'
          ];
          $colorMap = [
            'success' => 'var(--success)',
            'warning' => 'var(--warning)',
            'reminder' => 'var(--accent)',
            'info' => 'var(--info)'
          ];
        ?>
          <div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>">
            <div class="notif-icon" style="background: rgba(37, 99, 235, 0.08); color: <?= $colorMap[$n['type']] ?? 'var(--accent)' ?>;">
              <i class="<?= $iconMap[$n['type']] ?? 'fa-solid fa-bell' ?>"></i>
            </div>
            <div class="notif-info">
              <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <div class="notif-title"><?= e($n['title']) ?></div>
                <div class="notif-time"><?= date('M j, Y • g:i A', strtotime($n['created_at'])) ?></div>
              </div>
              <div class="notif-message"><?= e($n['message']) ?></div>
              <?php if (!empty($n['link'])): ?>
                <a href="<?= $baseUrl . e($n['link']) ?>" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.75rem; margin-top: 0.35rem;">
                  View Related Page <i class="fa-solid fa-arrow-right" style="font-size: 0.7rem;"></i>
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa-regular fa-bell-slash"></i>
        <h4>No Notifications</h4>
        <p>You are all caught up! New event updates and certificate alerts will appear here.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

    </div> <!-- /dashboard-content -->
  </div> <!-- /dashboard-main -->
</div> <!-- /dashboard-layout -->

<script src="<?= $baseUrl ?>assets/js/main.js"></script>
</body>
</html>
