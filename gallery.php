<?php
/**
 * CampusEvents - Public Gallery
 */
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

// Fetch approved gallery photos
$stmt = $pdo->prepare("
    SELECT g.*, e.title as event_title, e.category, e.event_date
    FROM gallery g
    JOIN events e ON e.id = g.event_id
    WHERE g.status = 'approved'
    ORDER BY g.created_at DESC
");
$stmt->execute();
$photos = $stmt->fetchAll();

$categories = ['All', 'Technical', 'Cultural', 'Sports', 'Workshop', 'Arts', 'Competition', 'College Festival'];

$pageTitle = 'Campus Gallery';
$extraScripts = ['assets/js/gallery.js'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div style="background: linear-gradient(180deg, rgba(37, 99, 235, 0.05) 0%, transparent 100%); padding: 3.5rem 0 2rem;">
  <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
    <div>
      <span class="badge badge-primary" style="margin-bottom: 0.5rem;">Memories & Highlights</span>
      <h1 style="font-size: 2.75rem; font-weight: 850; color: var(--primary-dark); margin-bottom: 0.5rem;">Campus Event Gallery</h1>
      <p style="color: var(--muted); font-size: 1.05rem;">Relive the most exciting moments captured during university events.</p>
    </div>

    <div>
      <?php if (isLoggedIn() && isStudent()): ?>
        <a href="<?= $baseUrl ?>student/upload-photo.php" class="btn btn-primary">
          <i class="fa-solid fa-cloud-arrow-up"></i> Upload Event Photos
        </a>
      <?php else: ?>
        <a href="<?= $baseUrl ?>auth/login.php?redirect=student/upload-photo.php" class="btn btn-primary">
          <i class="fa-solid fa-cloud-arrow-up"></i> Submit Photos
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<section class="section" style="padding-top: 2rem;">
  <div class="container">
    
    <!-- Filter Pills -->
    <div class="filter-wrapper">
      <div class="gallery-pills category-pills">
        <?php foreach ($categories as $cat): ?>
          <button type="button" class="pill-btn <?= $cat === 'All' ? 'active' : '' ?>" data-category="<?= strtolower($cat) ?>">
            <?= e($cat) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Gallery Grid -->
    <div class="gallery-grid">
      <?php if (!empty($photos)): ?>
        <?php foreach ($photos as $idx => $p): ?>
          <div class="gallery-item" 
               data-full-img="<?= $baseUrl . e($p['image_url']) ?>" 
               data-caption="<?= e($p['caption']) ?>"
               data-meta="<?= e($p['event_title']) ?> • <?= date('M Y', strtotime($p['event_date'])) ?>"
               data-category="<?= strtolower(e($p['category'])) ?>">
            <img src="<?= $baseUrl . e($p['image_url']) ?>" alt="<?= e($p['caption']) ?>" loading="lazy">
            <div class="gallery-overlay">
              <div class="gallery-caption"><?= e($p['caption']) ?></div>
              <div class="gallery-meta"><?= e($p['event_title']) ?> • <?= e($p['category']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state" style="grid-column: 1 / -1;">
          <i class="fa-regular fa-image"></i>
          <h4>No Photos in Gallery Yet</h4>
          <p>Be the first to upload and share your campus memories!</p>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<!-- Lightbox Modal -->
<div class="lightbox-modal" id="gallery-lightbox-modal">
  <div class="lightbox-content">
    <button type="button" class="lightbox-close" aria-label="Close lightbox">&times;</button>
    <button type="button" class="lightbox-prev" aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></button>
    <button type="button" class="lightbox-next" aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></button>

    <div class="lightbox-img-wrapper">
      <img id="lightbox-image" src="" alt="Lightbox full view">
    </div>
    <div class="lightbox-caption" id="lightbox-caption"></div>
    <div class="gallery-meta" id="lightbox-meta" style="color: #94a3b8; font-size: 0.9rem;"></div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
