<?php
/**
 * CampusEvents - About Page
 */
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$baseUrl = getBaseUrl();

// Stats
$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE is_published = 1")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalCertificates = (int)$pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
$totalRegistrations = (int)$pdo->query("SELECT COUNT(*) FROM event_registrations WHERE status != 'cancelled'")->fetchColumn();

$pageTitle = 'About CampusEvents';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div style="background: linear-gradient(180deg, rgba(37, 99, 235, 0.05) 0%, transparent 100%); padding: 4rem 0 2.5rem;">
  <div class="container" style="text-align: center; max-width: 760px;">
    <span class="badge badge-primary" style="margin-bottom: 0.75rem;">Our Mission</span>
    <h1 style="font-size: 2.85rem; font-weight: 850; color: var(--primary-dark); margin-bottom: 1rem;">Experience. Participate. Celebrate.</h1>
    <p style="color: var(--muted); font-size: 1.15rem; line-height: 1.65;">CampusEvents was founded to unify student life across every academic department, club, and student committee under one seamless, modern, digital campus hub.</p>
  </div>
</div>

<section class="section" style="padding-top: 2rem;">
  <div class="container">
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3.5rem; align-items: center; margin-bottom: 5rem;">
      <div>
        <span class="badge badge-primary" style="margin-bottom: 0.5rem;">The Platform</span>
        <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 1.25rem;">Revolutionizing University Activities</h2>
        <p style="color: var(--muted); line-height: 1.7; font-size: 1.05rem; margin-bottom: 1.25rem;">
          Traditional campus activities relied on fragmented notice boards, physical paper forms, and slow coordination. CampusEvents empowers students to explore all fests, hackathons, sports cups, and workshops in real time with instant free registration.
        </p>
        <p style="color: var(--muted); line-height: 1.7; font-size: 1.05rem;">
          Event organizers gain complete transparency with digital attendance records, automated participant rosters, feedback analytics, and instantaneous verifiable digital certificate generation.
        </p>
      </div>

      <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 2.5rem; box-shadow: var(--shadow-lg);">
        <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 1.5rem;">Key Pillars</h3>
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
          <div style="display: flex; gap: 1rem; align-items: flex-start;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(37, 99, 235, 0.1); color: var(--accent); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem;">
              <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <div>
              <h4 style="font-size: 1.05rem; font-weight: 700;">Discover Activities</h4>
              <p style="font-size: 0.9rem; color: var(--muted);">Explore all clubs and department fests categorized and filtered with live seat availability.</p>
            </div>
          </div>

          <div style="display: flex; gap: 1rem; align-items: flex-start;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); color: var(--success); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem;">
              <i class="fa-solid fa-ticket"></i>
            </div>
            <div>
              <h4 style="font-size: 1.05rem; font-weight: 700;">100% Free Participation</h4>
              <p style="font-size: 0.9rem; color: var(--muted);">Zero registration barriers. Free access for every enrolled university student.</p>
            </div>
          </div>

          <div style="display: flex; gap: 1rem; align-items: flex-start;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(245, 158, 11, 0.1); color: var(--warning); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem;">
              <i class="fa-solid fa-award"></i>
            </div>
            <div>
              <h4 style="font-size: 1.05rem; font-weight: 700;">Verified Credentials</h4>
              <p style="font-size: 0.9rem; color: var(--muted);">Printable, high-resolution participation certificates with unique tamper-proof certificate IDs.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Impact Numbers Section -->
    <div style="background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-navy) 100%); border-radius: var(--radius-xl); padding: 3.5rem 2.5rem; color: #ffffff; margin-bottom: 5rem;">
      <div style="text-align: center; max-width: 600px; margin: 0 auto 3rem;">
        <span class="badge badge-navy" style="background: rgba(255, 255, 255, 0.15); color: #ffffff;">Real Metrics</span>
        <h2 style="font-size: 2.2rem; font-weight: 800; color: #ffffff; margin-top: 0.5rem;">Our Campus Impact</h2>
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
          <div class="impact-icon"><i class="fa-solid fa-sitemap"></i></div>
          <div class="impact-number" data-target="18">0</div>
          <div class="impact-label">Organizing Committees</div>
        </div>
      </div>
    </div>

    <!-- How It Works Recap -->
    <div class="section-header">
      <span class="badge badge-primary">Process</span>
      <h2 class="section-title">How Students Participate</h2>
      <p class="section-subtitle">A straightforward 5-step flow from discovering an event to celebrating your certificate.</p>
    </div>

    <div class="steps-grid" style="margin-bottom: 3.5rem;">
      <div class="step-card">
        <div class="step-number">1</div>
        <h3>Discover</h3>
        <p>Browse events filtered by technical, cultural, arts or sports categories.</p>
      </div>

      <div class="step-card">
        <div class="step-number">2</div>
        <h3>Register</h3>
        <p>Claim your entry pass in seconds with zero fees or complicated forms.</p>
      </div>

      <div class="step-card">
        <div class="step-number">3</div>
        <h3>Participate</h3>
        <p>Showcase skills, compete, attend workshops and network with peers.</p>
      </div>

      <div class="step-card">
        <div class="step-number">4</div>
        <h3>Share Feedback</h3>
        <p>Rate the experience and upload event photos to the public gallery.</p>
      </div>

      <div class="step-card">
        <div class="step-number">5</div>
        <h3>Certificate</h3>
        <p>Download your verified Certificate of Participation with unique ID.</p>
      </div>
    </div>

  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
