<?php
require 'config.php';
if (!empty($_SESSION['user'])) {
    $existingRole = $_SESSION['user']['role'] ?? 'student';
    header('Location: ' . ($existingRole === 'admin' ? 'admin.php' : ($existingRole === 'faculty' ? 'faculty.php' : 'dashboard.php')));
    exit;
}
$error = '';
$email = trim($_POST['email'] ?? '');
$login_role = $_POST['login_role'] ?? 'student';
$allowed_roles = ['student','faculty','admin'];
if (!in_array($login_role, $allowed_roles, true)) $login_role = 'student';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && hash('sha256', $password) === $u['password_hash'] && $u['role'] === $login_role) {
        $_SESSION['user'] = $u;
        $destination = $u['role'] === 'admin' ? 'admin.php' : ($u['role'] === 'faculty' ? 'faculty.php' : 'dashboard.php');
        header('Location: ' . $destination);
        exit;
    }
    $roleNames = ['student'=>'student','faculty'=>'professor / teacher','admin'=>'administrator'];
    $error = 'The email or password is incorrect, or this account is not registered as a ' . $roleNames[$login_role] . '.';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign In | ETW – NU Baliwag</title>
<link rel="stylesheet" href="style.css?v=20260920-loginfix">
</head>
<body class="auth-page">
<div class="auth-shell">
  <section class="auth-hero">
    <div class="hero-grid"></div>
    <div class="hero-content">
      <div class="university-lockup">
        <div class="nu-seal">NU</div>
        <div><strong>National University</strong><span>BALIWAG</span></div>
      </div>
      <div class="system-name">ETW</div>
      <div class="system-subtitle">Online Management System</div>
      <div class="hero-line"></div>
      <h1>Everything you need.<br><strong>One place.</strong></h1>
      <p>Access academic services, enrollment, schedules, requests, and campus announcements through one centralized student portal.</p>
      <div class="hero-features">
        <span>Enrollment</span><span>Requests &amp; Tickets</span><span>Schedules</span><span>Announcements</span>
      </div>
    </div>
    <div class="auth-hero-footer"><span>NU Baliwag · Student Services</span><span>© 2026</span></div>
  </section>

  <section class="auth-panel">
    <div class="auth-panel-inner">
      <div class="help-link">Need help? <a href="contact_public.php">Contact the administrator</a></div>
      <div class="auth-heading">
        <span class="eyebrow">ETW PORTAL</span>
        <h2>Welcome back</h2>
        <p>Sign in to access your academic workspace.</p>
      </div>
      <?php if($error): ?><div class="auth-alert"><?=e($error)?></div><?php endif; ?>
      <form method="post" class="auth-form">
        <div class="field">
          <label for="login_role">Login as</label>
          <select id="login_role" name="login_role" aria-describedby="role-help">
            <option value="student" <?= $login_role==='student' ? 'selected' : '' ?>>Student</option>
            <option value="faculty" <?= $login_role==='faculty' ? 'selected' : '' ?>>Professor / Teacher</option>
            <option value="admin" <?= $login_role==='admin' ? 'selected' : '' ?>>Administrator</option>
          </select>
          <div id="role-help" class="field-help">Choose the account type you are signing in with.</div>
        </div>
        <div class="field">
          <label for="email">Institutional Email</label>
          <input id="email" type="text" name="email" value="<?=e($email)?>" placeholder="e.g. student@nu-baliwag.edu.ph" autocomplete="username" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
        </div>
        <div class="form-options">
          <label class="check"><input type="checkbox"> <span>Remember me</span></label>
          <a href="#">Forgot password?</a>
        </div>
        <button class="auth-primary" type="submit">Sign In</button>
      </form>
      <div class="register-prompt">Don't have an account? <a href="register.php">Create a student account</a></div>
      <div class="demo-note"><strong>Prototype accounts</strong><br>Student · student@nu-baliwag.edu.ph<br>Professor / Teacher · faculty@nu-baliwag.edu.ph<br>Administrator · admin@nu-baliwag.edu.ph<br><span>All demo accounts use: password</span></div>
    </div>
  </section>
</div>
</body>
</html>
