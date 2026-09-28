<?php
/**
 * CampusEvents - Student Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student = getCurrentUser();
$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$studentPk = (int)$student['student_id_pk'];

// 1. KPI Statistics
$stmtTotalReg = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE student_id = ? AND status != 'cancelled'");
$stmtTotalReg->execute([$studentPk]);
$totalReg = (int)$stmtTotalReg->fetchColumn();

$stmtUpcoming = $pdo->prepare("
    SELECT COUNT(*) 
    FROM event_registrations r
    JOIN events e ON e.id = r.event_id
    WHERE r.student_id = ? AND r.status = 'registered' AND CONCAT(e.event_date, ' ', e.event_time) >= NOW()
");
$stmtUpcoming->execute([$studentPk]);
$upcomingCount = (int)$stmtUpcoming->fetchColumn();

$stmtCompleted = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE student_id = ? AND status = 'completed'");
$stmtCompleted->execute([$studentPk]);
$completedCount = (int)$stmtCompleted->fetchColumn();

$stmtCerts = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE student_id = ?");
$stmtCerts->execute([$studentPk]);
$certsCount = (int)$stmtCerts->fetchColumn();

// 2. Upcoming Registered Events
$stmtUpcomingEvents = $pdo->prepare("
    SELECT r.id as reg_id, r.registration_date, r.status, e.*
    FROM event_registrations r
    JOIN events e ON e.id = r.event_id
    WHERE r.student_id = ? AND r.status = 'registered' AND CONCAT(e.event_date, ' ', e.event_time) >= NOW()
    ORDER BY CONCAT(e.event_date, ' ', e.event_time) ASC
    LIMIT 4
");
$stmtUpcomingEvents->execute([$studentPk]);
$upcomingEvents = $stmtUpcomingEvents->fetchAll();

// 3. Recent Registrations History
$stmtRecentReg = $pdo->prepare("
    SELECT r.id as reg_id, r.registration_date, r.status, r.is_certificate_eligible, e.id as event_id, e.title, e.event_date, e.event_time, e.venue
    FROM event_registrations r
    JOIN events e ON e.id = r.event_id
    WHERE r.student_id = ?
    ORDER BY r.registration_date DESC
    LIMIT 5
");
$stmtRecentReg->execute([$studentPk]);
$recentRegistrations = $stmtRecentReg->fetchAll();

// 4. Recent Notifications
$stmtNotifs = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 4
");
$stmtNotifs->execute([$student['user_id']]);
$notifications = $stmtNotifs->fetchAll();

$pageTitle = 'Student Dashboard';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/student-sidebar.php';
?>

<!-- Welcome Banner -->
<div class="welcome-banner">
  <div class="welcome-text">
    <h2>Welcome back, <?= e($student['full_name']) ?>! 👋</h2>
    <p>You have <strong><?= $upcomingCount ?> upcoming event<?= $upcomingCount === 1 ? '' : 's' ?></strong> scheduled. Keep actively participating to earn verified credentials!</p>
  </div>
  <div>
    <a href="<?= $baseUrl ?>student/events.php" class="btn btn-primary" style="background: #ffffff; color: var(--primary-dark); font-weight: 700;">
      <i class="fa-solid fa-compass"></i> Discover Events
    </a>
  </div>
</div>

<!-- KPI Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-ticket"></i></div>
    <div class="stat-details">
      <h3><?= $totalReg ?></h3>
      <p>Registered Events</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-calendar-check"></i></div>
    <div class="stat-details">
      <h3><?= $upcomingCount ?></h3>
      <p>Upcoming Events</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa-solid fa-flag-checkered"></i></div>
    <div class="stat-details">
      <h3><?= $completedCount ?></h3>
      <p>Completed Events</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon amber"><i class="fa-solid fa-award"></i></div>
    <div class="stat-details">
      <h3><?= $certsCount ?></h3>
      <p>Certificates Earned</p>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="dash-card">
  <div class="dash-card-header">
    <h3><i class="fa-solid fa-bolt" style="color: var(--accent); margin-right: 0.5rem;"></i> Quick Actions</h3>
  </div>
  <div class="dash-card-body" style="padding-bottom: 0.5rem;">
    <div class="quick-actions-grid">
      <a href="<?= $baseUrl ?>student/events.php" class="quick-action-tile">
        <i class="fa-solid fa-compass"></i>
        <span>Explore Events</span>
      </a>
      <a href="<?= $baseUrl ?>student/registrations.php" class="quick-action-tile">
        <i class="fa-solid fa-ticket"></i>
        <span>My Registrations</span>
      </a>
      <a href="<?= $baseUrl ?>student/certificates.php" class="quick-action-tile">
        <i class="fa-solid fa-award"></i>
        <span>View Certificates</span>
      </a>
      <a href="<?= $baseUrl ?>student/upload-photo.php" class="quick-action-tile">
        <i class="fa-solid fa-cloud-arrow-up"></i>
        <span>Upload Photos</span>
      </a>
      <a href="<?= $baseUrl ?>student/feedback.php" class="quick-action-tile">
        <i class="fa-solid fa-star"></i>
        <span>Give Feedback</span>
      </a>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
  
  <!-- Left: Upcoming Registered Events & Recent Registrations -->
  <div>
    
    <!-- Upcoming Events -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h3><i class="fa-regular fa-calendar-days" style="color: var(--accent); margin-right: 0.5rem;"></i> Next Upcoming Registered Events</h3>
        <a href="<?= $baseUrl ?>student/registrations.php" class="btn btn-secondary btn-sm">View All</a>
      </div>
      <div class="dash-card-body" style="padding: 0;">
        <?php if (!empty($upcomingEvents)): ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>Date & Time</th>
                  <th>Venue</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($upcomingEvents as $ue): ?>
                  <tr>
                    <td>
                      <div style="font-weight: 700; color: var(--primary-dark);"><?= e($ue['title']) ?></div>
                      <span class="badge badge-primary" style="font-size: 0.65rem; padding: 0.15rem 0.45rem;"><?= e($ue['category']) ?></span>
                    </td>
                    <td><?= date('M j, Y', strtotime($ue['event_date'])) ?> <br><small class="text-muted"><?= date('g:i A', strtotime($ue['event_time'])) ?></small></td>
                    <td><?= e($ue['venue']) ?></td>
                    <td>
                      <a href="<?= $baseUrl ?>event-details.php?id=<?= $ue['id'] ?>" class="btn btn-secondary btn-sm" title="View details">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Details
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="fa-regular fa-calendar-xmark"></i>
            <h4>No Upcoming Events Registered</h4>
            <p>You haven't registered for any upcoming events yet.</p>
            <a href="<?= $baseUrl ?>student/events.php" class="btn btn-primary btn-sm">Explore Campus Events</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Registrations History -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h3><i class="fa-solid fa-clock-rotate-left" style="color: var(--accent); margin-right: 0.5rem;"></i> Recent Registrations</h3>
      </div>
      <div class="dash-card-body" style="padding: 0;">
        <?php if (!empty($recentRegistrations)): ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentRegistrations as $rr): ?>
                  <tr>
                    <td>
                      <a href="<?= $baseUrl ?>event-details.php?id=<?= $rr['event_id'] ?>" style="font-weight: 700; color: var(--text);">
                        <?= e($rr['title']) ?>
                      </a>
                    </td>
                    <td><?= date('M j, Y', strtotime($rr['event_date'])) ?></td>
                    <td>
                      <span class="status-pill status-<?= e($rr['status']) ?>">
                        <?= ucfirst(e($rr['status'])) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="fa-regular fa-ticket"></i>
            <h4>No Registrations Yet</h4>
            <p>Start participating today!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Right: Notifications & Profile Summary -->
  <div>
    
    <div class="dash-card">
      <div class="dash-card-header">
        <h3><i class="fa-regular fa-bell" style="color: var(--accent); margin-right: 0.5rem;"></i> Notifications</h3>
        <a href="<?= $baseUrl ?>student/notifications.php" class="btn btn-secondary btn-sm">Inbox</a>
      </div>
      <div class="dash-card-body" style="padding: 0;">
        <?php if (!empty($notifications)): ?>
          <?php foreach ($notifications as $n): ?>
            <div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>">
              <div class="notif-icon" style="background: rgba(37, 99, 235, 0.1); color: var(--accent);">
                <i class="fa-solid fa-bell"></i>
              </div>
              <div class="notif-info">
                <div class="notif-title"><?= e($n['title']) ?></div>
                <div class="notif-message"><?= e($n['message']) ?></div>
                <div class="notif-time"><?= date('M j, g:i A', strtotime($n['created_at'])) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state" style="padding: 2rem 1rem;">
            <i class="fa-regular fa-bell-slash"></i>
            <h4>No Notifications</h4>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Student Profile Card -->
    <div class="dash-card">
      <div class="dash-card-header">
        <h3><i class="fa-solid fa-id-card" style="color: var(--accent); margin-right: 0.5rem;"></i> Student Card</h3>
        <a href="<?= $baseUrl ?>student/profile.php" class="btn btn-secondary btn-sm">Edit</a>
      </div>
      <div class="dash-card-body" style="text-align: center;">
        <div class="author-avatar" style="width: 64px; height: 64px; margin: 0 auto 1rem; font-size: 1.5rem;">
          <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
        </div>
        <h4 style="font-size: 1.1rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">
          <?= e($student['full_name']) ?>
        </h4>
        <p style="font-size: 0.85rem; color: var(--accent); font-weight: 700; margin-bottom: 1rem;">
          <?= e($student['student_id']) ?>
        </p>
        <div style="font-size: 0.85rem; color: var(--muted); text-align: left; background: var(--surface-alt); padding: 1rem; border-radius: var(--radius-md); display: flex; flex-direction: column; gap: 0.4rem;">
          <div><strong>Dept:</strong> <?= e($student['department']) ?></div>
          <div><strong>Course:</strong> <?= e($student['course']) ?> (<?= e($student['semester']) ?>)</div>
          <div><strong>Email:</strong> <?= e($student['email']) ?></div>
        </div>
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
