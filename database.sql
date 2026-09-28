-- ============================================================
-- Database: campusevents
-- CampusEvents - College Event Management System
-- "Experience. Participate. Celebrate."
-- ============================================================

CREATE DATABASE IF NOT EXISTS `campusevents` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campusevents`;

-- 1. Users Table (Core Auth)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Students Profile Table
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `student_id` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(25) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `course` VARCHAR(100) NOT NULL,
  `semester` VARCHAR(20) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Admins Profile Table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `full_name` VARCHAR(150) NOT NULL,
  `department` VARCHAR(100) NOT NULL DEFAULT 'Student Affairs & Activities',
  `phone` VARCHAR(25) DEFAULT '+1 (555) 019-2834',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Events Table
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `category` ENUM('Technical', 'Cultural', 'Sports', 'Arts', 'Workshop', 'Seminar', 'Competition', 'College Festival') NOT NULL,
  `description` TEXT NOT NULL,
  `rules` TEXT DEFAULT NULL,
  `eligibility` VARCHAR(255) DEFAULT 'Open to all registered university students',
  `event_date` DATE NOT NULL,
  `event_time` TIME NOT NULL,
  `venue` VARCHAR(200) NOT NULL,
  `organizer` VARCHAR(150) NOT NULL,
  `contact_info` VARCHAR(200) NOT NULL,
  `banner_image` VARCHAR(255) DEFAULT NULL,
  `max_participants` INT NOT NULL DEFAULT 100,
  `registration_deadline` DATETIME NOT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_event_date` (`event_date`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Event Registrations Table
CREATE TABLE IF NOT EXISTS `event_registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('registered', 'attended', 'completed', 'cancelled') NOT NULL DEFAULT 'registered',
  `is_certificate_eligible` TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uniq_event_student` (`event_id`, `student_id`),
  INDEX `idx_reg_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Feedback Table
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'hidden') NOT NULL DEFAULT 'approved',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_feedback_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_feedback_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Gallery Table (Student photo submissions & official photos)
CREATE TABLE IF NOT EXISTS `gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `student_id` INT DEFAULT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_gallery_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gallery_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Certificates Table
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `certificate_code` VARCHAR(50) NOT NULL UNIQUE,
  `registration_id` INT NOT NULL UNIQUE,
  `student_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `issue_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cert_reg` FOREIGN KEY (`registration_id`) REFERENCES `event_registrations`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cert_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cert_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'reminder') NOT NULL DEFAULT 'info',
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_notif_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- Default Admin: admin@campusevents.edu / Admin@123
-- Default Student: student@campusevents.edu / Student@123
-- ============================================================

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`) VALUES
(1, 'admin@campusevents.edu', '$2y$10$poSh0i0.YBlSu0md1/CL6eJLbHJdHksrG9vzwt1aW3we9CI784Huy', 'admin'),
(2, 'student@campusevents.edu', '$2y$10$tSMF5.0tupcQLdj5Z0f4IeklqnJ3NEjiS8BbQAHm6Fg2vpXUWMGji', 'student'),
(3, 'sarah.chen@campusevents.edu', '$2y$10$tSMF5.0tupcQLdj5Z0f4IeklqnJ3NEjiS8BbQAHm6Fg2vpXUWMGji', 'student'),
(4, 'marcus.vance@campusevents.edu', '$2y$10$tSMF5.0tupcQLdj5Z0f4IeklqnJ3NEjiS8BbQAHm6Fg2vpXUWMGji', 'student'),
(5, 'priya.patel@campusevents.edu', '$2y$10$tSMF5.0tupcQLdj5Z0f4IeklqnJ3NEjiS8BbQAHm6Fg2vpXUWMGji', 'student')
ON DUPLICATE KEY UPDATE `email`=`email`;

INSERT INTO `admins` (`id`, `user_id`, `full_name`, `department`, `phone`) VALUES
(1, 1, 'Dr. Arthur Sterling', 'Directorate of Student Affairs', '+1 (555) 234-5678')
ON DUPLICATE KEY UPDATE `full_name`=`full_name`;

INSERT INTO `students` (`id`, `user_id`, `student_id`, `full_name`, `phone`, `department`, `course`, `semester`, `avatar`) VALUES
(1, 2, 'CS2024001', 'Alex Rivera', '+1 (555) 301-4455', 'Computer Science & Engineering', 'B.Tech CSE', 'Semester 6', 'assets/images/avatar-1.jpg'),
(2, 3, 'EC2024042', 'Sarah Chen', '+1 (555) 301-8899', 'Electronics & Communication', 'B.Tech ECE', 'Semester 4', 'assets/images/avatar-2.jpg'),
(3, 4, 'ME2023019', 'Marcus Vance', '+1 (555) 301-6622', 'Mechanical Engineering', 'B.Tech ME', 'Semester 8', 'assets/images/avatar-3.jpg'),
(4, 5, 'BA2025110', 'Priya Patel', '+1 (555) 301-9911', 'School of Business & Management', 'MBA Finance', 'Semester 2', 'assets/images/avatar-4.jpg')
ON DUPLICATE KEY UPDATE `full_name`=`full_name`;

-- Realistic events (Upcoming dates relative to current date)
INSERT INTO `events` (`id`, `title`, `slug`, `category`, `description`, `rules`, `eligibility`, `event_date`, `event_time`, `venue`, `organizer`, `contact_info`, `banner_image`, `max_participants`, `registration_deadline`, `is_published`) VALUES
(1, 'InnovateX 2026: National Tech Symposium & Hackathon', 'innovatex-2026-national-tech-symposium', 'Technical', 
'A 24-hour campus hackathon and tech symposium bringing together top innovators, developers, and designers to solve real-world problems using cutting-edge AI, IoT, and Cloud technologies. Keynotes from industry leaders and venture pitch sessions.',
'1. Teams of 2-4 members.\n2. Original code built during the hackathon.\n3. Bring your student ID and laptops.\n4. Code of conduct must be strictly observed.',
'All undergraduate and postgraduate engineering and tech students',
DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), '09:00:00', 'APJ Abdul Kalam Tech Auditorium & Innovation Labs', 'Computer Science Society', 'techsociety@campusevents.edu | +1 (555) 441-1001', 'assets/images/event-tech.jpg', 250, DATE_ADD(NOW(), INTERVAL 4 DAY), 1),

(2, 'Aura 2026: Annual Cultural Extravaganza', 'aura-2026-annual-cultural-extravaganza', 'Cultural',
'The grand annual inter-collegiate cultural fest featuring western and classical dance competitions, battle of the bands, dramatic theater, fashion runway, and live evening acoustic concerts under the open campus stars.',
'1. Prior registration required for all stage competitions.\n2. Time limit: 8 minutes per stage performance.\n3. Music tracks must be submitted 24 hours prior to coordinator desk.',
'Open to all enrolled students with valid campus ID',
DATE_ADD(CURRENT_DATE, INTERVAL 12 DAY), '16:30:00', 'University Central Amphitheatre', 'Cultural Affairs Committee', 'cultural@campusevents.edu | +1 (555) 441-1002', 'assets/images/event-cultural.jpg', 800, DATE_ADD(NOW(), INTERVAL 11 DAY), 1),

(3, 'Inter-College Football Championship 2026', 'inter-college-football-championship-2026', 'Sports',
'The pinnacle campus football cup! 16 college departments clash in a knockout tournament over three adrenaline-packed days. Experience intense sporting camaraderie, top physical conditioning, and vocal crowd energy.',
'1. 11 active players + 5 substitutes per team roster.\n2. Official FIFA match rules apply.\n3. Standard football studs and shin guards mandatory.\n4. Referees decision is final and binding.',
'Enrolled full-time students belonging to represented departments',
DATE_ADD(CURRENT_DATE, INTERVAL 8 DAY), '07:30:00', 'Main Campus Sports Complex & Arena', 'Department of Physical Education', 'sports@campusevents.edu | +1 (555) 441-1003', 'assets/images/event-sports.jpg', 120, DATE_ADD(NOW(), INTERVAL 7 DAY), 1),

(4, 'Deep Learning & Generative AI Hands-on Workshop', 'deep-learning-generative-ai-workshop', 'Workshop',
'An intensive full-day masterclass covering transformer architectures, fine-tuning LLMs, PyTorch pipeline design, and deploying neural agents. Includes hands-on GPU lab access and real enterprise case studies.',
'1. Bring laptop with Python 3.9+ and Google Colab access.\n2. Basic familiarity with linear algebra and Python assumed.\n3. Workshop certificates awarded upon lab project submission.',
'Open to 2nd, 3rd, 4th year undergraduate & graduate students',
DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), '10:00:00', 'Advanced Computing Lab 3, Faraday Block', 'AI & Data Science Student Chapter', 'aiclub@campusevents.edu | +1 (555) 441-1004', 'assets/images/event-workshop.jpg', 60, DATE_ADD(NOW(), INTERVAL 2 DAY), 1),

(5, 'Vibrant Canvas: Annual College Fine Arts Showcase', 'vibrant-canvas-annual-fine-arts-showcase', 'Arts',
'Celebrate creative expression across oil painting, charcoal sketching, digital illustration, and clay sculpture. The exhibition will feature student artist galleries, live pottery demonstrations, and public art judging.',
'1. Artworks must be registered 48 hours before the showcase.\n2. Both physical canvas and digital print formats accepted.\n3. All materials provided for live spot-sketching events.',
'All campus students passionate about art and design',
DATE_ADD(CURRENT_DATE, INTERVAL 16 DAY), '11:00:00', 'Fine Arts Gallery & Exhibition Hall', 'Fine Arts & Design Guild', 'finearts@campusevents.edu | +1 (555) 441-1005', 'assets/images/event-arts.jpg', 100, DATE_ADD(NOW(), INTERVAL 15 DAY), 1),

(6, 'Global Startup Ecosystems & Venture Capital Seminar', 'global-startup-ecosystems-seminar', 'Seminar',
'Join seasoned venture capitalists, angel investors, and unicorn founders as they unpack early-stage bootstrapping, equity structuring, product-market fit, and pitching to top VC syndicates in 2026.',
'1. Business casual attire recommended.\n2. Q&A session will follow the panel discussion.\n3. Pitch decks may be submitted for private networking review.',
'Open to all students, faculty, and campus entrepreneurs',
DATE_ADD(CURRENT_DATE, INTERVAL 21 DAY), '14:00:00', 'Management Block Conference Hall A', 'Entrepreneurship Cell (E-Cell)', 'ecell@campusevents.edu | +1 (555) 441-1006', 'assets/images/event-seminar.jpg', 180, DATE_ADD(NOW(), INTERVAL 20 DAY), 1),

(7, 'Algorithm Arena: Fast-Paced Coding Duel', 'algorithm-arena-coding-duel', 'Competition',
'A thrilling 3-hour speed competitive programming showdown on algorithmic problem solving, graph theory, dynamic programming, and data structures. Immediate automated leaderboard feedback.',
'1. Individual participation only.\n2. Supported languages: C++, Java, Python 3.\n3. Plagiarism detection algorithms rigorously enforced.',
'Open to all university students',
DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), '14:00:00', 'Virtual Online Arena & Turing Hall', 'ACM Student Chapter', 'acm@campusevents.edu | +1 (555) 441-1007', 'assets/images/event-competition.jpg', 150, DATE_SUB(NOW(), INTERVAL 8 DAY), 1),

(8, 'Echoes Fest: Inter-Department Music Battle', 'echoes-fest-music-battle', 'College Festival',
'An electrifying acoustic and rock battle between student bands across faculties. High-energy solos, harmonious vocal choruses, and an unforgettable evening of campus community spirit.',
'1. Minimum 3, Maximum 7 members per band.\n2. Time limit: 12 minutes including setup.\n3. Acoustic drums and amplifiers provided on stage.',
'Open to all department band formations',
DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY), '17:00:00', 'University Open Ground & Stadium', 'Music & Performing Arts Society', 'music@campusevents.edu | +1 (555) 441-1008', 'assets/images/event-music.jpg', 500, DATE_SUB(NOW(), INTERVAL 15 DAY), 1)
ON DUPLICATE KEY UPDATE `title`=`title`;

-- Realistic Registrations
INSERT INTO `event_registrations` (`id`, `event_id`, `student_id`, `registration_date`, `status`, `is_certificate_eligible`) VALUES
(1, 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), 'registered', 0),
(2, 4, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), 'registered', 0),
(3, 7, 1, DATE_SUB(NOW(), INTERVAL 10 DAY), 'completed', 1),
(4, 8, 1, DATE_SUB(NOW(), INTERVAL 18 DAY), 'completed', 1),
(5, 1, 2, DATE_SUB(NOW(), INTERVAL 3 DAY), 'registered', 0),
(6, 2, 2, DATE_SUB(NOW(), INTERVAL 2 DAY), 'registered', 0),
(7, 7, 2, DATE_SUB(NOW(), INTERVAL 11 DAY), 'completed', 1),
(8, 3, 3, DATE_SUB(NOW(), INTERVAL 4 DAY), 'registered', 0),
(9, 8, 3, DATE_SUB(NOW(), INTERVAL 19 DAY), 'completed', 1),
(10, 6, 4, DATE_SUB(NOW(), INTERVAL 1 DAY), 'registered', 0),
(11, 7, 4, DATE_SUB(NOW(), INTERVAL 10 DAY), 'completed', 1)
ON DUPLICATE KEY UPDATE `status`=`status`;

-- Certificates for completed events
INSERT INTO `certificates` (`id`, `certificate_code`, `registration_id`, `student_id`, `event_id`, `issue_date`) VALUES
(1, 'CERT-2026-COD1094', 3, 1, 7, DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY)),
(2, 'CERT-2026-ECH2817', 4, 1, 8, DATE_SUB(CURRENT_DATE, INTERVAL 13 DAY)),
(3, 'CERT-2026-COD2044', 7, 2, 7, DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY)),
(4, 'CERT-2026-ECH3189', 9, 3, 8, DATE_SUB(CURRENT_DATE, INTERVAL 13 DAY))
ON DUPLICATE KEY UPDATE `certificate_code`=`certificate_code`;

-- Realistic Feedback (Approved ones show in "What Students Say")
INSERT INTO `feedback` (`id`, `event_id`, `student_id`, `rating`, `comment`, `status`) VALUES
(1, 7, 1, 5, 'Algorithm Arena was exceptionally well organized! The real-time leaderboard kept everyone on their toes, and the problem statements were world-class. Looking forward to the next edition!', 'approved'),
(2, 8, 2, 5, 'Echoes Fest was the highlight of our semester! The sound system and stage lighting at the Open Stadium created pure magic. Big thanks to the student coordinators!', 'approved'),
(3, 7, 3, 4, 'Very challenging competitive coding problems. The mentoring sessions right after the duel were super helpful for understanding optimal solutions. Highly recommended for junior devs!', 'approved'),
(4, 8, 4, 5, 'Loved the sheer energy and talent displayed by all the musical bands. CampusEvents made the entire entry check-in and seating seamless without long queues.', 'approved'),
(5, 7, 4, 5, 'Smooth registration and instant certificate issuance. The platform worked without a hitch!', 'approved')
ON DUPLICATE KEY UPDATE `rating`=`rating`;

-- Realistic Gallery Photos
INSERT INTO `gallery` (`id`, `event_id`, `student_id`, `image_url`, `caption`, `status`) VALUES
(1, 7, 1, 'assets/images/gallery-1.jpg', 'Final showdown problem solving round during Algorithm Arena', 'approved'),
(2, 8, 2, 'assets/images/gallery-2.jpg', 'Lead guitarist in the spotlight during Echoes Fest acoustic round', 'approved'),
(3, 8, 3, 'assets/images/gallery-3.jpg', 'Crowd cheering as the winning department band lifts the trophy', 'approved'),
(4, 7, 1, 'assets/images/gallery-4.jpg', 'Hackers debugging team code in the computer lab sprint', 'approved'),
(5, 8, 4, 'assets/images/gallery-5.jpg', 'Evening amphitheatre illuminated by flashlights and stage beams', 'approved'),
(6, 7, 2, 'assets/images/gallery-6.jpg', 'Award ceremony for top algorithmic solvers at Turing Hall', 'approved'),
(7, 1, 1, 'assets/images/gallery-7.jpg', 'Team working on an IoT robotics prototype during pre-hack setup', 'pending')
ON DUPLICATE KEY UPDATE `caption`=`caption`;

-- Realistic Notifications
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link`, `is_read`) VALUES
(1, 2, 'Registration Confirmed', 'You have successfully registered for InnovateX 2026: National Tech Symposium & Hackathon.', 'success', 'student/registrations.php', 0),
(2, 2, 'Certificate Issued!', 'Your Certificate of Participation for Algorithm Arena is ready to download.', 'info', 'student/certificates.php', 0),
(3, 2, 'Upcoming Event Reminder', 'Deep Learning & Generative AI Workshop starts in 3 days at Faraday Block.', 'reminder', 'student/events.php', 0),
(4, 1, 'New Student Registration', 'Alex Rivera registered for InnovateX 2026 Hackathon.', 'info', 'admin/registrations.php', 0),
(5, 1, 'Pending Photo Approval', 'Alex Rivera submitted a new event photograph awaiting moderation.', 'warning', 'admin/gallery.php', 0)
ON DUPLICATE KEY UPDATE `title`=`title`;
