<?php
/**
 * CampusEvents - Student Profile Settings
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student = getCurrentUser();
$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$userId = (int)$student['user_id'];
$studentPk = (int)$student['student_id_pk'];

$error = '';
$success = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security validation failed. Please refresh and try again.';
    } elseif ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $semester = trim($_POST['semester'] ?? '');

        if (empty($fullName) || empty($phone) || empty($department) || empty($course) || empty($semester)) {
            $error = 'All profile fields are required.';
        } else {
            $stmt = $pdo->prepare("
                UPDATE students 
                SET full_name = ?, phone = ?, department = ?, course = ?, semester = ?
                WHERE id = ?
            ");
            $stmt->execute([$fullName, $phone, $department, $course, $semester, $studentPk]);

            setFlashMessage('success', 'Profile information updated successfully.');
            header("Location: {$baseUrl}student/profile.php");
            exit;
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_new_password'] ?? '';

        $stmtUser = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $currentHash = $stmtUser->fetchColumn();

        if (!password_verify($currentPassword, $currentHash)) {
            $error = 'Your current password was entered incorrectly.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirmation do not match.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtUpdate = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmtUpdate->execute([$newHash, $userId]);

            setFlashMessage('success', 'Password changed successfully!');
            header("Location: {$baseUrl}student/profile.php");
            exit;
        }
    }
}

// Re-fetch fresh user info
$student = getCurrentUser();

$pageTitle = 'My Profile';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/student-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Student Profile & Security</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Manage your academic details and account password.</p>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #dc2626; padding: 0.85rem 1.25rem; border-radius: var(--radius-md); font-size: 0.875rem; margin-bottom: 1.5rem;">
    <i class="fa-solid fa-triangle-exclamation" style="margin-right: 0.4rem;"></i> <?= e($error) ?>
  </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: flex-start;">
  
  <!-- Left: Profile Details Form -->
  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-solid fa-id-card" style="color: var(--accent); margin-right: 0.5rem;"></i> Academic & Contact Information</h3>
    </div>
    <div class="dash-card-body">
      <form action="<?= $baseUrl ?>student/profile.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="update_profile">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
          <div class="form-group">
            <label class="form-label" for="full_name">Full Name *</label>
            <input type="text" name="full_name" id="full_name" class="form-control" value="<?= e($student['full_name']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="student_id">Student ID (Registered)</label>
            <input type="text" id="student_id" class="form-control" value="<?= e($student['student_id']) ?>" readonly style="background: var(--surface-alt); cursor: not-allowed;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
          <div class="form-group">
            <label class="form-label" for="email">University Email</label>
            <input type="email" id="email" class="form-control" value="<?= e($student['email']) ?>" readonly style="background: var(--surface-alt); cursor: not-allowed;">
          </div>

          <div class="form-group">
            <label class="form-label" for="phone">Phone Number *</label>
            <input type="tel" name="phone" id="phone" class="form-control" value="<?= e($student['phone']) ?>" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem;">
          <div class="form-group">
            <label class="form-label" for="department">Department *</label>
            <select name="department" id="department" class="form-control" required>
              <option value="Computer Science & Engineering" <?= $student['department'] === 'Computer Science & Engineering' ? 'selected' : '' ?>>Computer Science</option>
              <option value="Electronics & Communication" <?= $student['department'] === 'Electronics & Communication' ? 'selected' : '' ?>>Electronics & Comm</option>
              <option value="Mechanical Engineering" <?= $student['department'] === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Eng</option>
              <option value="Civil Engineering" <?= $student['department'] === 'Civil Engineering' ? 'selected' : '' ?>>Civil Eng</option>
              <option value="School of Business & Management" <?= $student['department'] === 'School of Business & Management' ? 'selected' : '' ?>>Business & Management</option>
              <option value="Fine Arts & Design" <?= $student['department'] === 'Fine Arts & Design' ? 'selected' : '' ?>>Fine Arts & Design</option>
              <option value="Sciences & Humanities" <?= $student['department'] === 'Sciences & Humanities' ? 'selected' : '' ?>>Sciences & Humanities</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="course">Course *</label>
            <input type="text" name="course" id="course" class="form-control" value="<?= e($student['course']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="semester">Semester *</label>
            <select name="semester" id="semester" class="form-control" required>
              <?php for ($i = 1; $i <= 8; $i++): $sem = "Semester $i"; ?>
                <option value="<?= $sem ?>" <?= $student['semester'] === $sem ? 'selected' : '' ?>><?= $sem ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
          <i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
        </button>
      </form>
    </div>
  </div>

  <!-- Right: Password Change Form -->
  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-solid fa-lock" style="color: var(--accent); margin-right: 0.5rem;"></i> Security & Password</h3>
    </div>
    <div class="dash-card-body">
      <form action="<?= $baseUrl ?>student/profile.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="change_password">

        <div class="form-group">
          <label class="form-label" for="current_password">Current Password</label>
          <input type="password" name="current_password" id="current_password" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="new_password">New Password (min 6 chars)</label>
          <input type="password" name="new_password" id="new_password" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirm_new_password">Confirm New Password</label>
          <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-secondary" style="width: 100%;">
          <i class="fa-solid fa-key"></i> Update Password
        </button>
      </form>
    </div>
  </div>

</div>

    </div> <!-- /dashboard-content -->
  </div> <!-- /dashboard-main -->
</div> <!-- /dashboard-layout -->

<script src="<?= $baseUrl ?>assets/js/main.js"></script>
</body>
</html>
