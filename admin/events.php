<?php
/**
 * CampusEvents - Admin Event Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$action = $_GET['action'] ?? 'list';
$eventId = (int)($_GET['id'] ?? 0);

$errors = [];
$eventData = [
    'title' => '',
    'category' => 'Technical',
    'description' => '',
    'rules' => '',
    'eligibility' => 'Open to all registered university students',
    'event_date' => date('Y-m-d', strtotime('+7 days')),
    'event_time' => '10:00:00',
    'venue' => '',
    'organizer' => '',
    'contact_info' => 'events@campusevents.edu',
    'max_participants' => 100,
    'registration_deadline' => date('Y-m-d H:i:s', strtotime('+6 days 23:59:59')),
    'is_published' => 1,
    'banner_image' => 'assets/images/event-tech.jpg'
];

// Handle DELETE
if ($action === 'delete' && $eventId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        setFlashMessage('success', 'Event successfully removed from the system.');
    } else {
        setFlashMessage('error', 'Invalid security token.');
    }
    header("Location: {$baseUrl}admin/events.php");
    exit;
}

// Handle TOGGLE PUBLISH
if ($action === 'toggle_publish' && $eventId > 0) {
    $stmt = $pdo->prepare("UPDATE events SET is_published = 1 - is_published WHERE id = ?");
    $stmt->execute([$eventId]);
    setFlashMessage('info', 'Event publication status updated.');
    header("Location: {$baseUrl}admin/events.php");
    exit;
}

// Handle EDIT LOAD
if ($action === 'edit' && $eventId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $eventData = $existing;
    } else {
        header("Location: {$baseUrl}admin/events.php");
        exit;
    }
}

// Handle SAVE (Create or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create', 'edit'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Security validation failed. Please try again.';
    }

    $eventData['title'] = trim($_POST['title'] ?? '');
    $eventData['category'] = $_POST['category'] ?? 'Technical';
    $eventData['description'] = trim($_POST['description'] ?? '');
    $eventData['rules'] = trim($_POST['rules'] ?? '');
    $eventData['eligibility'] = trim($_POST['eligibility'] ?? '');
    $eventData['event_date'] = $_POST['event_date'] ?? '';
    $eventData['event_time'] = $_POST['event_time'] ?? '';
    $eventData['venue'] = trim($_POST['venue'] ?? '');
    $eventData['organizer'] = trim($_POST['organizer'] ?? '');
    $eventData['contact_info'] = trim($_POST['contact_info'] ?? '');
    $eventData['max_participants'] = max(1, (int)($_POST['max_participants'] ?? 100));
    $eventData['registration_deadline'] = $_POST['registration_deadline'] ?? '';
    $eventData['is_published'] = isset($_POST['is_published']) ? 1 : 0;

    if (empty($eventData['title'])) $errors[] = 'Event title is required.';
    if (empty($eventData['description'])) $errors[] = 'Event description is required.';
    if (empty($eventData['event_date'])) $errors[] = 'Event date is required.';
    if (empty($eventData['event_time'])) $errors[] = 'Event time is required.';
    if (empty($eventData['venue'])) $errors[] = 'Venue is required.';
    if (empty($eventData['organizer'])) $errors[] = 'Organizer name is required.';

    // Handle Banner Upload if provided
    if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] === UPLOAD_ERR_OK) {
        $bannerFile = $_FILES['banner_file'];
        $ext = pathinfo($bannerFile['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
            $bannerName = 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $uploadDir = dirname(__DIR__) . '/uploads/events/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($bannerFile['tmp_name'], $uploadDir . $bannerName)) {
                $eventData['banner_image'] = 'uploads/events/' . $bannerName;
            }
        }
    }

    if (empty($errors)) {
        // Slug generation
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $eventData['title'])));
        $slug = $baseSlug;

        if ($action === 'create') {
            // Check uniqueness of slug
            $check = $pdo->prepare("SELECT id FROM events WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) {
                $slug = $baseSlug . '-' . time();
            }

            $stmt = $pdo->prepare("
                INSERT INTO events (title, slug, category, description, rules, eligibility, event_date, event_time, venue, organizer, contact_info, max_participants, registration_deadline, is_published, banner_image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $eventData['title'], $slug, $eventData['category'], $eventData['description'], $eventData['rules'],
                $eventData['eligibility'], $eventData['event_date'], $eventData['event_time'], $eventData['venue'],
                $eventData['organizer'], $eventData['contact_info'], $eventData['max_participants'],
                $eventData['registration_deadline'], $eventData['is_published'], $eventData['banner_image']
            ]);

            setFlashMessage('success', "Event '{$eventData['title']}' created successfully!");
            header("Location: {$baseUrl}admin/events.php");
            exit;
        } else {
            // Edit
            $stmt = $pdo->prepare("
                UPDATE events 
                SET title = ?, category = ?, description = ?, rules = ?, eligibility = ?, event_date = ?, event_time = ?, venue = ?, organizer = ?, contact_info = ?, max_participants = ?, registration_deadline = ?, is_published = ?, banner_image = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $eventData['title'], $eventData['category'], $eventData['description'], $eventData['rules'],
                $eventData['eligibility'], $eventData['event_date'], $eventData['event_time'], $eventData['venue'],
                $eventData['organizer'], $eventData['contact_info'], $eventData['max_participants'],
                $eventData['registration_deadline'], $eventData['is_published'], $eventData['banner_image'],
                $eventId
            ]);

            setFlashMessage('success', "Event updated successfully!");
            header("Location: {$baseUrl}admin/events.php");
            exit;
        }
    }
}

// Fetch all events for list view
$stmtList = $pdo->query("
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status != 'cancelled') as registered_count
    FROM events e
    ORDER BY e.event_date DESC
");
$allEvents = $stmtList->fetchAll();

$categories = ['Technical', 'Cultural', 'Sports', 'Arts', 'Workshop', 'Seminar', 'Competition', 'College Festival'];

$pageTitle = 'Event Management';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Event Management</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Create, edit, publish, and schedule campus fests and workshops.</p>
  </div>

  <?php if ($action === 'list'): ?>
    <a href="<?= $baseUrl ?>admin/events.php?action=create" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus"></i> Create New Event
    </a>
  <?php else: ?>
    <a href="<?= $baseUrl ?>admin/events.php" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Back to Event List
    </a>
  <?php endif; ?>
</div>

<?php if ($action === 'create' || $action === 'edit'): ?>
  <!-- Create / Edit Form Card -->
  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-solid fa-calendar-plus" style="color: var(--accent); margin-right: 0.5rem;"></i> <?= $action === 'create' ? 'Create New Event' : 'Edit Event: ' . e($eventData['title']) ?></h3>
    </div>
    <div class="dash-card-body">
      
      <?php if (!empty($errors)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #dc2626; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
          <ul style="padding-left: 1.25rem; margin: 0;">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form action="<?= $baseUrl ?>admin/events.php?action=<?= $action ?><?= $eventId > 0 ? '&id=' . $eventId : '' ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
          <div class="form-group">
            <label class="form-label" for="title">Event Title *</label>
            <input type="text" name="title" id="title" class="form-control" value="<?= e($eventData['title']) ?>" placeholder="e.g. InnovateX 2026 Hackathon" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="category">Category *</label>
            <select name="category" id="category" class="form-control" required>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat ?>" <?= $eventData['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="description">Event Description *</label>
          <textarea name="description" id="description" class="form-control" rows="4" placeholder="Detailed overview of the event, objectives, and highlights..." required><?= e($eventData['description']) ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem;">
          <div class="form-group">
            <label class="form-label" for="event_date">Event Date *</label>
            <input type="date" name="event_date" id="event_date" class="form-control" value="<?= e($eventData['event_date']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="event_time">Event Start Time *</label>
            <input type="time" name="event_time" id="event_time" class="form-control" value="<?= e($eventData['event_time']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="venue">Campus Venue *</label>
            <input type="text" name="venue" id="venue" class="form-control" value="<?= e($eventData['venue']) ?>" placeholder="e.g. Tech Auditorium" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem;">
          <div class="form-group">
            <label class="form-label" for="organizer">Organizing Committee / Club *</label>
            <input type="text" name="organizer" id="organizer" class="form-control" value="<?= e($eventData['organizer']) ?>" placeholder="e.g. Computer Science Society" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="max_participants">Max Participants Capacity *</label>
            <input type="number" name="max_participants" id="max_participants" class="form-control" value="<?= (int)$eventData['max_participants'] ?>" min="1" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="registration_deadline">Registration Deadline *</label>
            <input type="datetime-local" name="registration_deadline" id="registration_deadline" class="form-control" 
                   value="<?= date('Y-m-d\TH:i', strtotime($eventData['registration_deadline'])) ?>" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
          <div class="form-group">
            <label class="form-label" for="rules">Rules & Instructions</label>
            <textarea name="rules" id="rules" class="form-control" rows="3" placeholder="1. Teams of 2-4 members..."><?= e($eventData['rules']) ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label" for="eligibility">Eligibility & Prerequisites</label>
            <textarea name="eligibility" id="eligibility" class="form-control" rows="3" placeholder="Open to all undergraduate students"><?= e($eventData['eligibility']) ?></textarea>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
          <div class="form-group">
            <label class="form-label" for="contact_info">Coordinator Contact Information *</label>
            <input type="text" name="contact_info" id="contact_info" class="form-control" value="<?= e($eventData['contact_info']) ?>" placeholder="email@campusevents.edu | +1 (555) 000-0000" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="banner_file">Upload Custom Banner (Optional)</label>
            <input type="file" name="banner_file" id="banner_file" class="form-control" accept="image/*">
            <small class="form-text">Leave blank to keep existing banner.</small>
          </div>
        </div>

        <div style="margin-bottom: 2rem;">
          <label class="form-check">
            <input type="checkbox" name="is_published" value="1" <?= $eventData['is_published'] ? 'checked' : '' ?>>
            <span>Publish event publicly on the website</span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">
          <i class="fa-solid fa-floppy-disk"></i> <?= $action === 'create' ? 'Create & Launch Event' : 'Save Changes' ?>
        </button>
      </form>
    </div>
  </div>

<?php else: ?>
  <!-- Events List Table -->
  <div class="dash-card">
    <div class="dash-card-body" style="padding: 0;">
      <?php if (!empty($allEvents)): ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Event</th>
                <th>Category</th>
                <th>Event Date</th>
                <th>Venue</th>
                <th>Seats Filled</th>
                <th>Status</th>
                <th>Visibility</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($allEvents as $ev): 
                $evTimestamp = strtotime($ev['event_date'] . ' ' . $ev['event_time']);
                $isUpcoming = $evTimestamp >= time();
                $calcStatus = $isUpcoming ? 'upcoming' : 'completed';
                $available = max(0, $ev['max_participants'] - $ev['registered_count']);
              ?>
                <tr>
                  <td>
                    <a href="<?= $baseUrl ?>event-details.php?id=<?= $ev['id'] ?>" target="_blank" style="font-weight: 700; color: var(--primary-dark);">
                      <?= e($ev['title']) ?>
                    </a>
                    <div style="font-size: 0.75rem; color: var(--muted);"><?= e($ev['organizer']) ?></div>
                  </td>
                  <td><span class="badge badge-primary"><?= e($ev['category']) ?></span></td>
                  <td>
                    <strong><?= date('M j, Y', strtotime($ev['event_date'])) ?></strong><br>
                    <small style="color: var(--muted);"><?= date('g:i A', strtotime($ev['event_time'])) ?></small>
                  </td>
                  <td><?= e($ev['venue']) ?></td>
                  <td>
                    <strong><?= $ev['registered_count'] ?></strong> / <?= $ev['max_participants'] ?>
                  </td>
                  <td>
                    <span class="status-pill status-<?= $calcStatus ?>">
                      <?= ucfirst($calcStatus) ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?= $baseUrl ?>admin/events.php?action=toggle_publish&id=<?= $ev['id'] ?>" 
                       class="badge <?= $ev['is_published'] ? 'badge-success' : 'badge-danger' ?>" 
                       title="Click to toggle visibility">
                      <?= $ev['is_published'] ? 'Published' : 'Hidden' ?>
                    </a>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <a href="<?= $baseUrl ?>admin/events.php?action=edit&id=<?= $ev['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Event">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </a>
                      <a href="<?= $baseUrl ?>admin/registrations.php?event_id=<?= $ev['id'] ?>" class="btn btn-secondary btn-sm" title="View Registrations">
                        <i class="fa-solid fa-users"></i>
                      </a>
                      <button type="button" class="btn btn-danger btn-sm btn-del-event" 
                              data-id="<?= $ev['id'] ?>" data-title="<?= e($ev['title']) ?>" title="Delete Event">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <p>No events found.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="delete-event-modal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title"><i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> Confirm Event Deletion</h3>
      <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
    </div>
    <form action="<?= $baseUrl ?>admin/events.php?action=delete" method="POST" id="form-del-event">
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <p>Are you sure you want to permanently delete <strong id="del-event-name">this event</strong>?</p>
        <p style="font-size: 0.85rem; color: var(--danger); margin-top: 0.5rem;">All associated student registrations, feedback, and gallery photos will also be deleted.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger btn-sm">Yes, Delete Permanently</button>
      </div>
    </form>
  </div>
</div>

    </div> <!-- /dashboard-content -->
  </div> <!-- /dashboard-main -->
</div> <!-- /dashboard-layout -->

<script src="<?= $baseUrl ?>assets/js/main.js"></script>
<script>
document.querySelectorAll('.btn-del-event').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.getAttribute('data-id');
    const title = btn.getAttribute('data-title');
    document.getElementById('del-event-name').textContent = title;
    document.getElementById('form-del-event').action = '<?= $baseUrl ?>admin/events.php?action=delete&id=' + id;
    openModal('delete-event-modal');
  });
});
</script>
</body>
</html>
