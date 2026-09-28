<?php
/**
 * CampusEvents - Modern Authentication: Login
 */
require_once __DIR__ . '/../includes/auth.php';

$baseUrl = getBaseUrl();

// Redirect if already authenticated
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: {$baseUrl}admin/dashboard.php");
    } else {
        header("Location: {$baseUrl}student/dashboard.php");
    }
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security validation failed (invalid CSRF). Please try again.';
    } elseif (empty($loginInput) || empty($password)) {
        $error = 'Please provide both your Email / Student ID and Password.';
    } else {
        $pdo = getDBConnection();
        // Check either email in users or student_id in students
        $stmt = $pdo->prepare("
            SELECT u.id, u.email, u.password_hash, u.role, s.student_id, s.full_name
            FROM users u
            LEFT JOIN students s ON s.user_id = u.id
            WHERE u.email = ? OR s.student_id = ?
            LIMIT 1
        ");
        $stmt->execute([$loginInput, $loginInput]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Password verified
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];

            setFlashMessage('success', 'Welcome back! You have successfully logged in.');

            if (!empty($redirect) && strpos($redirect, 'http') === false) {
                header("Location: " . urldecode($redirect));
            } elseif ($user['role'] === 'admin') {
                header("Location: {$baseUrl}admin/dashboard.php");
            } else {
                header("Location: {$baseUrl}student/dashboard.php");
            }
            exit;
        } else {
            $error = 'Invalid credentials. Please verify your Email/Student ID and Password.';
        }
    }
}

$pageTitle = 'Login to CampusEvents';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="min-height: 100vh; display: flex; flex-direction: column;">
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 3rem 1.5rem; background: radial-gradient(circle at 80% 20%, rgba(37, 99, 235, 0.08) 0%, transparent 60%), #f8fafc;">
    <div style="max-width: 440px; width: 100%;">
      <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 2.5rem 2rem; box-shadow: var(--shadow-xl);">
        
        <div style="text-align: center; margin-bottom: 2rem;">
          <div style="width: 52px; height: 52px; background: rgba(37, 99, 235, 0.1); border-radius: 50%; color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem;">
            <i class="fa-solid fa-lock"></i>
          </div>
          <h2 style="font-size: 1.65rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.35rem;">Sign In</h2>
          <p style="color: var(--muted); font-size: 0.9rem;">Access your events, registrations & certificates</p>
        </div>

        <?php if (!empty($error)): ?>
          <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #dc2626; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.875rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.65rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= e($error) ?></span>
          </div>
        <?php endif; ?>

        <form action="<?= $baseUrl ?>auth/login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

          <div class="form-group">
            <label class="form-label" for="login_input">Email Address or Student ID</label>
            <div style="position: relative;">
              <input type="text" id="login_input" name="login_input" class="form-control" style="padding-left: 2.6rem;" placeholder="student@campusevents.edu or CS2024001" required value="<?= e($_POST['login_input'] ?? '') ?>">
              <i class="fa-regular fa-envelope" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.9rem;"></i>
            </div>
          </div>

          <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
              <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
              <a href="#" onclick="showToast('info', 'Password Reset', 'Contact campus IT support at support@campusevents.edu or use demo credentials below.'); return false;" style="font-size: 0.8rem; font-weight: 600;">Forgot?</a>
            </div>
            <div style="position: relative;">
              <input type="password" id="password" name="password" class="form-control" style="padding-left: 2.6rem; padding-right: 2.6rem;" placeholder="••••••••" required>
              <i class="fa-solid fa-key" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.9rem;"></i>
              <button type="button" class="toggle-password-btn" data-target="password" style="position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--muted); cursor: pointer; padding: 0.25rem;">
                <i class="fa-regular fa-eye"></i>
              </button>
            </div>
          </div>

          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.75rem;">
            <label class="form-check">
              <input type="checkbox" name="remember_me" value="1" checked>
              <span>Remember me</span>
            </label>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; font-size: 1rem;">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
          </button>
        </form>

        <!-- Quick Demo Credentials Fill -->
        <div style="margin-top: 1.75rem; padding-top: 1.5rem; border-top: 1px solid var(--border); text-align: center;">
          <p style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); font-weight: 700; margin-bottom: 0.75rem;">Quick Demo Fill</p>
          <div style="display: flex; gap: 0.5rem; justify-content: center;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="quickFill('student@campusevents.edu', 'Student@123')">
              <i class="fa-solid fa-user-graduate"></i> Demo Student
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="quickFill('admin@campusevents.edu', 'Admin@123')">
              <i class="fa-solid fa-shield-halved"></i> Demo Admin
            </button>
          </div>
        </div>

        <div style="margin-top: 1.5rem; text-align: center; font-size: 0.875rem; color: var(--muted);">
          Don't have a student account? <a href="<?= $baseUrl ?>auth/register.php" style="font-weight: 700;">Register here</a>
        </div>

      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>

<script>
function quickFill(email, pwd) {
  document.getElementById('login_input').value = email;
  document.getElementById('password').value = pwd;
  showToast('info', 'Demo Credentials Loaded', 'Click "Sign In" to continue as ' + email);
}
</script>
