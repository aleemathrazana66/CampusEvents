<?php
/**
 * CampusEvents - Events Directory & Search
 */
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

$selectedCategory = $_GET['category'] ?? 'all';
$selectedTimeframe = $_GET['timeframe'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

// Fetch all published events with registered participant counts
$stmt = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status != 'cancelled') as registered_count
    FROM events e
    WHERE e.is_published = 1
    ORDER BY e.event_date ASC
");
$stmt->execute();
$events = $stmt->fetchAll();

$categories = ['All', 'Technical', 'Cultural', 'Sports', 'Workshop', 'Arts', 'Seminar', 'Competition', 'College Festival'];

$pageTitle = 'Explore Events';
$extraScripts = ['assets/js/events.js'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div style="background: linear-gradient(180deg, rgba(37, 99, 235, 0.05) 0%, transparent 100%); padding: 3.5rem 0 2rem;">
  <div class="container">
    <div style="max-width: 720px; margin: 0 auto; text-align: center;">
      <span class="badge badge-primary" style="margin-bottom: 0.75rem;">Campus Life</span>
      <h1 style="font-size: 2.75rem; font-weight: 850; color: var(--primary-dark); margin-bottom: 0.75rem;">Explore Campus Events</h1>
      <p style="color: var(--muted); font-size: 1.1rem;">Discover competitions, seminars, workshops, and sports matches organized across university clubs.</p>
    </div>
  </div>
</div>

<section class="section" style="padding-top: 2rem;">
  <div class="container">
    
    <!-- Controls: Search & Category Pills & Timeframe -->
    <div class="filter-wrapper">
      <div class="category-pills">
        <?php foreach ($categories as $cat): 
          $catLower = strtolower($cat);
          $isActive = (strtolower($selectedCategory) === $catLower) || ($cat === 'All' && $selectedCategory === 'all');
        ?>
          <button type="button" class="pill-btn <?= $isActive ? 'active' : '' ?>" data-category="<?= $catLower ?>">
            <?= e($cat) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <!-- Upcoming / Past Tabs -->
        <div class="timeframe-tabs" style="display: flex; gap: 0.35rem; background: var(--surface-alt); padding: 0.25rem; border-radius: var(--radius-full);">
          <button type="button" class="pill-btn <?= $selectedTimeframe === 'all' ? 'active' : '' ?>" data-timeframe="all" style="padding: 0.35rem 0.85rem; font-size: 0.8rem;">All</button>
          <button type="button" class="pill-btn <?= $selectedTimeframe === 'upcoming' ? 'active' : '' ?>" data-timeframe="upcoming" style="padding: 0.35rem 0.85rem; font-size: 0.8rem;">Upcoming</button>
          <button type="button" class="pill-btn <?= $selectedTimeframe === 'past' ? 'active' : '' ?>" data-timeframe="past" style="padding: 0.35rem 0.85rem; font-size: 0.8rem;">Past</button>
        </div>

        <!-- Search Input -->
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="event-search-input" placeholder="Search by event title..." value="<?= e($searchQuery) ?>">
        </div>
      </div>
    </div>

    <!-- Events Grid -->
    <div class="events-grid">
      <?php foreach ($events as $ev): 
        $isUpcoming = (strtotime($ev['event_date'] . ' ' . $ev['event_time']) >= time());
        $timeframe = $isUpcoming ? 'upcoming' : 'past';
        $availableSeats = max(0, $ev['max_participants'] - $ev['registered_count']);
        $evDate = date('M j, Y', strtotime($ev['event_date']));
        $evTime = date('g:i A', strtotime($ev['event_time']));
        $isClosed = (strtotime($ev['registration_deadline']) < time()) || ($availableSeats <= 0);
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
              <i class="fa-regular fa-calendar"></i> <?= $evDate ?> • <?= $evTime ?>
              <?php if (!$isUpcoming): ?>
                <span class="badge badge-navy" style="font-size: 0.65rem; padding: 0.15rem 0.45rem; margin-left: auto;">Completed</span>
              <?php endif; ?>
            </div>

            <h3 class="event-card-title"><?= e($ev['title']) ?></h3>
            <p class="event-card-desc"><?= e($ev['description']) ?></p>

            <div class="event-card-venue">
              <i class="fa-solid fa-location-dot"></i>
              <span><?= e($ev['venue']) ?></span>
            </div>

            <div class="event-card-footer">
              <a href="<?= $baseUrl ?>event-details.php?id=<?= $ev['id'] ?>" class="btn btn-secondary btn-sm">
                View Details
              </a>
              <?php if ($isUpcoming && !$isClosed): ?>
                <a href="<?= $baseUrl ?>event-details.php?id=<?= $ev['id'] ?>#register-section" class="btn btn-primary btn-sm">
                  Register Free
                </a>
              <?php elseif (!$isUpcoming): ?>
                <button class="btn btn-secondary btn-sm" disabled style="opacity: 0.6;">Event Ended</button>
              <?php else: ?>
                <button class="btn btn-secondary btn-sm" disabled style="opacity: 0.6;">Registration Closed</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Empty State -->
    <div class="empty-state" id="events-empty-state" style="display: none;">
      <i class="fa-regular fa-calendar-xmark"></i>
      <h4>No Events Found</h4>
      <p>Try clearing your search query or selecting a different category to see more events.</p>
    </div>

  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
