<?php
/**
 * CampusEvents - Feedback & Reviews Page
 */
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$student = getCurrentUser();

$feedbackSubmitted = false;

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn() || !isStudent()) {
        setFlashMessage('error', 'Please log in to submit your feedback.');
        header("Location: {$baseUrl}auth/login.php?redirect=feedback.php");
        exit;
    }

    $eventId = (int)($_POST['event_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrfToken)) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
    } elseif ($eventId <= 0 || empty($comment)) {
        setFlashMessage('error', 'Please select an event and write your thoughts.');
    } elseif ($rating < 1 || $rating > 5) {
        setFlashMessage('error', 'Please provide a rating between 1 and 5 stars.');
    } else {
        try {
            $stmtInsert = $pdo->prepare("
                INSERT INTO feedback (event_id, student_id, rating, comment, status)
                VALUES (?, ?, ?, ?, 'approved')
            ");
            $stmtInsert->execute([$eventId, $student['student_id_pk'], $rating, $comment]);

            // Notify admin
            $stmtAdmins = $pdo->query("SELECT user_id FROM admins");
            foreach ($stmtAdmins->fetchAll() as $ad) {
                addNotification($ad['user_id'], 'New Feedback Submitted', "{$student['full_name']} submitted feedback for an event.", 'info', 'admin/feedback.php');
            }

            setFlashMessage('success', 'Thank you! Your feedback has been published.');
            header("Location: {$baseUrl}feedback.php");
            exit;
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to submit feedback. Please try again.');
        }
    }
}

// Fetch all events for the dropdown
$eventsList = $pdo->query("SELECT id, title FROM events WHERE is_published = 1 ORDER BY event_date DESC")->fetchAll();

// Fetch approved student reviews
$stmtReviews = $pdo->prepare("
    SELECT f.*, s.full_name, s.course, s.department, e.title as event_title
    FROM feedback f
    JOIN students s ON s.id = f.student_id
    JOIN events e ON e.id = f.event_id
    WHERE f.status = 'approved'
    ORDER BY f.created_at DESC
");
$stmtReviews->execute();
$reviews = $stmtReviews->fetchAll();

$pageTitle = 'Student Feedback & Reviews';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div style="background: linear-gradient(180deg, rgba(37, 99, 235, 0.05) 0%, transparent 100%); padding: 3.5rem 0 2rem;">
  <div class="container" style="text-align: center; max-width: 680px;">
    <span class="badge badge-primary" style="margin-bottom: 0.5rem;">Voice of the Campus</span>
    <h1 style="font-size: 2.75rem; font-weight: 850; color: var(--primary-dark); margin-bottom: 0.75rem;">Student Feedback</h1>
    <p style="color: var(--muted); font-size: 1.1rem;">Share your event experience, celebrate great organization, and help us make university life even better.</p>
  </div>
</div>

<div class="container" style="padding-top: 2rem; padding-bottom: 5rem;">
  <div style="display: grid; grid-template-columns: 1.1fr 1.9fr; gap: 3rem; align-items: flex-start;">
    
    <!-- Left Column: Submit Feedback Card -->
    <div style="position: sticky; top: 90px;">
      <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-lg);">
        <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.5rem;">Submit Your Review</h3>
        <p style="font-size: 0.875rem; color: var(--muted); margin-bottom: 1.5rem;">Let us know how your recent event participation went.</p>

        <?php if (!isLoggedIn()): ?>
          <div style="background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: var(--radius-md); padding: 1.5rem; text-align: center;">
            <i class="fa-solid fa-user-lock" style="font-size: 2rem; color: var(--accent); margin-bottom: 0.75rem; display: block;"></i>
            <p style="font-size: 0.95rem; font-weight: 600; color: var(--primary-dark); margin-bottom: 1rem;">Please log in to submit your verified student review.</p>
            <a href="<?= $baseUrl ?>auth/login.php?redirect=feedback.php" class="btn btn-primary btn-sm" style="width: 100%;">
              Log In as Student
            </a>
          </div>
        <?php elseif (!isStudent()): ?>
          <div style="background: var(--surface-alt); border-radius: var(--radius-md); padding: 1.25rem; text-align: center;">
            <p style="font-size: 0.9rem; color: var(--muted);">Admin accounts can moderate reviews from the Admin Portal.</p>
            <a href="<?= $baseUrl ?>admin/feedback.php" class="btn btn-secondary btn-sm" style="margin-top: 0.75rem;">Go to Feedback Moderation</a>
          </div>
        <?php else: ?>
          <form action="<?= $baseUrl ?>feedback.php" method="POST" id="feedback-form">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
              <label class="form-label" for="event_id">Select Event *</label>
              <select name="event_id" id="event_id" class="form-control" required>
                <option value="">Choose an event...</option>
                <?php foreach ($eventsList as $ev): ?>
                  <option value="<?= $ev['id'] ?>"><?= e($ev['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Your Rating *</label>
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="star-rating">
                  <input type="radio" id="star5" name="rating" value="5" checked><label for="star5" title="5 stars"><i class="fa-solid fa-star"></i></label>
                  <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars"><i class="fa-solid fa-star"></i></label>
                  <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars"><i class="fa-solid fa-star"></i></label>
                  <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars"><i class="fa-solid fa-star"></i></label>
                  <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star"><i class="fa-solid fa-star"></i></label>
                </div>
                <span class="rating-value-display" style="font-size: 0.85rem; font-weight: 700; color: #d97706;">5 Stars</span>
              </div>
            </div>

            <div class="form-group">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                <label class="form-label" for="comment" style="margin-bottom: 0;">Feedback Message *</label>
                <span id="char-counter" style="font-size: 0.75rem; color: var(--muted);">0 / 500</span>
              </div>
              <textarea name="comment" id="comment" class="form-control" rows="4" 
                        data-max-chars="500" data-counter-target="#char-counter"
                        placeholder="What did you love? Any suggestions for next year's coordinators?" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
              <i class="fa-solid fa-paper-plane"></i> Submit Feedback
            </button>
          </form>
        <?php endif; ?>

      </div>
    </div>

    <!-- Right Column: List of Approved Reviews -->
    <div>
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark);">Recent Reviews (<?= count($reviews) ?>)</h3>
        <span style="font-size: 0.85rem; color: var(--muted);"><i class="fa-solid fa-circle-check" style="color: var(--success);"></i> Verified Student Reviews</span>
      </div>

      <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <?php if (!empty($reviews)): ?>
          <?php foreach ($reviews as $rev): ?>
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); transition: var(--transition);">
              <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                  <div class="author-avatar" style="width: 42px; height: 42px; font-size: 1rem;">
                    <?= strtoupper(substr($rev['full_name'], 0, 1)) ?>
                  </div>
                  <div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--primary-dark); margin: 0;"><?= e($rev['full_name']) ?></h4>
                    <p style="font-size: 0.8rem; color: var(--muted); margin: 0;"><?= e($rev['course']) ?></p>
                  </div>
                </div>

                <div style="color: #f59e0b; font-size: 0.85rem;">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-<?= $i <= $rev['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                  <?php endfor; ?>
                </div>
              </div>

              <div style="margin-bottom: 0.75rem;">
                <span class="badge badge-primary" style="font-size: 0.7rem; padding: 0.2rem 0.6rem;">
                  <i class="fa-solid fa-calendar-check"></i> <?= e($rev['event_title']) ?>
                </span>
              </div>

              <p style="color: var(--text); font-size: 0.95rem; line-height: 1.6; margin: 0;">"<?= e($rev['comment']) ?>"</p>
              
              <div style="margin-top: 0.75rem; text-align: right; font-size: 0.75rem; color: var(--muted-light);">
                Posted on <?= date('M j, Y', strtotime($rev['created_at'])) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <i class="fa-regular fa-comments"></i>
            <h4>No Reviews Yet</h4>
            <p>Be the first student to review a campus event!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
