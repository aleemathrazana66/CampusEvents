<?php
/**
 * CampusEvents - Student Events Catalog
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student = getCurrentUser();
$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$studentPk = (int)$student['student_id_pk'];

// Fetch all published events with current student's registration status
$stmt = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status != 'cancelled') as registered_count,
           (SELECT id FROM event_registrations WHERE event_id = e.id AND student_id = ? AND status != 'cancelled' LIMIT 1) as user_reg_id
    FROM events e
    WHERE e.is_published = 1
    ORDER BY e.event_date ASC
");
$stmt->execute([$studentPk]);
$events = $stmt->fetchAll();

$categories = ['All', 'Technical', 'Cultural', 'Sports', 'Workshop', 'Arts', 'Seminar', 'Competition', 'College Festival'];

$pageTitle = 'Browse Events';
$isDashboardPage = true;
$extraScripts = ['assets/js/events.js'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/student-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Available Campus Events</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Register for upcoming competitions, fests, and workshops for free.</p>
  </div>
</div>

<!-- Filters -->
<div class="filter-wrapper" style="margin-bottom: 2rem;">
  <div class="category-pills">
    <?php foreach ($categories as $cat): ?>
      <button type="button" class="pill-btn <?= $cat === 'All' ? 'active' : '' ?>" data-category="<?= strtolower($cat) ?>">
        <?= e($cat) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
    <div class="timeframe-tabs" style="display: flex; gap: 0.35rem; background: var(--surface-alt); padding: 0.25rem; border-radius: var(--radius-full);">
      <button type="button" class="pill-btn active" data-timeframe="all" style="padding: 0.35rem 0.85rem; font-size: 0.8rem;">All</button>
      <button type="button" class="pill-btn" data-timeframe="upcoming" style="padding: 0.35rem 0.85rem; font-size: 0.8rem;">Upcoming</button>
      <button type="button" class="pill-btn" data-timeframe="past" style="padding: 0.35rem 0.85rem; font-size: 0.8rem;">Past</button>
    </div>

    <div class="search-box">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="event-search-input" placeholder="Search events by title...">
    </div>
  </div>
</div>

<!-- Events Grid -->
<div class="events-grid">
  <?php foreach ($events as $ev): 
    $isUpcoming = (strtotime($ev['event_date'] . ' ' . $ev['event_time']) >= time());
    $timeframe = $isUpcoming ? 'upcoming' : 'past';
    $availableSeats = max(0, $ev['max_participants'] - $ev['registered_count']);
    $isRegistered = !empty($ev['user_reg_id']);
    $isPastDeadline = strtotime($ev['registration_deadline']) < time();
    $isClosed = $isPastDeadline || ($availableSeats <= 0);
  ?>
    <div class="event-card" 
         data-title="<?= strtolower(e($ev['title'])) ?>"
         data-category="<?= strtolower(e($ev['category'])) ?>"
         data-timeframe="<?= $timeframe ?>">
      
      <div class="event-card-media">
        <img src="<?= $baseUrl . e($ev['banner_image'] ?: 'assets/images/event-tech.jpg') ?>" alt="<?= e($ev['title']) ?>" loading="lazy">
        <span class="badge badge-primary event-badge-cat"><?= e($ev['category']) ?></span>
        <span class="event-badge-seats">
          <i class="fa-solid fa-chair"></i> <?= $availableSeats ?> seats left
        </span>
      </div>

      <div class="event-card-body">
        <div class="event-card-date">
          <i class="fa-regular fa-calendar"></i> <?= date('M j, Y', strtotime($ev['event_date'])) ?> • <?= date('g:i A', strtotime($ev['event_time'])) ?>
        </div>

        <h3 class="event-card-title"><?= e($ev['title']) ?></h3>
        <p class="event-card-desc"><?= e($ev['description']) ?></p>

        <div class="event-card-venue">
          <i class="fa-solid fa-location-dot"></i>
          <span><?= e($ev['venue']) ?></span>
        </div>

        <div class="event-card-footer">
          <a href="<?= $baseUrl ?>event-details.php?id=<?= $ev['id'] ?>" class="btn btn-secondary btn-sm">
            Details
          </a>

          <?php if ($isRegistered): ?>
            <button class="btn btn-secondary btn-sm" disabled style="color: var(--success); border-color: rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.08);">
              <i class="fa-solid fa-circle-check"></i> Registered
            </button>
          <?php elseif (!$isUpcoming): ?>
            <button class="btn btn-secondary btn-sm" disabled style="opacity: 0.6;">Ended</button>
          <?php elseif ($isClosed): ?>
            <button class="btn btn-secondary btn-sm" disabled style="opacity: 0.6;">Closed</button>
          <?php else: ?>
            <button type="button" class="btn btn-primary btn-sm btn-quick-reg" data-event-id="<?= $ev['id'] ?>">
              Register Free
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="empty-state" id="events-empty-state" style="display: none;">
  <i class="fa-regular fa-calendar-xmark"></i>
  <h4>No Events Found</h4>
  <p>Try matching other keywords or clearing filters.</p>
</div>

    </div> <!-- /dashboard-content -->
  </div> <!-- /dashboard-main -->
</div> <!-- /dashboard-layout -->

<script src="<?= $baseUrl ?>assets/js/main.js"></script>
<script src="<?= $baseUrl ?>assets/js/events.js"></script>
<script>
document.querySelectorAll('.btn-quick-reg').forEach(btn => {
  btn.addEventListener('click', () => {
    const eventId = btn.getAttribute('data-event-id');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    const formData = new FormData();
    formData.append('event_id', eventId);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    fetch('<?= $baseUrl ?>api/register-event.php', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('success', 'Registered!', data.message);
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Registered';
        btn.className = 'btn btn-secondary btn-sm';
        btn.style.color = 'var(--success)';
      } else {
        showToast('error', 'Notice', data.message);
        btn.disabled = false;
        btn.innerHTML = 'Register Free';
      }
    })
    .catch(() => {
      showToast('error', 'Error', 'Failed to submit registration.');
      btn.disabled = false;
      btn.innerHTML = 'Register Free';
    });
  });
});
</script>
</body>
</html>
