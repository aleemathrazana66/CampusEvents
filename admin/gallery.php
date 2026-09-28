<?php
/**
 * CampusEvents - Admin Gallery Moderation
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

// Actions: approve, reject, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $photoId = (int)($_POST['photo_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (verify_csrf_token($csrfToken) && $photoId > 0) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE gallery SET status = 'approved' WHERE id = ?");
            $stmt->execute([$photoId]);

            // Notify student
            $photo = $pdo->query("SELECT student_id, (SELECT user_id FROM students WHERE id = gallery.student_id) as user_id FROM gallery WHERE id = $photoId")->fetch();
            if ($photo && $photo['user_id']) {
                addNotification($photo['user_id'], 'Photo Approved!', 'Your uploaded event photograph has been approved and is now live in the gallery.', 'success', 'gallery.php');
            }
            setFlashMessage('success', 'Photograph approved and published to the public gallery.');
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE gallery SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$photoId]);

            $photo = $pdo->query("SELECT student_id, (SELECT user_id FROM students WHERE id = gallery.student_id) as user_id FROM gallery WHERE id = $photoId")->fetch();
            if ($photo && $photo['user_id']) {
                addNotification($photo['user_id'], 'Photo Rejected', 'Your uploaded photograph could not be approved due to community guidelines.', 'warning', 'student/upload-photo.php');
            }
            setFlashMessage('warning', 'Photograph rejected.');
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $stmt->execute([$photoId]);
            setFlashMessage('success', 'Photograph permanently deleted.');
        }
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$filterEvent = (int)($_GET['event_id'] ?? 0);
$filterStatus = $_GET['status'] ?? 'all';

$sql = "
    SELECT g.*, s.full_name as student_name, s.student_id as student_reg_no, e.title as event_title
    FROM gallery g
    LEFT JOIN students s ON s.id = g.student_id
    JOIN events e ON e.id = g.event_id
    WHERE 1=1
";
$params = [];

if ($filterEvent > 0) {
    $sql .= " AND g.event_id = ?";
    $params[] = $filterEvent;
}
if ($filterStatus !== 'all') {
    $sql .= " AND g.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY g.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$photos = $stmt->fetchAll();

$eventsList = $pdo->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();

$pageTitle = 'Gallery Moderation';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Event Photo Moderation</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Approve, reject, or remove submitted photographs before they appear in the campus gallery.</p>
  </div>
</div>

<!-- Filters -->
<div class="dash-card" style="margin-bottom: 1.5rem;">
  <div class="dash-card-body" style="padding: 1.25rem;">
    <form action="<?= $baseUrl ?>admin/gallery.php" method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
      <div style="flex: 1; min-width: 250px;">
        <select name="event_id" class="form-control" onchange="this.form.submit()">
          <option value="0">All Events</option>
          <?php foreach ($eventsList as $ev): ?>
            <option value="<?= $ev['id'] ?>" <?= $filterEvent === (int)$ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="width: 170px;">
        <select name="status" class="form-control" onchange="this.form.submit()">
          <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
          <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending Review</option>
          <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
          <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <?php if ($filterEvent > 0 || $filterStatus !== 'all'): ?>
        <a href="<?= $baseUrl ?>admin/gallery.php" class="btn btn-outline btn-sm">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Photos Grid -->
<?php if (!empty($photos)): ?>
  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
    <?php foreach ($photos as $p): ?>
      <div class="dash-card" style="margin-bottom: 0;">
        <div style="height: 180px; background: var(--primary-navy); position: relative; overflow: hidden;">
          <img src="<?= $baseUrl . e($p['image_url']) ?>" alt="<?= e($p['caption']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
          <span class="status-pill status-<?= e($p['status']) ?>" style="position: absolute; top: 0.75rem; right: 0.75rem;">
            <?= ucfirst(e($p['status'])) ?>
          </span>
        </div>

        <div style="padding: 1.25rem;">
          <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--primary-dark); margin-bottom: 0.35rem;"><?= e($p['caption']) ?></h4>
          <div style="font-size: 0.8rem; color: var(--muted); margin-bottom: 0.35rem;">
            <i class="fa-regular fa-calendar" style="margin-right: 0.25rem;"></i> <?= e($p['event_title']) ?>
          </div>
          <div style="font-size: 0.75rem; color: var(--muted-light); margin-bottom: 1.25rem;">
            Submitted by: <?= e($p['student_name'] ?? 'Staff/Official') ?> &bull; <?= date('M j, Y', strtotime($p['created_at'])) ?>
          </div>

          <div style="display: flex; gap: 0.5rem;">
            <?php if ($p['status'] !== 'approved'): ?>
              <form action="" method="POST" style="flex: 1;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                  <i class="fa-solid fa-check"></i> Approve
                </button>
              </form>
            <?php endif; ?>

            <?php if ($p['status'] !== 'rejected'): ?>
              <form action="" method="POST" style="flex: 1;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">
                  <i class="fa-solid fa-xmark"></i> Reject
                </button>
              </form>
            <?php endif; ?>

            <form action="" method="POST" onsubmit="return confirm('Delete this photograph?');">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="dash-card">
    <div class="dash-card-body">
      <div class="empty-state">
        <i class="fa-regular fa-images"></i>
        <h4>No Photos Found</h4>
        <p>No photos match the selected filter criteria.</p>
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
