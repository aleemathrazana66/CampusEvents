<?php
/**
 * CampusEvents - Admin Student Directory
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

$search = trim($_GET['search'] ?? '');
$filterDept = trim($_GET['department'] ?? '');

$sql = "
    SELECT s.*, u.email,
           (SELECT COUNT(*) FROM event_registrations WHERE student_id = s.id AND status != 'cancelled') as registration_count,
           (SELECT COUNT(*) FROM certificates WHERE student_id = s.id) as certificate_count
    FROM students s
    JOIN users u ON u.id = s.user_id
    WHERE 1=1
";
$params = [];

if (!empty($filterDept)) {
    $sql .= " AND s.department = ?";
    $params[] = $filterDept;
}

if (!empty($search)) {
    $sql .= " AND (s.full_name LIKE ? OR s.student_id LIKE ? OR u.email LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get list of distinct departments
$departments = $pdo->query("SELECT DISTINCT department FROM students ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Student Directory';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Enrolled Students Directory</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Inspect student participation, departments, and credentials earned.</p>
  </div>
</div>

<!-- Search & Department Filter -->
<div class="dash-card" style="margin-bottom: 1.5rem;">
  <div class="dash-card-body" style="padding: 1.25rem;">
    <form action="<?= $baseUrl ?>admin/students.php" method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
      <div style="flex: 1; min-width: 250px;">
        <input type="text" name="search" class="form-control" placeholder="Search by name, student ID, or email..." value="<?= e($search) ?>">
      </div>

      <div style="min-width: 200px;">
        <select name="department" class="form-control" onchange="this.form.submit()">
          <option value="">All Departments</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= e($dept) ?>" <?= $filterDept === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <?php if (!empty($search) || !empty($filterDept)): ?>
        <a href="<?= $baseUrl ?>admin/students.php" class="btn btn-outline btn-sm">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="dash-card">
  <div class="dash-card-body" style="padding: 0;">
    <?php if (!empty($students)): ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Student ID</th>
              <th>Email & Phone</th>
              <th>Academic Dept</th>
              <th>Course & Sem</th>
              <th>Registrations</th>
              <th>Certificates</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $st): ?>
              <tr>
                <td>
                  <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="author-avatar" style="width: 36px; height: 36px; font-size: 0.85rem;">
                      <?= strtoupper(substr($st['full_name'], 0, 1)) ?>
                    </div>
                    <div>
                      <div style="font-weight: 700; color: var(--primary-dark);"><?= e($st['full_name']) ?></div>
                    </div>
                  </div>
                </td>
                <td><strong style="font-family: monospace;"><?= e($st['student_id']) ?></strong></td>
                <td>
                  <div><?= e($st['email']) ?></div>
                  <small style="color: var(--muted);"><?= e($st['phone']) ?></small>
                </td>
                <td><?= e($st['department']) ?></td>
                <td><?= e($st['course']) ?> &bull; <?= e($st['semester']) ?></td>
                <td>
                  <span class="badge badge-primary"><?= $st['registration_count'] ?></span>
                </td>
                <td>
                  <span class="badge badge-success"><?= $st['certificate_count'] ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa-solid fa-user-xmark"></i>
        <h4>No Students Found</h4>
        <p>No student records match your query.</p>
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
