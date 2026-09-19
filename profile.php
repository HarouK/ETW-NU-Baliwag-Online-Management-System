<?php
require 'config.php';
require_login();

// Add the profile-picture column automatically for existing prototype databases.
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL");
} catch (PDOException $e) {
    // Column already exists; continue normally.
}

$userId = (int)$_SESSION['user']['id'];
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$u = $stmt->fetch();
if (!$u) { header('Location: logout.php'); exit; }

$error = '';
$success = '';
$editing = isset($_GET['edit']) || $_SERVER['REQUEST_METHOD'] === 'POST';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $program === '') {
        $error = 'Please complete all required profile fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $check->execute([$email, $userId]);
        if ($check->fetch()) {
            $error = 'That email address is already being used by another account.';
        }
    }

    $avatarPath = $u['avatar_path'] ?? null;
    if (!$error && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $error = 'The profile picture could not be uploaded.';
        } elseif ($_FILES['profile_picture']['size'] > 3 * 1024 * 1024) {
            $error = 'Profile pictures must be 3 MB or smaller.';
        } else {
            $tmp = $_FILES['profile_picture']['tmp_name'];
            $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : '';
            $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!isset($allowed[$mime])) {
                $error = 'Please upload a JPG, PNG, or WebP image.';
            } else {
                $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
                $filename = 'user_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
                $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;
                if (!move_uploaded_file($tmp, $destination)) {
                    $error = 'The profile picture could not be saved.';
                } else {
                    $avatarPath = 'uploads/profiles/' . $filename;
                }
            }
        }
    }

    if (!$error) {
        if ($newPassword !== '') {
            if ($currentPassword === '' || hash('sha256',$currentPassword) !== ($u['password_hash'] ?? '')) {
                $error = 'Enter your current password to change your password.';
            } elseif (strlen($newPassword) < 6) {
                $error = 'New password must be at least 6 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New password and confirmation do not match.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, program=?, password_hash=SHA2(?,256), avatar_path=? WHERE id=?");
                $stmt->execute([$name, $email, $program, $newPassword, $avatarPath, $userId]);
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, program=?, avatar_path=? WHERE id=?");
            $stmt->execute([$name, $email, $program, $avatarPath, $userId]);
        }
        if (!$error) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $u = $stmt->fetch();
            $_SESSION['user'] = $u;
            $success = 'Your profile has been updated.';
            $editing = false;
        }
    }
}

$title = 'My Profile';
require 'header.php';
?>

<?php if ($success): ?><div class="success profile-message"><?=e($success)?></div><?php endif; ?>
<?php if ($error): ?><div class="danger profile-message"><?=e($error)?></div><?php endif; ?>

<?php if (!$editing): ?>
<div class="section profile-page">
  <div class="profile-heading">
    <div class="profile-identity">
      <?php if (!empty($u['avatar_path'])): ?>
        <img class="profile-photo" src="<?=e($u['avatar_path'])?>" alt="Profile picture">
      <?php else: ?>
        <div class="profile-photo profile-initial"><?=e(strtoupper(substr($u['name'],0,1)))?></div>
      <?php endif; ?>
      <div>
        <h2><?=e($u['name'])?></h2>
        <p class="muted"><?=e($u['program'] ?: 'Student')?></p>
      </div>
    </div>
    <a class="btn" href="profile.php?edit=1">Edit Profile</a>
  </div>

  <div class="profile-details">
    <div class="profile-detail"><span>Student ID</span><strong><?=e($u['student_id'] ?: '—')?></strong></div>
    <div class="profile-detail"><span>School Email</span><strong><?=e($u['email'])?></strong></div>
    <div class="profile-detail"><span>Program / Course</span><strong><?=e($u['program'] ?: '—')?></strong></div>
    <div class="profile-detail"><span>Account Type</span><strong><?=e(ucfirst($u['role']))?></strong></div>
  </div>
</div>
<?php else: ?>
<div class="section profile-page">
  <div class="card-head profile-edit-head">
    <div><h2>Edit Profile</h2><p class="muted">Update your personal information and profile picture.</p></div>
    <a href="profile.php">Cancel</a>
  </div>

  <form method="post" enctype="multipart/form-data" class="profile-edit-form">
    <div class="profile-edit-photo">
      <?php if (!empty($u['avatar_path'])): ?>
        <img class="profile-photo" src="<?=e($u['avatar_path'])?>" alt="Current profile picture">
      <?php else: ?>
        <div class="profile-photo profile-initial"><?=e(strtoupper(substr($u['name'],0,1)))?></div>
      <?php endif; ?>
      <div>
        <label for="profile_picture">Profile picture</label>
        <input id="profile_picture" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp">
        <small class="muted">JPG, PNG or WebP · maximum 3 MB</small>
      </div>
    </div>

    <div class="profile-form-grid">
      <div class="field"><label for="name">Full name</label><input id="name" name="name" value="<?=e($u['name'])?>" required></div>
      <div class="field"><label for="email">School email</label><input id="email" type="email" name="email" value="<?=e($u['email'])?>" required></div>
      <div class="field"><label for="student_id">Student ID</label><input id="student_id" value="<?=e($u['student_id'] ?: '—')?>" readonly class="readonly-field"><small class="muted">Student ID cannot be changed.</small></div>
      <div class="field"><label for="program">Program / Course</label><select id="program" name="program" required><option value="">Select your program</option><optgroup label="Accountancy, Business and Management"><option value="BS Accountancy" <?= $u['program'] === 'BS Accountancy' ? 'selected' : '' ?>>BS Accountancy</option><option value="BS Management Accounting" <?= $u['program'] === 'BS Management Accounting' ? 'selected' : '' ?>>BS Management Accounting</option><option value="BSBA Major in Financial Management" <?= $u['program'] === 'BSBA Major in Financial Management' ? 'selected' : '' ?>>BSBA Major in Financial Management</option><option value="BSBA Major in Marketing Management" <?= $u['program'] === 'BSBA Major in Marketing Management' ? 'selected' : '' ?>>BSBA Major in Marketing Management</option><option value="BS Hospitality Management" <?= $u['program'] === 'BS Hospitality Management' ? 'selected' : '' ?>>BS Hospitality Management</option><option value="BS Tourism Management" <?= $u['program'] === 'BS Tourism Management' ? 'selected' : '' ?>>BS Tourism Management</option></optgroup><optgroup label="Education, Arts and Sciences"><option value="Bachelor of Physical Education" <?= $u['program'] === 'Bachelor of Physical Education' ? 'selected' : '' ?>>Bachelor of Physical Education</option><option value="BS Psychology" <?= $u['program'] === 'BS Psychology' ? 'selected' : '' ?>>BS Psychology</option><option value="AB English Language Studies" <?= $u['program'] === 'AB English Language Studies' ? 'selected' : '' ?>>AB English Language Studies</option></optgroup><optgroup label="Engineering and Technology"><option value="BS Architecture" <?= $u['program'] === 'BS Architecture' ? 'selected' : '' ?>>BS Architecture</option><option value="BS Civil Engineering" <?= $u['program'] === 'BS Civil Engineering' ? 'selected' : '' ?>>BS Civil Engineering</option><option value="BS Computer Engineering" <?= $u['program'] === 'BS Computer Engineering' ? 'selected' : '' ?>>BS Computer Engineering</option><option value="BS Information Technology" <?= $u['program'] === 'BS Information Technology' ? 'selected' : '' ?>>BS Information Technology</option></optgroup></select></div>
      <div class="field"><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" placeholder="Required only when changing password"><small class="muted">Used to verify your account.</small></div><div class="field"><label for="new_password">New password <span class="muted">(optional)</span></label><input id="new_password" type="password" name="new_password" placeholder="Leave blank to keep your password"><small class="muted">At least 6 characters.</small></div><div class="field"><label for="confirm_password">Confirm new password</label><input id="confirm_password" type="password" name="confirm_password" placeholder="Repeat your new password"><small class="muted">Must match your new password.</small></div>
    </div>

    <div class="profile-actions"><a href="profile.php">Cancel</a><button class="btn" type="submit">Save Changes</button></div>
  </form>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>
