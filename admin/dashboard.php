<?php
/**
 * CampusEvents - Admin Coordinator Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$admin = getCurrentUser();
$pdo = getDBConnection();
$baseUrl = getBaseUrl();

// 1. Core KPIs
$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$upcomingEventsCount = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE CONCAT(event_date, ' ', event_time) >= NOW()")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalRegistrations = (int)$pdo->query("SELECT COUNT(*) FROM event_registrations WHERE status != 'cancelled'")->fetchColumn();
$totalFeedback = (int)$pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$totalCertificates = (int)$pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();

// 2. Pending Approvals (Photos & Feedback)
$pendingPhotos = $pdo->query("
    SELECT g.*, s.full_name as student_name, e.title as event_title 
    FROM gallery g
    JOIN students s ON s.id = g.student_id
    JOIN events e ON e.id = g.event_id
    WHERE g.status = 'pending'
    ORDER BY g.created_at DESC
    LIMIT 4
")->fetchAll();

// 3. Recent Registrations
$recentRegistrations = $pdo->query("
    SELECT r.id, r.registration_date, r.status, s.full_name as student_name, s.student_id, e.title as event_title, e.id as event_id
    FROM event_registrations r
    JOIN students s ON s.id = r.student_id
    JOIN events e ON e.id = r.event_id
    ORDER BY r.registration_date DESC
    LIMIT 6
")->fetchAll();

// 4. Registration Trends by Category (for lightweight CSS bar chart)
$categoryTrends = $pdo->query("
    SELECT e.category, COUNT(r.id) as reg_count
    FROM events e
    LEFT JOIN event_registrations r ON r.event_id = e.id AND r.status != 'cancelled'
    GROUP BY e.category
    ORDER BY reg_count DESC
    LIMIT 6
")->fetchAll();

$maxTrend = 1;
foreach ($categoryTrends as $ct) {
    if ($ct['reg_count'] > $maxTrend) $maxTrend = $ct['reg_count'];
}

// 5. Recent Feedback
$recentFeedback = $pdo->query("
    SELECT f.*, s.full_name, e.title as event_title
    FROM feedback f
    JOIN students s ON s.id = f.student_id
    JOIN events e ON e.id = f.event_id
    ORDER BY f.created_at DESC
    LIMIT 4
")->fetchAll();

$pageTitle = 'Coordinator Dashboard';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<!-- Welcome Banner -->
<div class="welcome-banner">
  <div class="welcome-text">
    <h2>Welcome, <?= e($admin['full_name']) ?>! 🎓</h2>
    <p>University Event Coordinator Portal &bull; Overview of campus event registrations, certificates, and student activities.</p>
  </div>
  <div style="display: flex; gap: 0.75rem;">
    <a href="<?= $baseUrl ?>admin/events.php?action=create" class="btn btn-primary" style="background: #ffffff; color: var(--primary-dark); font-weight: 700;">
      <i class="fa-solid fa-plus"></i> New Event
    </a>
    <a href="<?= $baseUrl ?>admin/reports.php" class="btn btn-secondary" style="background: rgba(255, 255, 255, 0.15); color: #ffffff; border-color: rgba(255, 255, 255, 0.3);">
      <i class="fa-solid fa-chart-line"></i> View Reports
    </a>
  </div>
</div>

<!-- 6 Statistics Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-calendar-days"></i></div>
    <div class="stat-details">
      <h3><?= $totalEvents ?></h3>
      <p>Total Events</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-regular fa-calendar-check"></i></div>
    <div class="stat-details">
      <h3><?= $upcomingEventsCount ?></h3>
      <p>Upcoming Events</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa-solid fa-user-graduate"></i></div>
    <div class="stat-details">
      <h3><?= $totalStudents ?></h3>
      <p>Total Students</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon amber"><i class="fa-solid fa-ticket"></i></div>
    <div class="stat-details">
      <h3><?= $totalRegistrations ?></h3>
      <p>Registrations</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-comments"></i></div>
    <div class="stat-details">
      <h3><?= $totalFeedback ?></h3>
      <p>Feedbacks</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-award"></i></div>
    <div class="stat-details">
      <h3><?= $totalCertificates ?></h3>
      <p>Certificates</p>
    </div>
  </div>
</div>

<!-- Main 2-Column Layout -->
<div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 2rem; align-items: flex-start;">
  
  <!-- Left: Trends Chart & Recent Registrations -->
  <div>
    
    <!-- Registration Trends by Category -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h3><i class="fa-solid fa-chart-simple" style="color: var(--accent); margin-right: 0.5rem;"></i> Participation by Category</h3>
        <span style="font-size: 0.8rem; color: var(--muted);">Live Registrations</span>
      </div>
      <div class="dash-card-body">
        <div class="chart-bars-wrapper">
          <?php foreach ($categoryTrends as $catTrend): 
            $barHeight = max(15, round(($catTrend['reg_count'] / $maxTrend) * 130));
          ?>
            <div class="chart-bar-col">
              <div class="chart-bar" style="height: <?= $barHeight ?>px;">
                <span class="chart-bar-val"><?= $catTrend['reg_count'] ?></span>
              </div>
              <span class="chart-bar-lbl"><?= e($catTrend['category']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Recent Registrations Table -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h3><i class="fa-solid fa-users-viewfinder" style="color: var(--accent); margin-right: 0.5rem;"></i> Recent Registrations</h3>
        <a href="<?= $baseUrl ?>admin/registrations.php" class="btn btn-secondary btn-sm">Manage All</a>
      </div>
      <div class="dash-card-body" style="padding: 0;">
        <?php if (!empty($recentRegistrations)): ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Event</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentRegistrations as $reg): ?>
                  <tr>
                    <td>
                      <div style="font-weight: 700; color: var(--primary-dark);"><?= e($reg['student_name']) ?></div>
                      <small style="color: var(--muted);"><?= e($reg['student_id']) ?></small>
                    </td>
                    <td><?= e($reg['event_title']) ?></td>
                    <td><?= date('M j, Y', strtotime($reg['registration_date'])) ?></td>
                    <td>
                      <span class="status-pill status-<?= e($reg['status']) ?>">
                        <?= ucfirst(e($reg['status'])) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <p>No event registrations recorded yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Right: Pending Photo Approvals & Recent Feedback -->
  <div>
    
    <!-- Pending Photo Approvals -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h3><i class="fa-solid fa-clock" style="color: var(--warning); margin-right: 0.5rem;"></i> Pending Photos (<?= count($pendingPhotos) ?>)</h3>
        <a href="<?= $baseUrl ?>admin/gallery.php" class="btn btn-secondary btn-sm">Moderate</a>
      </div>
      <div class="dash-card-body" style="padding: 0;">
        <?php if (!empty($pendingPhotos)): ?>
          <?php foreach ($pendingPhotos as $photo): ?>
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-light); display: flex; align-items: center; gap: 1rem;">
              <div style="width: 54px; height: 54px; border-radius: var(--radius-sm); overflow: hidden; background: var(--primary-navy); flex-shrink: 0;">
                <img src="<?= $baseUrl . e($photo['image_url']) ?>" alt="Pending" style="width: 100%; height: 100%; object-fit: cover;">
              </div>
              <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 700; font-size: 0.85rem; color: var(--primary-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                  <?= e($photo['caption']) ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--muted);">
                  By <?= e($photo['student_name']) ?> &bull; <?= e($photo['event_title']) ?>
                </div>
              </div>
              <a href="<?= $baseUrl ?>admin/gallery.php" class="btn btn-primary btn-sm" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                Review
              </a>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state" style="padding: 2rem 1rem;">
            <i class="fa-regular fa-circle-check" style="color: var(--success); font-size: 2rem; margin-bottom: 0.5rem;"></i>
            <p style="margin: 0; font-size: 0.85rem; color: var(--muted);">No photographs pending moderation.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Feedback -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h3><i class="fa-solid fa-comments" style="color: var(--accent); margin-right: 0.5rem;"></i> Recent Feedback</h3>
        <a href="<?= $baseUrl ?>admin/feedback.php" class="btn btn-secondary btn-sm">All</a>
      </div>
      <div class="dash-card-body" style="padding: 0;">
        <?php if (!empty($recentFeedback)): ?>
          <?php foreach ($recentFeedback as $fb): ?>
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-light);">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary-dark);"><?= e($fb['full_name']) ?></span>
                <span style="color: #f59e0b; font-size: 0.75rem;">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-<?= $i <= $fb['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                  <?php endfor; ?>
                </span>
              </div>
              <p style="font-size: 0.8rem; color: var(--muted); margin: 0 0 0.35rem; line-height: 1.4;">"<?= e(substr($fb['comment'], 0, 100)) ?>..."</p>
              <div style="font-size: 0.7rem; color: var(--muted-light);"><?= e($fb['event_title']) ?></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state" style="padding: 2rem 1rem;">
            <p style="margin: 0; font-size: 0.85rem; color: var(--muted);">No feedback received yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

    </div> <!-- /dashboard-content -->
  </div> <!-- /dashboard-main -->
</div> <!-- /dashboard-layout -->

<script src="<?= $baseUrl ?>assets/js/main.js"></script>
</body>
</html>
