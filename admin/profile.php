<?php
/**
 * CampusEvents - Admin Profile & Security Settings
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$admin = getCurrentUser();
$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$userId = (int)$admin['user_id'];
$adminPk = (int)$admin['admin_id'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security validation failed. Please try again.';
    } elseif ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($fullName) || empty($department)) {
            $error = 'Name and Department are required.';
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, department = ?, phone = ? WHERE id = ?");
            $stmt->execute([$fullName, $department, $phone, $adminPk]);
            setFlashMessage('success', 'Admin profile details saved.');
            header("Location: {$baseUrl}admin/profile.php");
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
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $userId]);
            setFlashMessage('success', 'Admin password changed successfully.');
            header("Location: {$baseUrl}admin/profile.php");
            exit;
        }
    }
}

$admin = getCurrentUser();

$pageTitle = 'Admin Profile';
$isDashboardPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 0.25rem;">Administrator Settings</h2>
    <p style="color: var(--muted); font-size: 0.9rem;">Manage your coordinator contact profile and security credentials.</p>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #dc2626; padding: 0.85rem 1.25rem; border-radius: var(--radius-md); font-size: 0.875rem; margin-bottom: 1.5rem;">
    <i class="fa-solid fa-triangle-exclamation" style="margin-right: 0.4rem;"></i> <?= e($error) ?>
  </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: flex-start;">
  
  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-solid fa-user-shield" style="color: var(--accent); margin-right: 0.5rem;"></i> Coordinator Profile</h3>
    </div>
    <div class="dash-card-body">
      <form action="<?= $baseUrl ?>admin/profile.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="update_profile">

        <div class="form-group">
          <label class="form-label" for="full_name">Coordinator Name *</label>
          <input type="text" name="full_name" id="full_name" class="form-control" value="<?= e($admin['full_name']) ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
          <div class="form-group">
            <label class="form-label" for="email">Account Email</label>
            <input type="email" id="email" class="form-control" value="<?= e($admin['email']) ?>" readonly style="background: var(--surface-alt); cursor: not-allowed;">
          </div>

          <div class="form-group">
            <label class="form-label" for="phone">Contact Phone</label>
            <input type="tel" name="phone" id="phone" class="form-control" value="<?= e($admin['phone']) ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="department">Department / Division *</label>
          <input type="text" name="department" id="department" class="form-control" value="<?= e($admin['department']) ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-floppy-disk"></i> Save Profile
        </button>
      </form>
    </div>
  </div>

  <div class="dash-card">
    <div class="dash-card-header">
      <h3><i class="fa-solid fa-lock" style="color: var(--accent); margin-right: 0.5rem;"></i> Update Password</h3>
    </div>
    <div class="dash-card-body">
      <form action="<?= $baseUrl ?>admin/profile.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="change_password">

        <div class="form-group">
          <label class="form-label" for="current_password">Current Password</label>
          <input type="password" name="current_password" id="current_password" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="new_password">New Password</label>
          <input type="password" name="new_password" id="new_password" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirm_new_password">Confirm New Password</label>
          <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-secondary" style="width: 100%;">
          <i class="fa-solid fa-key"></i> Change Password
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
