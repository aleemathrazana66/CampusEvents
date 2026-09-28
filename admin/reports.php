<?php
/**
 * CampusEvents - Admin Reports & Analytics
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

// 1. Overview KPIs
$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalRegs = (int)$pdo->query("SELECT COUNT(*) FROM event_registrations WHERE status != 'cancelled'")->fetchColumn();
$totalCerts = (int)$pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
$avgRating = (float)$pdo->query("SELECT AVG(rating) FROM feedback WHERE status = 'approved'")->fetchColumn();

// 2. Event-wise participation breakdown
$eventParticipation = $pdo->query("
    SELECT e.id, e.title, e.category, e.event_date, e.max_participants,
           COUNT(r.id) as total_registered,
           SUM(CASE WHEN r.status = 'attended' THEN 1 ELSE 0 END) as attended_count,
           SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
           COUNT(c.id) as certs_issued
    FROM events e
    LEFT JOIN event_registrations r ON r.event_id = e.id AND r.status != 'cancelled'
    LEFT JOIN certificates c ON c.event_id = e.id
    GROUP BY e.id
    ORDER BY total_registered DESC
")->fetchAll();

// 3. Department Participation
$deptParticipation = $pdo->query("
    SELECT s.department, COUNT(r.id) as count
    FROM students s
    JOIN event_registrations r ON r.student_id = s.id AND r.status != 'cancelled'
    GROUP BY s.department
    ORDER BY count DESC
")->fetchAll();

$pageTitle = 'Event Reports & Analytics';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Campus Participation Reports</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Analytical summary of attendance, department engagement, and certificates.</p>
  </div>

  <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
    <i class="fa-solid fa-print"></i> Print Official Report
  </button>
</div>

<!-- Key Metric Highlights -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-calendar-check"></i></div>
    <div class="stat-details">
      <h3><?= $totalEvents ?></h3>
      <p>Organized Events</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon amber"><i class="fa-solid fa-ticket"></i></div>
    <div class="stat-details">
      <h3><?= $totalRegs ?></h3>
      <p>Active Registrations</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-award"></i></div>
    <div class="stat-details">
      <h3><?= $totalCerts ?></h3>
      <p>Certificates Issued</p>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa-solid fa-star"></i></div>
    <div class="stat-details">
      <h3><?= number_format($avgRating ?: 5.0, 1) ?> / 5.0</h3>
      <p>Average Satisfaction</p>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: flex-start; margin-bottom: 2.5rem;">
  
  <!-- Event Participation Breakdown Table -->
  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-solid fa-table-list" style="color: var(--accent); margin-right: 0.5rem;"></i> Event Participation Breakdown</h3>
    </div>
    <div class="dash-card-body" style="padding: 0;">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Event Title</th>
              <th>Category</th>
              <th>Date</th>
              <th>Registered</th>
              <th>Turnout</th>
              <th>Certs</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($eventParticipation as $ep): 
              $turnoutPercent = $ep['max_participants'] > 0 ? round(($ep['total_registered'] / $ep['max_participants']) * 100) : 0;
            ?>
              <tr>
                <td><strong><?= e($ep['title']) ?></strong></td>
                <td><span class="badge badge-primary"><?= e($ep['category']) ?></span></td>
                <td><?= date('M j, Y', strtotime($ep['event_date'])) ?></td>
                <td><strong><?= $ep['total_registered'] ?></strong> / <?= $ep['max_participants'] ?></td>
                <td>
                  <span class="badge <?= $turnoutPercent > 70 ? 'badge-success' : 'badge-navy' ?>">
                    <?= $turnoutPercent ?>%
                  </span>
                </td>
                <td><?= $ep['certs_issued'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Department Engagement Table -->
  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-solid fa-building-columns" style="color: var(--accent); margin-right: 0.5rem;"></i> By Department</h3>
    </div>
    <div class="dash-card-body" style="padding: 0;">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Department</th>
              <th>Registrations</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($deptParticipation as $dp): ?>
              <tr>
                <td><?= e($dp['department']) ?></td>
                <td><strong><?= $dp['count'] ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
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
