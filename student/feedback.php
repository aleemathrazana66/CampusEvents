<?php
/**
 * CampusEvents - Student Feedback Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student = getCurrentUser();
$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$studentPk = (int)$student['student_id_pk'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrfToken)) {
        setFlashMessage('error', 'Security token invalid. Please reload and try again.');
    } elseif ($eventId <= 0 || empty($comment)) {
        setFlashMessage('error', 'Please pick an event and enter your review comments.');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO feedback (event_id, student_id, rating, comment, status)
            VALUES (?, ?, ?, ?, 'approved')
        ");
        $stmt->execute([$eventId, $studentPk, $rating, $comment]);

        setFlashMessage('success', 'Your feedback has been submitted successfully!');
        header("Location: {$baseUrl}student/feedback.php");
        exit;
    }
}

// Fetch events list
$events = $pdo->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();

// Fetch this student's reviews
$stmtMyFeedback = $pdo->prepare("
    SELECT f.*, e.title as event_title
    FROM feedback f
    JOIN events e ON e.id = f.event_id
    WHERE f.student_id = ?
    ORDER BY f.created_at DESC
");
$stmtMyFeedback->execute([$studentPk]);
$myFeedbacks = $stmtMyFeedback->fetchAll();

$pageTitle = 'Give Feedback';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/student-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Event Feedback & Reviews</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Help campus organizers improve future competitions, workshops, and celebrations.</p>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1.1fr 1.9fr; gap: 2rem; align-items: flex-start;">
  
  <!-- Submit Feedback Card -->
  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-solid fa-star-half-stroke" style="color: var(--accent); margin-right: 0.5rem;"></i> Submit Event Review</h3>
    </div>
    <div class="dash-card-body">
      <form action="<?= $baseUrl ?>student/feedback.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
          <label class="form-label" for="event_id">Event *</label>
          <select name="event_id" id="event_id" class="form-control" required>
            <option value="">Select an event...</option>
            <?php foreach ($events as $ev): ?>
              <option value="<?= $ev['id'] ?>"><?= e($ev['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Rating *</label>
          <div style="display: flex; align-items: center; justify-content: space-between;">
            <div class="star-rating">
              <input type="radio" id="s5" name="rating" value="5" checked><label for="s5"><i class="fa-solid fa-star"></i></label>
              <input type="radio" id="s4" name="rating" value="4"><label for="s4"><i class="fa-solid fa-star"></i></label>
              <input type="radio" id="s3" name="rating" value="3"><label for="s3"><i class="fa-solid fa-star"></i></label>
              <input type="radio" id="s2" name="rating" value="2"><label for="s2"><i class="fa-solid fa-star"></i></label>
              <input type="radio" id="s1" name="rating" value="1"><label for="s1"><i class="fa-solid fa-star"></i></label>
            </div>
            <span class="rating-value-display" style="font-weight: 700; color: #d97706; font-size: 0.85rem;">5 Stars</span>
          </div>
        </div>

        <div class="form-group">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
            <label class="form-label" for="comment" style="margin-bottom: 0;">Your Comments *</label>
            <span id="char-num" style="font-size: 0.75rem; color: var(--muted);">0 / 500</span>
          </div>
          <textarea name="comment" id="comment" class="form-control" rows="4" data-max-chars="500" data-counter-target="#char-num" placeholder="What stood out? How was the organization, speaker, or competition flow?" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i class="fa-solid fa-paper-plane"></i> Submit Review
        </button>
      </form>
    </div>
  </div>

  <!-- Feedback History -->
  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-solid fa-comments" style="color: var(--accent); margin-right: 0.5rem;"></i> My Reviews (<?= count($myFeedbacks) ?>)</h3>
    </div>
    <div class="dash-card-body" style="padding: 0;">
      <?php if (!empty($myFeedbacks)): ?>
        <div style="display: flex; flex-direction: column;">
          <?php foreach ($myFeedbacks as $fb): ?>
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-light);">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--primary-dark); margin: 0;"><?= e($fb['event_title']) ?></h4>
                <div style="color: #f59e0b; font-size: 0.8rem;">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-<?= $i <= $fb['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                  <?php endfor; ?>
                </div>
              </div>
              <p style="color: var(--text); font-size: 0.9rem; margin-bottom: 0.5rem; line-height: 1.5;">"<?= e($fb['comment']) ?>"</p>
              <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: var(--muted);">
                <span>Status: <strong style="color: <?= $fb['status'] === 'approved' ? 'var(--success)' : 'var(--warning)' ?>; text-transform: capitalize;"><?= e($fb['status']) ?></strong></span>
                <span>Submitted on <?= date('M j, Y', strtotime($fb['created_at'])) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fa-regular fa-comment-dots"></i>
          <h4>No Reviews Submitted</h4>
          <p>Share your review on recent events you've experienced.</p>
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
