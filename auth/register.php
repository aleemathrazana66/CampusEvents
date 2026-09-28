<?php
/**
 * CampusEvents - Student Registration
 */
require_once __DIR__ . '/../includes/auth.php';

$baseUrl = getBaseUrl();

if (isLoggedIn()) {
    header("Location: {$baseUrl}student/dashboard.php");
    exit;
}

$errors = [];
$formData = [
    'full_name' => '',
    'student_id' => '',
    'email' => '',
    'phone' => '',
    'department' => '',
    'course' => '',
    'semester' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['full_name'] = trim($_POST['full_name'] ?? '');
    $formData['student_id'] = strtoupper(trim($_POST['student_id'] ?? ''));
    $formData['email'] = strtolower(trim($_POST['email'] ?? ''));
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['department'] = trim($_POST['department'] ?? '');
    $formData['course'] = trim($_POST['course'] ?? '');
    $formData['semester'] = trim($_POST['semester'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validation
    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Security token expired. Please reload and submit again.';
    }

    if (empty($formData['full_name'])) $errors[] = 'Full Name is required.';
    if (empty($formData['student_id'])) $errors[] = 'Student ID / Register Number is required.';
    
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid university or personal email address is required.';
    }

    if (empty($formData['phone'])) {
        $errors[] = 'Contact phone number is required.';
    }

    if (empty($formData['department'])) $errors[] = 'Please select your department.';
    if (empty($formData['course'])) $errors[] = 'Course name is required.';
    if (empty($formData['semester'])) $errors[] = 'Please select your semester.';

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Database Uniqueness Check
    if (empty($errors)) {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'This email address is already registered.';
        }

        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? LIMIT 1");
        $stmt->execute([$formData['student_id']]);
        if ($stmt->fetch()) {
            $errors[] = 'This Student ID / Register Number is already registered.';
        }
    }

    // Insert Student Account
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmtUser = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'student')");
            $stmtUser->execute([$formData['email'], $passwordHash]);
            $userId = (int)$pdo->lastInsertId();

            $stmtStudent = $pdo->prepare("
                INSERT INTO students (user_id, student_id, full_name, phone, department, course, semester)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtStudent->execute([
                $userId,
                $formData['student_id'],
                $formData['full_name'],
                $formData['phone'],
                $formData['department'],
                $formData['course'],
                $formData['semester']
            ]);

            // Create initial welcome notification
            addNotification($userId, 'Welcome to CampusEvents!', 'Your student registration was successful. You can now register for all campus events for free.', 'success', 'student/dashboard.php');

            $pdo->commit();

            // Auto log in new student
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_role'] = 'student';
            $_SESSION['user_email'] = $formData['email'];

            setFlashMessage('success', 'Registration complete! Welcome to CampusEvents.');
            header("Location: {$baseUrl}student/dashboard.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'System error occurred while creating your account. Please try again.';
        }
    }
}

$pageTitle = 'Student Registration';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="min-height: 100vh; display: flex; flex-direction: column;">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 3.5rem 1.5rem; background: radial-gradient(circle at 20% 20%, rgba(37, 99, 235, 0.08) 0%, transparent 60%), #f8fafc;">
    <div style="max-width: 680px; width: 100%;">
      <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 2.5rem; box-shadow: var(--shadow-xl);">
        
        <div style="text-align: center; margin-bottom: 2rem;">
          <span class="badge badge-primary" style="margin-bottom: 0.5rem;">Join CampusEvents</span>
          <h2 style="font-size: 1.85rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.35rem;">Create Student Account</h2>
          <p style="color: var(--muted); font-size: 0.95rem;">Join thousands of students participating in campus life</p>
        </div>

        <?php if (!empty($errors)): ?>
          <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #dc2626; padding: 1rem 1.25rem; border-radius: var(--radius-md); font-size: 0.875rem; margin-bottom: 1.75rem;">
            <div style="font-weight: 700; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem;">
              <i class="fa-solid fa-circle-exclamation"></i>
              <span>Please resolve the following issues:</span>
            </div>
            <ul style="padding-left: 1.4rem; margin: 0;">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form action="<?= $baseUrl ?>auth/register.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
              <label class="form-label" for="full_name">Full Name *</label>
              <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Jordan Mitchell" value="<?= e($formData['full_name']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="student_id">Student ID / Reg Number *</label>
              <input type="text" id="student_id" name="student_id" class="form-control" placeholder="e.g. CS2025088" value="<?= e($formData['student_id']) ?>" required>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
              <label class="form-label" for="email">University / Personal Email *</label>
              <input type="email" id="email" name="email" class="form-control" placeholder="jordan@campusevents.edu" value="<?= e($formData['email']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="phone">Phone Number *</label>
              <input type="tel" id="phone" name="phone" class="form-control" placeholder="+1 (555) 000-0000" value="<?= e($formData['phone']) ?>" required>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
              <label class="form-label" for="department">Department *</label>
              <select id="department" name="department" class="form-control" required>
                <option value="">Select Dept</option>
                <option value="Computer Science & Engineering" <?= $formData['department'] === 'Computer Science & Engineering' ? 'selected' : '' ?>>Computer Science</option>
                <option value="Electronics & Communication" <?= $formData['department'] === 'Electronics & Communication' ? 'selected' : '' ?>>Electronics & Comm</option>
                <option value="Mechanical Engineering" <?= $formData['department'] === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Eng</option>
                <option value="Civil Engineering" <?= $formData['department'] === 'Civil Engineering' ? 'selected' : '' ?>>Civil Eng</option>
                <option value="School of Business & Management" <?= $formData['department'] === 'School of Business & Management' ? 'selected' : '' ?>>Business & Management</option>
                <option value="Fine Arts & Design" <?= $formData['department'] === 'Fine Arts & Design' ? 'selected' : '' ?>>Fine Arts & Design</option>
                <option value="Sciences & Humanities" <?= $formData['department'] === 'Sciences & Humanities' ? 'selected' : '' ?>>Sciences & Humanities</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="course">Course *</label>
              <input type="text" id="course" name="course" class="form-control" placeholder="e.g. B.Tech / MBA / B.Sc" value="<?= e($formData['course']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="semester">Semester *</label>
              <select id="semester" name="semester" class="form-control" required>
                <option value="">Select Sem</option>
                <option value="Semester 1" <?= $formData['semester'] === 'Semester 1' ? 'selected' : '' ?>>Semester 1</option>
                <option value="Semester 2" <?= $formData['semester'] === 'Semester 2' ? 'selected' : '' ?>>Semester 2</option>
                <option value="Semester 3" <?= $formData['semester'] === 'Semester 3' ? 'selected' : '' ?>>Semester 3</option>
                <option value="Semester 4" <?= $formData['semester'] === 'Semester 4' ? 'selected' : '' ?>>Semester 4</option>
                <option value="Semester 5" <?= $formData['semester'] === 'Semester 5' ? 'selected' : '' ?>>Semester 5</option>
                <option value="Semester 6" <?= $formData['semester'] === 'Semester 6' ? 'selected' : '' ?>>Semester 6</option>
                <option value="Semester 7" <?= $formData['semester'] === 'Semester 7' ? 'selected' : '' ?>>Semester 7</option>
                <option value="Semester 8" <?= $formData['semester'] === 'Semester 8' ? 'selected' : '' ?>>Semester 8</option>
              </select>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
              <label class="form-label" for="password">Password (min 6 chars) *</label>
              <div style="position: relative;">
                <input type="password" id="reg_password" name="password" class="form-control" placeholder="••••••••" required>
                <button type="button" class="toggle-password-btn" data-target="reg_password" style="position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--muted); cursor: pointer; padding: 0.25rem;">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="confirm_password">Confirm Password *</label>
              <div style="position: relative;">
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                <button type="button" class="toggle-password-btn" data-target="confirm_password" style="position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--muted); cursor: pointer; padding: 0.25rem;">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <div style="margin-bottom: 1.75rem;">
            <label class="form-check">
              <input type="checkbox" required checked>
              <span>I agree to abide by the university campus event guidelines & code of conduct.</span>
            </label>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.9rem; font-size: 1.05rem;">
            <i class="fa-solid fa-user-plus"></i> Complete Registration
          </button>
        </form>

        <div style="margin-top: 1.75rem; text-align: center; font-size: 0.875rem; color: var(--muted);">
          Already registered? <a href="<?= $baseUrl ?>auth/login.php" style="font-weight: 700;">Sign in to your account</a>
        </div>

      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>
