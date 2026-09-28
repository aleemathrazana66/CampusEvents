<?php
/**
 * CampusEvents - Admin Registration Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

// Actions: update status or certificate eligibility
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regId = (int)($_POST['reg_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (verify_csrf_token($csrfToken) && $regId > 0) {
        if ($action === 'set_status') {
            $newStatus = $_POST['status'] ?? 'registered';
            if (in_array($newStatus, ['registered', 'attended', 'completed', 'cancelled'])) {
                $stmt = $pdo->prepare("UPDATE event_registrations SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $regId]);
                setFlashMessage('success', "Registration status updated to " . ucfirst($newStatus));
            }
        } elseif ($action === 'toggle_cert_eligible') {
            $stmt = $pdo->prepare("UPDATE event_registrations SET is_certificate_eligible = 1 - is_certificate_eligible WHERE id = ?");
            $stmt->execute([$regId]);
            setFlashMessage('success', "Certificate eligibility updated.");
        }
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$filterEvent = (int)($_GET['event_id'] ?? 0);
$filterStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT r.*, s.full_name as student_name, s.student_id, s.department, s.course, u.email,
           e.title as event_title, e.event_date,
           c.certificate_code
    FROM event_registrations r
    JOIN students s ON s.id = r.student_id
    JOIN users u ON u.id = s.user_id
    JOIN events e ON e.id = r.event_id
    WHERE 1=1
";
$params = [];

if ($filterEvent > 0) {
    $sql .= " AND r.event_id = ?";
    $params[] = $filterEvent;
}
if ($filterStatus !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $filterStatus;
}
if (!empty($search)) {
    $sql .= " AND (s.full_name LIKE ? OR s.student_id LIKE ? OR u.email LIKE ? OR e.title LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY r.registration_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

// Events dropdown
$eventsList = $pdo->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();

$pageTitle = 'Manage Registrations';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Event Participants & Registrations</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Track participant attendance, award completion credentials, and manage rosters.</p>
  </div>
</div>

<!-- Filters Bar -->
<div class="dash-card" style="margin-bottom: 1.5rem;">
  <div class="dash-card-body" style="padding: 1.25rem;">
    <form action="<?= $baseUrl ?>admin/registrations.php" method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
      <div style="flex: 1; min-width: 220px;">
        <select name="event_id" class="form-control" onchange="this.form.submit()">
          <option value="0">All Events (<?= count($eventsList) ?>)</option>
          <?php foreach ($eventsList as $ev): ?>
            <option value="<?= $ev['id'] ?>" <?= $filterEvent === (int)$ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="width: 170px;">
        <select name="status" class="form-control" onchange="this.form.submit()">
          <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
          <option value="registered" <?= $filterStatus === 'registered' ? 'selected' : '' ?>>Registered</option>
          <option value="attended" <?= $filterStatus === 'attended' ? 'selected' : '' ?>>Attended</option>
          <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
          <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>

      <div style="flex: 1; min-width: 200px; position: relative;">
        <input type="text" name="search" class="form-control" placeholder="Search by student name, ID or email..." value="<?= e($search) ?>">
      </div>

      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <?php if ($filterEvent > 0 || $filterStatus !== 'all' || !empty($search)): ?>
        <a href="<?= $baseUrl ?>admin/registrations.php" class="btn btn-outline btn-sm">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Registrations Table -->
<div class="dash-card">
  <div class="dash-card-body" style="padding: 0;">
    <?php if (!empty($registrations)): ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Student Details</th>
              <th>Event</th>
              <th>Registered</th>
              <th>Status</th>
              <th>Cert Eligible</th>
              <th>Update Attendance / Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($registrations as $r): ?>
              <tr>
                <td>
                  <div style="font-weight: 700; color: var(--primary-dark);"><?= e($r['student_name']) ?></div>
                  <div style="font-size: 0.75rem; color: var(--muted);"><?= e($r['student_id']) ?> &bull; <?= e($r['email']) ?></div>
                  <div style="font-size: 0.7rem; color: var(--muted-light);"><?= e($r['department']) ?></div>
                </td>
                <td>
                  <strong><?= e($r['event_title']) ?></strong>
                  <div style="font-size: 0.75rem; color: var(--muted);"><?= date('M j, Y', strtotime($r['event_date'])) ?></div>
                </td>
                <td><?= date('M j, Y', strtotime($r['registration_date'])) ?></td>
                <td>
                  <span class="status-pill status-<?= e($r['status']) ?>">
                    <?= ucfirst(e($r['status'])) ?>
                  </span>
                </td>
                <td>
                  <form action="" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="toggle_cert_eligible">
                    <button type="submit" class="badge <?= $r['is_certificate_eligible'] ? 'badge-success' : 'badge-navy' ?>" style="cursor: pointer; border: none;" title="Toggle eligibility">
                      <?= $r['is_certificate_eligible'] ? 'Eligible' : 'Not Eligible' ?>
                    </button>
                  </form>
                  <?php if (!empty($r['certificate_code'])): ?>
                    <div style="font-size: 0.7rem; color: #059669; font-weight: 700; margin-top: 0.2rem;">
                      <i class="fa-solid fa-check"></i> Issued
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <form action="" method="POST" style="display: flex; gap: 0.35rem;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="set_status">
                    
                    <select name="status" class="form-control" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; width: 120px;" onchange="this.form.submit()">
                      <option value="registered" <?= $r['status'] === 'registered' ? 'selected' : '' ?>>Registered</option>
                      <option value="attended" <?= $r['status'] === 'attended' ? 'selected' : '' ?>>Attended</option>
                      <option value="completed" <?= $r['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                      <option value="cancelled" <?= $r['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa-solid fa-users-slash"></i>
        <h4>No Registrations Found</h4>
        <p>No participant records match the current filters.</p>
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
