<?php
/**
 * CampusEvents - Admin Certificate Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

// Actions: Generate Certificate
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $regId = (int)($_POST['reg_id'] ?? 0);
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (verify_csrf_token($csrfToken) && $regId > 0) {
        if ($action === 'generate_cert') {
            // Fetch registration details
            $stmtReg = $pdo->prepare("
                SELECT r.*, e.id as event_id, e.title as event_title, s.id as student_id, s.user_id, s.full_name
                FROM event_registrations r
                JOIN events e ON e.id = r.event_id
                JOIN students s ON s.id = r.student_id
                WHERE r.id = ?
            ");
            $stmtReg->execute([$regId]);
            $reg = $stmtReg->fetch();

            if ($reg) {
                // Check if certificate already exists
                $checkCert = $pdo->prepare("SELECT id FROM certificates WHERE registration_id = ?");
                $checkCert->execute([$regId]);
                if (!$checkCert->fetch()) {
                    // Generate unique certificate ID
                    $certCode = 'CERT-2026-' . strtoupper(bin2hex(random_bytes(3)));

                    $stmtInsert = $pdo->prepare("
                        INSERT INTO certificates (certificate_code, registration_id, student_id, event_id, issue_date)
                        VALUES (?, ?, ?, ?, CURRENT_DATE)
                    ");
                    $stmtInsert->execute([$certCode, $regId, $reg['student_id'], $reg['event_id']]);

                    // Update registration status to completed and eligible
                    $pdo->prepare("UPDATE event_registrations SET status = 'completed', is_certificate_eligible = 1 WHERE id = ?")->execute([$regId]);

                    // Notify student
                    addNotification(
                        $reg['user_id'],
                        'Certificate Available!',
                        "Congratulations! Your official Certificate of Participation for '{$reg['event_title']}' is ready to download.",
                        'success',
                        'student/certificates.php'
                    );

                    setFlashMessage('success', "Certificate {$certCode} generated and issued to {$reg['full_name']}!");
                } else {
                    setFlashMessage('info', 'A certificate has already been issued for this registration.');
                }
            }
        } elseif ($action === 'revoke_cert') {
            $certId = (int)($_POST['cert_id'] ?? 0);
            if ($certId > 0) {
                $pdo->prepare("DELETE FROM certificates WHERE id = ?")->execute([$certId]);
                setFlashMessage('warning', 'Certificate revoked.');
            }
        }
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$filterEvent = (int)($_GET['event_id'] ?? 0);

// Fetch all registrations with certificate info
$sql = "
    SELECT r.id as reg_id, r.registration_date, r.status, r.is_certificate_eligible,
           s.full_name as student_name, s.student_id, s.department,
           e.title as event_title, e.event_date,
           c.id as cert_id, c.certificate_code, c.issue_date
    FROM event_registrations r
    JOIN students s ON s.id = r.student_id
    JOIN events e ON e.id = r.event_id
    LEFT JOIN certificates c ON c.registration_id = r.id
    WHERE r.status != 'cancelled'
";
$params = [];

if ($filterEvent > 0) {
    $sql .= " AND r.event_id = ?";
    $params[] = $filterEvent;
}

$sql .= " ORDER BY c.id DESC, r.registration_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$eventsList = $pdo->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();

$pageTitle = 'Certificate Management';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Certificate Issuance & Credentials</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Generate verified participation credentials with tamper-proof unique certificate IDs.</p>
  </div>
</div>

<!-- Event Filter -->
<div class="dash-card" style="margin-bottom: 1.5rem;">
  <div class="dash-card-body" style="padding: 1.25rem;">
    <form action="<?= $baseUrl ?>admin/certificates.php" method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
      <div style="flex: 1; min-width: 250px;">
        <select name="event_id" class="form-control" onchange="this.form.submit()">
          <option value="0">All Events</option>
          <?php foreach ($eventsList as $ev): ?>
            <option value="<?= $ev['id'] ?>" <?= $filterEvent === (int)$ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if ($filterEvent > 0): ?>
        <a href="<?= $baseUrl ?>admin/certificates.php" class="btn btn-outline btn-sm">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Table -->
<div class="dash-card">
  <div class="dash-card-body" style="padding: 0;">
    <?php if (!empty($records)): ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Event</th>
              <th>Status</th>
              <th>Eligible</th>
              <th>Certificate ID</th>
              <th>Issue Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $row): ?>
              <tr>
                <td>
                  <strong><?= e($row['student_name']) ?></strong>
                  <div style="font-size: 0.75rem; color: var(--muted);"><?= e($row['student_id']) ?> &bull; <?= e($row['department']) ?></div>
                </td>
                <td>
                  <?= e($row['event_title']) ?>
                  <div style="font-size: 0.75rem; color: var(--muted);"><?= date('M j, Y', strtotime($row['event_date'])) ?></div>
                </td>
                <td>
                  <span class="status-pill status-<?= e($row['status']) ?>">
                    <?= ucfirst(e($row['status'])) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $row['is_certificate_eligible'] ? 'badge-success' : 'badge-navy' ?>">
                    <?= $row['is_certificate_eligible'] ? 'Yes' : 'No' ?>
                  </span>
                </td>
                <td>
                  <?php if (!empty($row['certificate_code'])): ?>
                    <span style="font-family: monospace; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.2rem 0.5rem; border-radius: 4px;">
                      <?= e($row['certificate_code']) ?>
                    </span>
                  <?php else: ?>
                    <span style="color: var(--muted); font-size: 0.8rem;">Not Generated</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?= !empty($row['issue_date']) ? date('M j, Y', strtotime($row['issue_date'])) : '—' ?>
                </td>
                <td>
                  <?php if (empty($row['cert_id'])): ?>
                    <form action="" method="POST" style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                      <input type="hidden" name="reg_id" value="<?= $row['reg_id'] ?>">
                      <input type="hidden" name="action" value="generate_cert">
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-award"></i> Generate
                      </button>
                    </form>
                  <?php else: ?>
                    <div class="action-buttons">
                      <a href="<?= $baseUrl ?>student/certificates.php?id=<?= $row['reg_id'] ?>" target="_blank" class="btn btn-secondary btn-sm" title="View / Print Certificate">
                        <i class="fa-solid fa-print"></i> View
                      </a>
                      <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Revoke this issued certificate?');">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="reg_id" value="<?= $row['reg_id'] ?>">
                        <input type="hidden" name="cert_id" value="<?= $row['cert_id'] ?>">
                        <input type="hidden" name="action" value="revoke_cert">
                        <button type="submit" class="btn btn-danger btn-sm" title="Revoke">
                          <i class="fa-solid fa-ban"></i>
                        </button>
                      </form>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa-solid fa-award"></i>
        <h4>No Records Found</h4>
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
