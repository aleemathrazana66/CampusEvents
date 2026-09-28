<?php
/**
 * CampusEvents - Student Photo Upload
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student = getCurrentUser();
$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$studentPk = (int)$student['student_id_pk'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $caption = trim($_POST['caption'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security validation failed. Please try again.';
    } elseif ($eventId <= 0 || empty($caption)) {
        $error = 'Please select an event and provide a descriptive caption.';
    } elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid photograph to upload.';
    } else {
        $file = $_FILES['photo'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($file['size'] > $maxSize) {
            $error = 'The selected image exceeds the maximum permitted size of 5MB.';
        } elseif (!in_array($mime, $allowedMimes)) {
            $error = 'Invalid image format. Only JPEG, PNG, and WebP images are allowed.';
        } else {
            // Generate safe filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
                $ext = 'jpg';
            }
            $safeName = 'photo_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $uploadDir = dirname(__DIR__) . '/uploads/gallery/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $destPath = $uploadDir . $safeName;
            $relativeUrl = 'uploads/gallery/' . $safeName;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $stmt = $pdo->prepare("
                    INSERT INTO gallery (event_id, student_id, image_url, caption, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$eventId, $studentPk, $relativeUrl, $caption]);

                // Notify admin
                $stmtAdmins = $pdo->query("SELECT user_id FROM admins");
                foreach ($stmtAdmins->fetchAll() as $ad) {
                    addNotification($ad['user_id'], 'New Photo Awaiting Moderation', "{$student['full_name']} uploaded a photo for event moderation.", 'warning', 'admin/gallery.php');
                }

                setFlashMessage('success', 'Photo uploaded successfully! It is now pending approval by the event coordinator.');
                header("Location: {$baseUrl}student/upload-photo.php");
                exit;
            } else {
                $error = 'Failed to save photograph on server. Please try again.';
            }
        }
    }
}

// Fetch events list for dropdown
$eventsList = $pdo->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();

// Fetch student's uploaded photos
$stmtMyPhotos = $pdo->prepare("
    SELECT g.*, e.title as event_title
    FROM gallery g
    JOIN events e ON e.id = g.event_id
    WHERE g.student_id = ?
    ORDER BY g.created_at DESC
");
$stmtMyPhotos->execute([$studentPk]);
$myPhotos = $stmtMyPhotos->fetchAll();

$pageTitle = 'Upload Event Photos';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/student-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Share Event Photos</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Upload candid snaps and moments from university events to be featured in the official gallery.</p>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem; align-items: flex-start;">
  
  <!-- Upload Form Card -->
  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-solid fa-cloud-arrow-up" style="color: var(--accent); margin-right: 0.5rem;"></i> New Photo Submission</h3>
    </div>
    <div class="dash-card-body">
      
      <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #dc2626; padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; margin-bottom: 1.25rem;">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form action="<?= $baseUrl ?>student/upload-photo.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
          <label class="form-label" for="event_id">Event *</label>
          <select name="event_id" id="event_id" class="form-control" required>
            <option value="">Select Event...</option>
            <?php foreach ($eventsList as $ev): ?>
              <option value="<?= $ev['id'] ?>"><?= e($ev['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="photo">Select Image * (JPEG, PNG, WebP &bull; Max 5MB)</label>
          <input type="file" name="photo" id="photo" class="form-control" accept="image/jpeg,image/png,image/webp" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="caption">Caption *</label>
          <input type="text" name="caption" id="caption" class="form-control" placeholder="e.g. Hackathon team working on demo presentation" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i class="fa-solid fa-cloud-arrow-up"></i> Upload for Approval
        </button>
      </form>
    </div>
  </div>

  <!-- My Submissions -->
  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-regular fa-images" style="color: var(--accent); margin-right: 0.5rem;"></i> My Photo Submissions (<?= count($myPhotos) ?>)</h3>
    </div>
    <div class="dash-card-body">
      <?php if (!empty($myPhotos)): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1.25rem;">
          <?php foreach ($myPhotos as $p): ?>
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm);">
              <div style="height: 120px; background: var(--primary-navy); position: relative;">
                <img src="<?= $baseUrl . e($p['image_url']) ?>" alt="<?= e($p['caption']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <span class="status-pill status-<?= e($p['status']) ?>" style="position: absolute; top: 0.5rem; right: 0.5rem; font-size: 0.65rem;">
                  <?= ucfirst(e($p['status'])) ?>
                </span>
              </div>
              <div style="padding: 0.75rem;">
                <div style="font-weight: 700; font-size: 0.825rem; color: var(--primary-dark); margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= e($p['caption']) ?>">
                  <?= e($p['caption']) ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                  <?= e($p['event_title']) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state" style="padding: 2.5rem 1rem;">
          <i class="fa-regular fa-images"></i>
          <h4>No Photos Uploaded</h4>
          <p>Share photos from recent campus events you've attended.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

    </div> <!-- /dashboard-content -->
  </div> <!-- /dashboard-main -->
</div> <!-- /dashboard-layout -->

<script src="<?= $baseUrl ?>assets/js/main.js"></script>
</body>
</html>
