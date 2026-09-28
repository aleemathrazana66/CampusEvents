<?php
/**
 * CampusEvents - Admin Feedback Moderation
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

// Actions: approve, hide, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fbId = (int)($_POST['feedback_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (verify_csrf_token($csrfToken) && $fbId > 0) {
        if ($action === 'set_status') {
            $newStatus = $_POST['status'] ?? 'approved';
            if (in_array($newStatus, ['approved', 'pending', 'hidden'])) {
                $stmt = $pdo->prepare("UPDATE feedback SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $fbId]);
                setFlashMessage('success', "Feedback status updated to " . ucfirst($newStatus));
            }
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
            $stmt->execute([$fbId]);
            setFlashMessage('success', "Feedback deleted.");
        }
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$filterEvent = (int)($_GET['event_id'] ?? 0);
$filterStatus = $_GET['status'] ?? 'all';

$sql = "
    SELECT f.*, s.full_name, s.student_id, e.title as event_title
    FROM feedback f
    JOIN students s ON s.id = f.student_id
    JOIN events e ON e.id = f.event_id
    WHERE 1=1
";
$params = [];

if ($filterEvent > 0) {
    $sql .= " AND f.event_id = ?";
    $params[] = $filterEvent;
}
if ($filterStatus !== 'all') {
    $sql .= " AND f.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY f.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedbacks = $stmt->fetchAll();

$eventsList = $pdo->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();

$pageTitle = 'Feedback Moderation';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Student Feedback Moderation</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Review, approve, or hide reviews that display publicly on the website.</p>
  </div>
</div>

<!-- Filter controls -->
<div class="dash-card" style="margin-bottom: 1.5rem;">
  <div class="dash-card-body" style="padding: 1.25rem;">
    <form action="<?= $baseUrl ?>admin/feedback.php" method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
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
          <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
          <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="hidden" <?= $filterStatus === 'hidden' ? 'selected' : '' ?>>Hidden</option>
        </select>
      </div>

      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <?php if ($filterEvent > 0 || $filterStatus !== 'all'): ?>
        <a href="<?= $baseUrl ?>admin/feedback.php" class="btn btn-outline btn-sm">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="dash-card">
  <div class="dash-card-body" style="padding: 0;">
    <?php if (!empty($feedbacks)): ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Event</th>
              <th>Rating</th>
              <th>Feedback Comment</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($feedbacks as $fb): ?>
              <tr>
                <td>
                  <strong><?= e($fb['full_name']) ?></strong>
                  <div style="font-size: 0.75rem; color: var(--muted);"><?= e($fb['student_id']) ?></div>
                </td>
                <td><?= e($fb['event_title']) ?></td>
                <td>
                  <div style="color: #f59e0b; font-size: 0.85rem; white-space: nowrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="fa-<?= $i <= $fb['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                    <?php endfor; ?>
                  </div>
                </td>
                <td style="max-width: 320px;">
                  <div style="font-size: 0.875rem; line-height: 1.4;">"<?= e($fb['comment']) ?>"</div>
                  <small style="color: var(--muted-light);"><?= date('M j, Y', strtotime($fb['created_at'])) ?></small>
                </td>
                <td>
                  <span class="status-pill status-<?= e($fb['status']) ?>">
                    <?= ucfirst(e($fb['status'])) ?>
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <?php if ($fb['status'] !== 'approved'): ?>
                      <form action="" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                        <input type="hidden" name="action" value="set_status">
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn btn-primary btn-sm" title="Approve">
                          <i class="fa-solid fa-check"></i> Approve
                        </button>
                      </form>
                    <?php endif; ?>

                    <?php if ($fb['status'] !== 'hidden'): ?>
                      <form action="" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                        <input type="hidden" name="action" value="set_status">
                        <input type="hidden" name="status" value="hidden">
                        <button type="submit" class="btn btn-secondary btn-sm" title="Hide">
                          <i class="fa-solid fa-eye-slash"></i>
                        </button>
                      </form>
                    <?php endif; ?>

                    <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this feedback?');">
                      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                      <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa-regular fa-comment-dots"></i>
        <h4>No Feedback Records Found</h4>
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
