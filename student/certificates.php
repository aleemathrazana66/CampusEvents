<?php
/**
 * CampusEvents - Student Certificates
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student = getCurrentUser();
$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$studentPk = (int)$student['student_id_pk'];

// Fetch all certificates awarded to this student
$stmt = $pdo->prepare("
    SELECT c.*, e.title as event_title, e.category, e.event_date, e.organizer, e.venue, s.full_name as student_name, s.student_id
    FROM certificates c
    JOIN events e ON e.id = c.event_id
    JOIN students s ON s.id = c.student_id
    WHERE c.student_id = ?
    ORDER BY c.issue_date DESC
");
$stmt->execute([$studentPk]);
$certificates = $stmt->fetchAll();

$viewCertId = (int)($_GET['id'] ?? 0);
$activeCert = null;
if ($viewCertId > 0) {
    foreach ($certificates as $cert) {
        if ($cert['registration_id'] == $viewCertId || $cert['id'] == $viewCertId) {
            $activeCert = $cert;
            break;
        }
    }
}
if (!$activeCert && !empty($certificates)) {
    $activeCert = $certificates[0];
}

$pageTitle = 'My Certificates';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/student-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">My Verified Certificates</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Official certificates of participation and excellence awarded by the University.</p>
  </div>
</div>

<?php if (!empty($certificates)): ?>
  <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: flex-start;">
    
    <!-- Certificates List Column -->
    <div>
      <div class="dash-card">
        <div class="dash-card-header">
          <h3><i class="fa-solid fa-award" style="color: var(--warning); margin-right: 0.5rem;"></i> Earned Credentials (<?= count($certificates) ?>)</h3>
        </div>
        <div class="dash-card-body" style="padding: 0.75rem;">
          <div style="display: flex; flex-direction: column; gap: 0.65rem;">
            <?php foreach ($certificates as $c): 
              $isSelected = ($activeCert && $activeCert['id'] === $c['id']);
            ?>
              <div style="background: <?= $isSelected ? 'rgba(37, 99, 235, 0.08)' : 'var(--surface-alt)' ?>; border: 1px solid <?= $isSelected ? 'var(--accent)' : 'var(--border)' ?>; border-radius: var(--radius-md); padding: 1rem; cursor: pointer; transition: var(--transition);"
                   onclick="window.location.href='<?= $baseUrl ?>student/certificates.php?id=<?= $c['id'] ?>'">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                  <span class="badge badge-success" style="font-size: 0.65rem;">Verified</span>
                  <span style="font-size: 0.75rem; color: var(--muted); font-family: monospace; font-weight: 700;"><?= e($c['certificate_code']) ?></span>
                </div>
                <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--primary-dark); margin-bottom: 0.25rem;"><?= e($c['event_title']) ?></h4>
                <div style="font-size: 0.75rem; color: var(--muted);">Issued: <?= date('M j, Y', strtotime($c['issue_date'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Certificate Preview & Print Frame -->
    <?php if ($activeCert): ?>
      <div>
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-bottom: 1rem;">
          <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print / Download PDF
          </button>
        </div>

        <div class="certificate-preview-card" id="printable-certificate">
          <div class="cert-badge-stamp">
            <i class="fa-solid fa-award"></i>
          </div>

          <div class="cert-college">CampusEvents University</div>
          <div style="font-size: 0.9rem; letter-spacing: 0.15em; text-transform: uppercase; color: #64748b; margin-bottom: 1rem;">
            Office of Student Activities & Cultural Affairs
          </div>

          <div class="cert-title">Certificate of Participation</div>

          <div style="font-size: 1rem; color: #64748b; margin-bottom: 0.5rem;">
            This certificate is proudly presented to
          </div>

          <div class="cert-recipient">
            <?= e($activeCert['student_name']) ?>
          </div>

          <div style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1rem;">
            Student ID: <strong><?= e($activeCert['student_id']) ?></strong>
          </div>

          <p class="cert-text">
            For outstanding commitment, active engagement, and successful participation in <br>
            <strong>"<?= e($activeCert['event_title']) ?>"</strong><br>
            held on <?= date('F j, Y', strtotime($activeCert['event_date'])) ?> at <?= e($activeCert['venue']) ?>.
          </p>

          <div class="cert-meta-grid">
            <div>
              <div class="cert-signature-line">
                <span style="font-family: 'Brush Script MT', cursive, sans-serif; font-size: 1.4rem; color: #1e3a8a;">A. Sterling</span>
              </div>
              <div class="cert-sign-label">Dr. Arthur Sterling</div>
              <small style="font-size: 0.7rem; color: var(--muted);">Director of Student Affairs</small>
            </div>

            <div>
              <div style="height: 35px; display: flex; align-items: center; justify-content: center;">
                <span style="font-family: monospace; font-size: 0.8rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.2rem 0.6rem; border-radius: 4px; border: 1px dashed #b45309;">
                  <?= e($activeCert['certificate_code']) ?>
                </span>
              </div>
              <div class="cert-sign-label">Verification ID</div>
              <small style="font-size: 0.7rem; color: var(--muted);">Issued: <?= date('M d, Y', strtotime($activeCert['issue_date'])) ?></small>
            </div>

            <div>
              <div class="cert-signature-line">
                <span style="font-family: 'Brush Script MT', cursive, sans-serif; font-size: 1.4rem; color: #1e3a8a;"><?= substr(e($activeCert['organizer']), 0, 10) ?></span>
              </div>
              <div class="cert-sign-label"><?= e($activeCert['organizer']) ?></div>
              <small style="font-size: 0.7rem; color: var(--muted);">Event Coordinator</small>
            </div>
          </div>
        </div>

      </div>
    <?php endif; ?>

  </div>
<?php else: ?>
  <div class="dash-card">
    <div class="dash-card-body">
      <div class="empty-state">
        <i class="fa-solid fa-award" style="font-size: 3rem; color: #cbd5e1;"></i>
        <h4>No Certificates Issued Yet</h4>
        <p>Participate in campus competitions, coding duels, and workshops to receive your certified credentials.</p>
        <a href="<?= $baseUrl ?>student/events.php" class="btn btn-primary btn-sm">Explore Upcoming Events</a>
      </div>
    </div>
  </div>
<?php endif; ?>

    </div> <!-- /dashboard-content -->
  </div> <!-- /dashboard-main -->
</div> <!-- /dashboard-layout -->

<script src="<?= $baseUrl ?>assets/js/main.js"></script>
</body>
</html>
