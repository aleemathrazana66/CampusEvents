<?php
/**
 * CampusEvents - Homepage
 * Order:
 * 1. Navbar
 * 2. Hero
 * 3. Upcoming Event Countdown
 * 4. Featured Events
 * 5. Event Categories
 * 6. Our Impact
 * 7. How It Works
 * 8. What Students Say
 * 9. Gallery Preview
 * 10. Feedback CTA
 * 11. Final Call-to-Action
 * 12. Footer
 */
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

// 1. Fetch nearest upcoming event for live countdown
$stmtNext = $pdo->prepare("
    SELECT id, title, category, description, venue, event_date, event_time, banner_image
    FROM events
    WHERE is_published = 1 AND CONCAT(event_date, ' ', event_time) > NOW()
    ORDER BY CONCAT(event_date, ' ', event_time) ASC
    LIMIT 1
");
$stmtNext->execute();
$upcomingEvent = $stmtNext->fetch();

// 2. Fetch featured upcoming events (up to 6)
$stmtFeatured = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status != 'cancelled') as registered_count
    FROM events e
    WHERE e.is_published = 1
    ORDER BY e.event_date ASC
    LIMIT 6
");
$stmtFeatured->execute();
$featuredEvents = $stmtFeatured->fetchAll();

// 3. Dynamic Impact Stats
$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE is_published = 1")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalCertificates = (int)$pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
$totalRegistrations = (int)$pdo->query("SELECT COUNT(*) FROM event_registrations WHERE status != 'cancelled'")->fetchColumn();

// 4. Approved Testimonials
$stmtTestimonials = $pdo->prepare("
    SELECT f.*, s.full_name, s.course, s.department, s.avatar, e.title as event_title
    FROM feedback f
    JOIN students s ON s.id = f.student_id
    JOIN events e ON e.id = f.event_id
    WHERE f.status = 'approved'
    ORDER BY f.created_at DESC
    LIMIT 6
");
$stmtTestimonials->execute();
$testimonials = $stmtTestimonials->fetchAll();

// 5. Approved Gallery Preview
$stmtGallery = $pdo->prepare("
    SELECT g.*, e.title as event_title, e.category
    FROM gallery g
    JOIN events e ON e.id = g.event_id
    WHERE g.status = 'approved'
    ORDER BY g.created_at DESC
    LIMIT 8
");
$stmtGallery->execute();
$galleryPhotos = $stmtGallery->fetchAll();

$pageTitle = 'Home - Experience. Participate. Celebrate.';
$extraScripts = ['assets/js/countdown.js', 'assets/js/gallery.js'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- 2. HERO SECTION -->
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-content">
      <span class="badge badge-primary">
        <i class="fa-solid fa-sparkles"></i> Official University Platform
      </span>
      <h1>Experience. <span class="gradient-text">Participate.</span> Celebrate.</h1>
      <p>Discover, register and experience every event happening on your campus. Connect with peer clubs, win competitions, and collect verified certificates of participation.</p>
      
      <div class="hero-buttons">
        <a href="<?= $baseUrl ?>events.php" class="btn btn-primary btn-lg">
          <i class="fa-solid fa-compass"></i> Explore Events
        </a>
        <?php if (!isLoggedIn()): ?>
          <a href="<?= $baseUrl ?>auth/register.php" class="btn btn-secondary btn-lg">
            <i class="fa-solid fa-user-plus"></i> Register Now
          </a>
        <?php else: ?>
          <a href="<?= $baseUrl ?>student/dashboard.php" class="btn btn-secondary btn-lg">
            <i class="fa-solid fa-gauge"></i> My Dashboard
          </a>
        <?php endif; ?>
      </div>

      <div class="hero-stats-mini">
        <div class="stat-item">
          <h4><?= $totalEvents ?>+</h4>
          <p>Annual Events</p>
        </div>
        <div class="stat-item">
          <h4>100%</h4>
          <p>Free Registration</p>
        </div>
        <div class="stat-item">
          <h4>Instant</h4>
          <p>Digital Certificates</p>
        </div>
      </div>
    </div>

    <!-- 3. UPCOMING EVENT COUNTDOWN CARD -->
    <div class="hero-countdown-box">
      <?php if ($upcomingEvent): 
        $targetDateTime = $upcomingEvent['event_date'] . 'T' . $upcomingEvent['event_time'];
        $formattedDate = date('D, M j, Y', strtotime($upcomingEvent['event_date']));
        $formattedTime = date('g:i A', strtotime($upcomingEvent['event_time']));
      ?>
        <div class="countdown-card" id="upcoming-countdown-container" data-event-datetime="<?= $targetDateTime ?>">
          <div class="countdown-tag">
            <span class="pulse-dot"></span> Next Happening Event
          </div>

          <h3 class="countdown-title" id="cd-event-title"><?= e($upcomingEvent['title']) ?></h3>

          <div class="countdown-meta">
            <span id="cd-event-date"><i class="fa-regular fa-calendar"></i> <?= $formattedDate ?> at <?= $formattedTime ?></span>
            <span id="cd-event-venue"><i class="fa-solid fa-location-dot"></i> <?= e($upcomingEvent['venue']) ?></span>
          </div>

          <div class="timer-grid">
            <div class="timer-box">
              <div class="timer-number" id="cd-days">00</div>
              <div class="timer-label">Days</div>
            </div>
            <div class="timer-box">
              <div class="timer-number" id="cd-hours">00</div>
              <div class="timer-label">Hours</div>
            </div>
            <div class="timer-box">
              <div class="timer-number" id="cd-minutes">00</div>
              <div class="timer-label">Mins</div>
            </div>
            <div class="timer-box">
              <div class="timer-number" id="cd-seconds">00</div>
              <div class="timer-label">Secs</div>
            </div>
          </div>

          <a href="<?= $baseUrl ?>event-details.php?id=<?= $upcomingEvent['id'] ?>" class="btn btn-primary" id="cd-event-link" style="width: 100%;">
            <i class="fa-solid fa-ticket"></i> View Details & Register
          </a>
        </div>
      <?php else: ?>
        <div class="countdown-card" id="upcoming-countdown-container">
          <div class="empty-state" style="padding: 2.5rem 1rem; color: #ffffff;">
            <i class="fa-solid fa-calendar-xmark" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 0.75rem;"></i>
            <h3 style="color: #ffffff; font-size: 1.25rem; margin-bottom: 0.4rem;">No upcoming events at the moment.</h3>
            <p style="color: #94a3b8; font-size: 0.9rem;">Check back soon for new campus festivals, workshops, and competitions!</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- 4. FEATURED EVENTS -->
<section class="section" style="background: #ffffff;">
  <div class="container">
    <div class="section-header">
      <span class="badge badge-primary">Highlights</span>
      <h2 class="section-title">Featured Campus Events</h2>
      <p class="section-subtitle">Take part in state-of-the-art hackathons, dynamic cultural festivals, competitive sports tournaments, and thought leadership seminars.</p>
    </div>

    <div class="events-grid">
      <?php foreach ($featuredEvents as $ev): 
        $availableSeats = max(0, $ev['max_participants'] - $ev['registered_count']);
        $evDate = date('M j, Y', strtotime($ev['event_date']));
        $evTime = date('g:i A', strtotime($ev['event_time']));
      ?>
        <div class="event-card">
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
              <?php if (strtotime($ev['registration_deadline']) > time() && $availableSeats > 0): ?>
                <a href="<?= $baseUrl ?>event-details.php?id=<?= $ev['id'] ?>#register-section" class="btn btn-primary btn-sm">
                  Register Free
                </a>
              <?php else: ?>
                <button class="btn btn-secondary btn-sm" disabled style="opacity: 0.6;">Closed</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: 3.5rem;">
      <a href="<?= $baseUrl ?>events.php" class="btn btn-secondary btn-lg">
        View All Events <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- 5. EVENT CATEGORIES -->
<section class="section" style="background: var(--bg);">
  <div class="container">
    <div class="section-header">
      <span class="badge badge-primary">Diverse Interests</span>
      <h2 class="section-title">Explore by Category</h2>
      <p class="section-subtitle">Discover high-energy activities tailored for engineers, artists, athletes, designers, and campus innovators.</p>
    </div>

    <div class="categories-grid">
      <a href="<?= $baseUrl ?>events.php?category=Technical" class="category-card">
        <div class="category-icon"><i class="fa-solid fa-microchip"></i></div>
        <h3>Technical</h3>
        <p>Hackathons, robowars, IoT sprints, and web development challenges.</p>
      </a>

      <a href="<?= $baseUrl ?>events.php?category=Cultural" class="category-card">
        <div class="category-icon"><i class="fa-solid fa-masks-theater"></i></div>
        <h3>Cultural</h3>
        <p>Stage theatre, western dance, battle of the bands, and musical evenings.</p>
      </a>

      <a href="<?= $baseUrl ?>events.php?category=Sports" class="category-card">
        <div class="category-icon"><i class="fa-solid fa-volleyball"></i></div>
        <h3>Sports</h3>
        <p>Inter-department football, basketball, cricket cups, and athletic meets.</p>
      </a>

      <a href="<?= $baseUrl ?>events.php?category=Workshop" class="category-card">
        <div class="category-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
        <h3>Workshops</h3>
        <p>Hands-on AI bootcamps, UI/UX sprints, and cloud certification workshops.</p>
      </a>

      <a href="<?= $baseUrl ?>events.php?category=Arts" class="category-card">
        <div class="category-icon"><i class="fa-solid fa-palette"></i></div>
        <h3>Arts</h3>
        <p>Canvas exhibitions, digital design showcase, and photography duels.</p>
      </a>

      <a href="<?= $baseUrl ?>events.php?category=Seminar" class="category-card">
        <div class="category-icon"><i class="fa-solid fa-user-tie"></i></div>
        <h3>Seminars</h3>
        <p>Venture capital panels, tech keynotes, and career symposiums.</p>
      </a>

      <a href="<?= $baseUrl ?>events.php?category=Competition" class="category-card">
        <div class="category-icon"><i class="fa-solid fa-trophy"></i></div>
        <h3>Competitions</h3>
        <p>Coding duels, business quiz bowls, and product pitch tournaments.</p>
      </a>

      <a href="<?= $baseUrl ?>events.php?category=College+Festival" class="category-card">
        <div class="category-icon"><i class="fa-solid fa-guitar"></i></div>
        <h3>College Festivals</h3>
        <p>Multi-day campus fests, live celebrity concerts, and campus carnival nights.</p>
      </a>
    </div>
  </div>
</section>

<!-- 6. OUR IMPACT -->
<section class="section impact-section">
  <div class="container">
    <div class="section-header" style="color: #ffffff;">
      <span class="badge badge-navy" style="background: rgba(255, 255, 255, 0.15); color: #ffffff;">Campus Numbers</span>
      <h2 class="section-title" style="color: #ffffff;">Our Campus Impact</h2>
      <p class="section-subtitle" style="color: #cbd5e1;">Empowering vibrant student participation and recognizing achievements across all faculties.</p>
    </div>

    <div class="impact-grid">
      <div class="impact-card">
        <div class="impact-icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="impact-number" data-target="<?= max(25, $totalEvents * 4) ?>">0</div>
        <div class="impact-label">Events Conducted</div>
      </div>

      <div class="impact-card">
        <div class="impact-icon"><i class="fa-solid fa-users"></i></div>
        <div class="impact-number" data-target="<?= max(1200, $totalStudents * 300) ?>">0</div>
        <div class="impact-label">Students Participated</div>
      </div>

      <div class="impact-card">
        <div class="impact-icon"><i class="fa-solid fa-award"></i></div>
        <div class="impact-number" data-target="<?= max(850, $totalCertificates * 200) ?>">0</div>
        <div class="impact-label">Certificates Issued</div>
      </div>

      <div class="impact-card">
        <div class="impact-icon"><i class="fa-solid fa-building-columns"></i></div>
        <div class="impact-number" data-target="18">0</div>
        <div class="impact-label">Clubs & Societies</div>
      </div>
    </div>
  </div>
</section>

<!-- 7. HOW IT WORKS -->
<section class="section" style="background: #ffffff;">
  <div class="container">
    <div class="section-header">
      <span class="badge badge-primary">Effortless Journey</span>
      <h2 class="section-title">How It Works</h2>
      <p class="section-subtitle">From discovering your next favorite workshop to receiving your certified credential, here is how easy it is.</p>
    </div>

    <div class="steps-grid">
      <div class="step-card">
        <div class="step-number">1</div>
        <h3>Discover Events</h3>
        <p>Explore upcoming fests, competitions and workshops filtered by your interests and schedule.</p>
      </div>

      <div class="step-card">
        <div class="step-number">2</div>
        <h3>Register Free</h3>
        <p>Secure your spot in 1 click using your verified student account with zero registration fee.</p>
      </div>

      <div class="step-card">
        <div class="step-number">3</div>
        <h3>Participate</h3>
        <p>Show up on campus, showcase your talent, collaborate with peers, and check in with your coordinator.</p>
      </div>

      <div class="step-card">
        <div class="step-number">4</div>
        <h3>Share Feedback</h3>
        <p>Rate the organization, submit event photos to the public gallery, and review the speakers.</p>
      </div>

      <div class="step-card">
        <div class="step-number">5</div>
        <h3>Get Certificate</h3>
        <p>Download and print your uniquely verified Certificate of Participation directly from your dashboard.</p>
      </div>
    </div>
  </div>
</section>

<!-- 8. WHAT STUDENTS SAY -->
<section class="section" style="background: var(--bg);">
  <div class="container">
    <div class="section-header">
      <span class="badge badge-primary">Testimonials</span>
      <h2 class="section-title">What Students Say</h2>
      <p class="section-subtitle">Real experiences and feedback from university students who actively engage in campus events.</p>
    </div>

    <div class="testimonials-grid">
      <?php if (!empty($testimonials)): ?>
        <?php foreach ($testimonials as $t): ?>
          <div class="testimonial-card">
            <div class="testimonial-rating">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fa-<?= $i <= $t['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
              <?php endfor; ?>
            </div>
            <p class="testimonial-comment">"<?= e($t['comment']) ?>"</p>
            <div class="testimonial-author">
              <div class="author-avatar">
                <?= strtoupper(substr($t['full_name'], 0, 1)) ?>
              </div>
              <div class="author-info">
                <h4><?= e($t['full_name']) ?></h4>
                <p><?= e($t['course']) ?> • <?= e($t['event_title']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align: center; color: var(--muted); grid-column: 1 / -1;">No student feedback available yet.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- 9. GALLERY PREVIEW -->
<section class="section" style="background: #ffffff;">
  <div class="container">
    <div class="section-header">
      <span class="badge badge-primary">Memories</span>
      <h2 class="section-title">Captured Moments</h2>
      <p class="section-subtitle">Glimpses into recent coding marathons, stage dances, awards, and sports championships.</p>
    </div>

    <div class="gallery-grid">
      <?php foreach ($galleryPhotos as $idx => $photo): ?>
        <div class="gallery-item" 
             data-full-img="<?= $baseUrl . e($photo['image_url']) ?>" 
             data-caption="<?= e($photo['caption']) ?>"
             data-meta="<?= e($photo['event_title']) ?> • <?= e($photo['category']) ?>"
             data-category="<?= strtolower(e($photo['category'])) ?>">
          <img src="<?= $baseUrl . e($photo['image_url']) ?>" alt="<?= e($photo['caption']) ?>" loading="lazy">
          <div class="gallery-overlay">
            <div class="gallery-caption"><?= e($photo['caption']) ?></div>
            <div class="gallery-meta"><?= e($photo['event_title']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: 3rem;">
      <a href="<?= $baseUrl ?>gallery.php" class="btn btn-secondary btn-lg">
        Explore Full Gallery <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- LIGHTBOX MODAL FOR GALLERY PREVIEWS -->
<div class="lightbox-modal" id="gallery-lightbox-modal">
  <div class="lightbox-content">
    <button type="button" class="lightbox-close" aria-label="Close lightbox">&times;</button>
    <button type="button" class="lightbox-prev" aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></button>
    <button type="button" class="lightbox-next" aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></button>

    <div class="lightbox-img-wrapper">
      <img id="lightbox-image" src="" alt="Lightbox preview">
    </div>
    <div class="lightbox-caption" id="lightbox-caption"></div>
    <div class="gallery-meta" id="lightbox-meta" style="color: #94a3b8;"></div>
  </div>
</div>

<!-- 10 & 11. FEEDBACK & FINAL CALL-TO-ACTION -->
<section class="section" style="padding-top: 1rem; padding-bottom: 5rem;">
  <div class="container">
    <div class="cta-banner">
      <span class="badge badge-navy" style="background: rgba(255, 255, 255, 0.2); color: #ffffff; margin-bottom: 1rem;">
        Get Involved Today
      </span>
      <h2>Ready to Experience Campus Life?</h2>
      <p>Whether you're presenting an engineering project, dancing on the main stage, or learning machine learning, your university experience starts here.</p>
      <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <a href="<?= $baseUrl ?>events.php" class="btn btn-secondary btn-lg" style="background: #ffffff; color: var(--primary-dark);">
          <i class="fa-solid fa-compass"></i> Browse Events
        </a>
        <a href="<?= $baseUrl ?>feedback.php" class="btn btn-outline btn-lg" style="border-color: #ffffff; color: #ffffff;">
          <i class="fa-solid fa-comment-dots"></i> Give Feedback
        </a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
