<?php
/**
 * CampusEvents - My Registrations
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student = getCurrentUser();
$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$studentPk = (int)$student['student_id_pk'];

// Fetch all registrations for this student
$stmt = $pdo->prepare("
    SELECT r.id as reg_id, r.registration_date, r.status, r.is_certificate_eligible,
           e.id as event_id, e.title, e.category, e.event_date, e.event_time, e.venue, e.registration_deadline,
           c.certificate_code
    FROM event_registrations r
    JOIN events e ON e.id = r.event_id
    LEFT JOIN certificates c ON c.registration_id = r.id
    WHERE r.student_id = ?
    ORDER BY r.registration_date DESC
");
$stmt->execute([$studentPk]);
$registrations = $stmt->fetchAll();

$pageTitle = 'My Registrations';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/student-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">My Registered Events</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">View your participation passes, attendance, and credentials.</p>
  </div>

  <a href="<?= $baseUrl ?>student/events.php" class="btn btn-primary btn-sm">
    <i class="fa-solid fa-plus"></i> Register for More Events
  </a>
</div>

<div class="dash-card">
  <div class="dash-card-body" style="padding: 0;">
    <?php if (!empty($registrations)): ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Event</th>
              <th>Category</th>
              <th>Date & Time</th>
              <th>Venue</th>
              <th>Registered On</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($registrations as $r): 
              $canCancel = ($r['status'] === 'registered') && (strtotime($r['registration_deadline']) > time());
            ?>
              <tr id="reg-row-<?= $r['reg_id'] ?>">
                <td>
                  <a href="<?= $baseUrl ?>event-details.php?id=<?= $r['event_id'] ?>" style="font-weight: 700; color: var(--primary-dark);">
                    <?= e($r['title']) ?>
                  </a>
                  <?php if (!empty($r['certificate_code'])): ?>
                    <div style="font-size: 0.75rem; color: #059669; margin-top: 0.2rem;">
                      <i class="fa-solid fa-award"></i> Cert: <?= e($r['certificate_code']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td><span class="badge badge-primary"><?= e($r['category']) ?></span></td>
                <td>
                  <strong><?= date('M j, Y', strtotime($r['event_date'])) ?></strong><br>
                  <small style="color: var(--muted);"><?= date('g:i A', strtotime($r['event_time'])) ?></small>
                </td>
                <td><?= e($r['venue']) ?></td>
                <td><?= date('M j, Y', strtotime($r['registration_date'])) ?></td>
                <td>
                  <span class="status-pill status-<?= e($r['status']) ?>" id="status-pill-<?= $r['reg_id'] ?>">
                    <?= ucfirst(e($r['status'])) ?>
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <a href="<?= $baseUrl ?>event-details.php?id=<?= $r['event_id'] ?>" class="btn btn-secondary btn-sm" title="View details">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                    <?php if (!empty($r['certificate_code'])): ?>
                      <a href="<?= $baseUrl ?>student/certificates.php?id=<?= $r['reg_id'] ?>" class="btn btn-primary btn-sm" title="View Certificate">
                        <i class="fa-solid fa-award"></i>
                      </a>
                    <?php endif; ?>
                    <?php if ($canCancel): ?>
                      <button type="button" class="btn btn-danger btn-sm btn-open-cancel" 
                              data-reg-id="<?= $r['reg_id'] ?>" 
                              data-title="<?= e($r['title']) ?>"
                              title="Cancel Registration">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa-solid fa-ticket-simple"></i>
        <h4>No Registrations Yet</h4>
        <p>You haven't signed up for any events yet. Check out the campus event schedule.</p>
        <a href="<?= $baseUrl ?>student/events.php" class="btn btn-primary btn-sm">Explore Events</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Cancellation Confirmation Modal -->
<div class="modal-overlay" id="cancel-reg-modal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title"><i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> Cancel Event Pass</h3>
      <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to cancel your registration for <strong id="modal-cancel-event-title">this event</strong>?</p>
      <p style="font-size: 0.85rem; color: var(--muted); margin-top: 0.5rem;">Your reserved seat will be released back into the public pool.</p>
      <input type="hidden" id="modal-cancel-reg-id">
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Keep My Seat</button>
      <button type="button" class="btn btn-danger btn-sm" id="btn-confirm-cancel">Yes, Cancel Registration</button>
    </div>
  </div>
</div>

    </div> <!-- /dashboard-content -->
  </div> <!-- /dashboard-main -->
</div> <!-- /dashboard-layout -->

<script src="<?= $baseUrl ?>assets/js/main.js"></script>
<script>
document.querySelectorAll('.btn-open-cancel').forEach(btn => {
  btn.addEventListener('click', () => {
    const regId = btn.getAttribute('data-reg-id');
    const title = btn.getAttribute('data-title');
    document.getElementById('modal-cancel-reg-id').value = regId;
    document.getElementById('modal-cancel-event-title').textContent = title;
    openModal('cancel-reg-modal');
  });
});

document.getElementById('btn-confirm-cancel').addEventListener('click', () => {
  const regId = document.getElementById('modal-cancel-reg-id').value;
  const btn = document.getElementById('btn-confirm-cancel');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cancelling...';

  const formData = new FormData();
  formData.append('registration_id', regId);
  formData.append('csrf_token', '<?= csrf_token() ?>');

  fetch('<?= $baseUrl ?>api/cancel-registration.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    closeModal('cancel-reg-modal');
    btn.disabled = false;
    btn.textContent = 'Yes, Cancel Registration';

    if (data.success) {
      showToast('success', 'Cancelled', data.message);
      const pill = document.getElementById('status-pill-' + regId);
      if (pill) {
        pill.className = 'status-pill status-cancelled';
        pill.textContent = 'Cancelled';
      }
      const row = document.getElementById('reg-row-' + regId);
      if (row) {
        const cancelBtn = row.querySelector('.btn-open-cancel');
        if (cancelBtn) cancelBtn.remove();
      }
    } else {
      showToast('error', 'Error', data.message);
    }
  })
  .catch(() => {
    closeModal('cancel-reg-modal');
    btn.disabled = false;
    btn.textContent = 'Yes, Cancel Registration';
    showToast('error', 'Error', 'Failed to communicate with server.');
  });
});
</script>
</body>
</html>
