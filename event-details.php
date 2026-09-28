<?php
/**
 * CampusEvents - Event Details & Registration
 */
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

$eventId = (int)($_GET['id'] ?? 0);
if ($eventId <= 0) {
    header("Location: {$baseUrl}events.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status != 'cancelled') as registered_count
    FROM events e
    WHERE e.id = ? AND e.is_published = 1
    LIMIT 1
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: {$baseUrl}events.php");
    exit;
}

$student = getCurrentUser();
$isUserRegistered = false;
$userRegistration = null;

if ($student && isStudent()) {
    $stmtReg = $pdo->prepare("
        SELECT * FROM event_registrations 
        WHERE event_id = ? AND student_id = ? AND status != 'cancelled'
        LIMIT 1
    ");
    $stmtReg->execute([$eventId, $student['student_id_pk']]);
    $userRegistration = $stmtReg->fetch();
    if ($userRegistration) {
        $isUserRegistered = true;
    }
}

$availableSeats = max(0, $event['max_participants'] - $event['registered_count']);
$isPastDeadline = strtotime($event['registration_deadline']) < time();
$isEventPassed = strtotime($event['event_date'] . ' ' . $event['event_time']) < time();
$isFull = $availableSeats <= 0;

$pageTitle = $event['title'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div style="background: var(--surface); border-bottom: 1px solid var(--border); padding: 1.5rem 0;">
  <div class="container">
    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--muted);">
      <a href="<?= $baseUrl ?>index.php">Home</a>
      <span>/</span>
      <a href="<?= $baseUrl ?>events.php">Events</a>
      <span>/</span>
      <span style="color: var(--text); font-weight: 600;"><?= e($event['title']) ?></span>
    </div>
  </div>
</div>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 5rem;">
  
  <!-- Banner Media -->
  <div style="position: relative; height: 380px; border-radius: var(--radius-xl); overflow: hidden; margin-bottom: 2.5rem; box-shadow: var(--shadow-lg);">
    <img src="<?= $baseUrl . e($event['banner_image'] ?: 'assets/images/event-tech.jpg') ?>" alt="<?= e($event['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
    <div style="position: absolute; inset: 0; background: linear-gradient(180deg, transparent 40%, rgba(11, 19, 43, 0.85) 100%);"></div>
    <div style="position: absolute; bottom: 2rem; left: 2rem; right: 2rem; color: #ffffff;">
      <span class="badge badge-primary" style="margin-bottom: 0.75rem;"><?= e($event['category']) ?></span>
      <h1 style="color: #ffffff; font-size: 2.35rem; font-weight: 800; line-height: 1.25;"><?= e($event['title']) ?></h1>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 3rem; align-items: flex-start;">
    
    <!-- Left Column: Details, Description, Rules -->
    <div>
      
      <!-- Key Meta Pills -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.25rem; display: flex; align-items: center; gap: 1rem;">
          <div style="width: 46px; height: 46px; border-radius: var(--radius-sm); background: rgba(37, 99, 235, 0.1); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
            <i class="fa-regular fa-calendar"></i>
          </div>
          <div>
            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--muted); font-weight: 700;">Date & Time</div>
            <div style="font-weight: 700; color: var(--primary-dark); font-size: 0.95rem;">
              <?= date('M j, Y', strtotime($event['event_date'])) ?> at <?= date('g:i A', strtotime($event['event_time'])) ?>
            </div>
          </div>
        </div>

        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.25rem; display: flex; align-items: center; gap: 1rem;">
          <div style="width: 46px; height: 46px; border-radius: var(--radius-sm); background: rgba(16, 185, 129, 0.1); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
            <i class="fa-solid fa-location-dot"></i>
          </div>
          <div>
            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--muted); font-weight: 700;">Venue</div>
            <div style="font-weight: 700; color: var(--primary-dark); font-size: 0.95rem;">
              <?= e($event['venue']) ?>
            </div>
          </div>
        </div>

        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.25rem; display: flex; align-items: center; gap: 1rem;">
          <div style="width: 46px; height: 46px; border-radius: var(--radius-sm); background: rgba(245, 158, 11, 0.1); color: var(--warning); display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
            <i class="fa-solid fa-user-group"></i>
          </div>
          <div>
            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--muted); font-weight: 700;">Organized By</div>
            <div style="font-weight: 700; color: var(--primary-dark); font-size: 0.95rem;">
              <?= e($event['organizer']) ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2rem;">
        <h3 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 1rem; color: var(--primary-dark);">About This Event</h3>
        <p style="color: var(--text); line-height: 1.7; font-size: 1rem; white-space: pre-line;"><?= e($event['description']) ?></p>
      </div>

      <!-- Rules & Guidelines -->
      <?php if (!empty($event['rules'])): ?>
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2rem;">
          <h3 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 1rem; color: var(--primary-dark);">
            <i class="fa-solid fa-clipboard-list" style="color: var(--accent); margin-right: 0.5rem;"></i> Rules & Guidelines
          </h3>
          <div style="color: var(--text); line-height: 1.7; font-size: 0.95rem; white-space: pre-line;"><?= e($event['rules']) ?></div>
        </div>
      <?php endif; ?>

      <!-- Eligibility & Contact -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem;">
          <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary-dark);">
            <i class="fa-solid fa-graduation-cap" style="color: var(--accent); margin-right: 0.4rem;"></i> Eligibility
          </h4>
          <p style="color: var(--muted); font-size: 0.9rem; line-height: 1.5;"><?= e($event['eligibility']) ?></p>
        </div>

        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem;">
          <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary-dark);">
            <i class="fa-solid fa-phone" style="color: var(--accent); margin-right: 0.4rem;"></i> Contact Information
          </h4>
          <p style="color: var(--muted); font-size: 0.9rem; line-height: 1.5;"><?= e($event['contact_info']) ?></p>
        </div>
      </div>

    </div>

    <!-- Right Column: Registration Card -->
    <div style="position: sticky; top: 90px;" id="register-section">
      <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-lg);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
          <span class="badge badge-success" style="font-size: 0.85rem; padding: 0.35rem 0.85rem;">
            <i class="fa-solid fa-tag"></i> 100% Free
          </span>
          <span style="font-size: 0.85rem; font-weight: 700; color: var(--muted);">
            Registration Fee: $0
          </span>
        </div>

        <div style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-light);">
          <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.5rem;">
            <span style="color: var(--muted); font-weight: 600;">Available Capacity</span>
            <span style="font-weight: 800; color: var(--primary-dark);"><?= $availableSeats ?> / <?= $event['max_participants'] ?> seats</span>
          </div>
          <div style="height: 8px; background: var(--surface-alt); border-radius: var(--radius-full); overflow: hidden;">
            <?php 
              $percentFilled = min(100, round(($event['registered_count'] / max(1, $event['max_participants'])) * 100));
            ?>
            <div style="width: <?= $percentFilled ?>%; height: 100%; background: linear-gradient(90deg, var(--accent) 0%, var(--primary) 100%);"></div>
          </div>
        </div>

        <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--muted); line-height: 1.5;">
          <div><i class="fa-regular fa-clock" style="color: var(--accent); margin-right: 0.4rem;"></i> <strong>Deadline:</strong> <?= date('M j, Y g:i A', strtotime($event['registration_deadline'])) ?></div>
          <div style="margin-top: 0.4rem;"><i class="fa-solid fa-certificate" style="color: var(--success); margin-right: 0.4rem;"></i> <strong>Certificate:</strong> Available upon participation</div>
        </div>

        <!-- Action Button -->
        <?php if ($isUserRegistered): ?>
          <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #059669; padding: 1rem; border-radius: var(--radius-md); text-align: center; margin-bottom: 1rem;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.5rem; margin-bottom: 0.35rem; display: block;"></i>
            <strong>You are registered!</strong>
            <p style="font-size: 0.8rem; margin: 0.25rem 0 0.75rem;">Your campus event entry pass is active.</p>
            <a href="<?= $baseUrl ?>student/registrations.php" class="btn btn-primary btn-sm" style="width: 100%;">
              View In My Registrations
            </a>
          </div>
        <?php elseif ($isEventPassed): ?>
          <button class="btn btn-secondary" style="width: 100%; padding: 0.9rem;" disabled>
            <i class="fa-solid fa-clock-rotate-left"></i> Event Concluded
          </button>
        <?php elseif ($isPastDeadline): ?>
          <button class="btn btn-secondary" style="width: 100%; padding: 0.9rem;" disabled>
            <i class="fa-solid fa-calendar-xmark"></i> Registration Closed
          </button>
        <?php elseif ($isFull): ?>
          <button class="btn btn-secondary" style="width: 100%; padding: 0.9rem;" disabled>
            <i class="fa-solid fa-users-slash"></i> Event At Capacity
          </button>
        <?php elseif (!isLoggedIn()): ?>
          <a href="<?= $baseUrl ?>auth/login.php?redirect=<?= urlencode('event-details.php?id=' . $event['id']) ?>" class="btn btn-primary" style="width: 100%; padding: 0.9rem; font-size: 1.05rem;">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Login to Register Free
          </a>
          <p style="text-align: center; font-size: 0.8rem; color: var(--muted); margin-top: 0.65rem;">
            New student? <a href="<?= $baseUrl ?>auth/register.php" style="font-weight: 700;">Sign up</a> in 30 seconds.
          </p>
        <?php elseif (isAdmin()): ?>
          <a href="<?= $baseUrl ?>admin/events.php?action=edit&id=<?= $event['id'] ?>" class="btn btn-secondary" style="width: 100%;">
            <i class="fa-solid fa-pen-to-square"></i> Edit Event (Admin)
          </a>
        <?php else: ?>
          <!-- Logged in Student Register Button -->
          <button type="button" id="btn-register-event" class="btn btn-primary" style="width: 100%; padding: 0.9rem; font-size: 1.05rem;">
            <i class="fa-solid fa-ticket"></i> Register for Event (Free)
          </button>
        <?php endif; ?>

        <div style="margin-top: 1.5rem; text-align: center;">
          <p style="font-size: 0.75rem; color: var(--muted); display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
            <i class="fa-solid fa-shield-halved" style="color: var(--success);"></i> Guaranteed Free • No Hidden Fees
          </p>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const regBtn = document.getElementById('btn-register-event');
  if (regBtn) {
    regBtn.addEventListener('click', () => {
      regBtn.disabled = true;
      regBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

      const formData = new FormData();
      formData.append('event_id', '<?= $event['id'] ?>');
      formData.append('csrf_token', '<?= csrf_token() ?>');

      fetch('<?= $baseUrl ?>api/register-event.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('success', 'Success!', data.message);
          setTimeout(() => window.location.reload(), 1200);
        } else {
          showToast('error', 'Registration Notice', data.message);
          regBtn.disabled = false;
          regBtn.innerHTML = '<i class="fa-solid fa-ticket"></i> Register for Event (Free)';
        }
      })
      .catch(() => {
        showToast('error', 'Error', 'Failed to communicate with the server. Please try again.');
        regBtn.disabled = false;
        regBtn.innerHTML = '<i class="fa-solid fa-ticket"></i> Register for Event (Free)';
      });
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
