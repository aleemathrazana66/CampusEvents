<?php
/**
 * CampusEvents - Global Footer Component
 */
$baseUrl = getBaseUrl();
?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <!-- Brand & Mission -->
      <div class="footer-brand">
        <a href="<?= $baseUrl ?>index.php" class="brand-logo">
          <i class="fa-solid fa-graduation-cap"></i>
          <span>Campus<span class="accent">Events</span></span>
        </a>
        <p>Making campus participation easier, smarter and more memorable. Empowering every student to discover talent, connect with peers, and celebrate college life.</p>
        <div class="footer-socials">
          <a href="#" class="social-link" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" class="social-link" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" class="social-link" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
          <a href="#" class="social-link" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
        </div>
      </div>

      <!-- Quick Navigation -->
      <div class="footer-col">
        <h4>Explore</h4>
        <ul class="footer-links">
          <li><a href="<?= $baseUrl ?>index.php">Home</a></li>
          <li><a href="<?= $baseUrl ?>events.php">Browse Events</a></li>
          <li><a href="<?= $baseUrl ?>about.php">About CampusEvents</a></li>
          <li><a href="<?= $baseUrl ?>gallery.php">Event Gallery</a></li>
          <li><a href="<?= $baseUrl ?>feedback.php">Student Reviews</a></li>
        </ul>
      </div>

      <!-- Categories -->
      <div class="footer-col">
        <h4>Categories</h4>
        <ul class="footer-links">
          <li><a href="<?= $baseUrl ?>events.php?category=Technical">Technical Fests</a></li>
          <li><a href="<?= $baseUrl ?>events.php?category=Cultural">Cultural Nights</a></li>
          <li><a href="<?= $baseUrl ?>events.php?category=Sports">Sports Tournaments</a></li>
          <li><a href="<?= $baseUrl ?>events.php?category=Workshop">Hands-on Workshops</a></li>
          <li><a href="<?= $baseUrl ?>events.php?category=Competition">Coding & Contests</a></li>
        </ul>
      </div>

      <!-- Contact Info -->
      <div class="footer-col">
        <h4>Contact Us</h4>
        <ul class="footer-links footer-contact">
          <li>
            <i class="fa-solid fa-location-dot"></i>
            <span>100 University Boulevard, Tech Campus, Academic City, AC 50001</span>
          </li>
          <li>
            <i class="fa-solid fa-envelope"></i>
            <span>support@campusevents.edu</span>
          </li>
          <li>
            <i class="fa-solid fa-phone"></i>
            <span>+1 (555) 019-2834 / Ext. 402</span>
          </li>
          <li>
            <i class="fa-solid fa-clock"></i>
            <span>Mon - Fri: 8:30 AM - 5:30 PM</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Copyright -->
    <div class="footer-bottom">
      <p>&copy; 2026 CampusEvents. All rights reserved. Built with pride for University Student Activities.</p>
    </div>
  </div>
</footer>

<!-- Core Script -->
<script src="<?= $baseUrl ?>assets/js/main.js"></script>

<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
  <?php foreach ($extraScripts as $script): ?>
    <script src="<?= $baseUrl . $script ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
