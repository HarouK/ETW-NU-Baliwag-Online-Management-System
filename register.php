<?php
require 'config.php';
if (!empty($_SESSION['user'])) { header('Location: dashboard.php'); exit; }
$error = '';
$success = '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$student_id = trim($_POST['student_id'] ?? '');
$program = trim($_POST['program'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($name === '' || $email === '' || $student_id === '' || $program === '' || $password === '') {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR student_id = ? LIMIT 1');
        $stmt->execute([$email, $student_id]);
        if ($stmt->fetch()) {
            $error = 'An account with that email or student ID already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,student_id,program) VALUES (?,?,SHA2(?,256),'student',?,?)");
            $stmt->execute([$name, $email, $password, $student_id, $program]);
            $success = 'Account created successfully. You can now sign in.';
            $name = $email = $student_id = $program = '';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account | ETW – NU Baliwag</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<div class="auth-shell register-layout">
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
      <p>Register once and access academic services, enrollment, schedules, requests, and announcements from a single portal.</p>
      <div class="hero-features"><span>Student Account</span><span>Online Enrollment</span><span>Service Requests</span></div>
    </div>
    <div class="auth-hero-footer"><span>NU Baliwag · Student Services</span><span>© 2026</span></div>
  </section>

  <section class="auth-panel register-panel">
    <div class="auth-panel-inner">
      <div class="help-link">Already registered? <a href="index.php">Back to sign in</a></div>
      <div class="auth-heading">
        <a class="back-link" href="index.php">← Back to login</a>
        <span class="eyebrow">STUDENT REGISTRATION</span>
        <h2>Create your account</h2>
        <p>Enter your student information to get started with ETW.</p>
      </div>
      <?php if($error): ?><div class="auth-alert"><?=e($error)?></div><?php endif; ?>
      <?php if($success): ?><div class="auth-success"><?=e($success)?></div><?php endif; ?>
      <form method="post" class="auth-form">
        <div class="register-grid">
          <div class="field span-2"><label for="name">Full name</label><input id="name" name="name" value="<?=e($name)?>" placeholder="Juan Dela Cruz" autocomplete="name" required></div>
          <div class="field"><label for="student_id">Student ID</label><input id="student_id" name="student_id" value="<?=e($student_id)?>" placeholder="e.g. 2021-00123" required></div>
          <div class="field"><label for="program">Program / Course</label><select id="program" name="program" required><option value="">Select your program</option><optgroup label="Accountancy, Business and Management"><option value="BS Accountancy" <?= $program === 'BS Accountancy' ? 'selected' : '' ?>>BS Accountancy</option><option value="BS Management Accounting" <?= $program === 'BS Management Accounting' ? 'selected' : '' ?>>BS Management Accounting</option><option value="BSBA Major in Financial Management" <?= $program === 'BSBA Major in Financial Management' ? 'selected' : '' ?>>BSBA Major in Financial Management</option><option value="BSBA Major in Marketing Management" <?= $program === 'BSBA Major in Marketing Management' ? 'selected' : '' ?>>BSBA Major in Marketing Management</option><option value="BS Hospitality Management" <?= $program === 'BS Hospitality Management' ? 'selected' : '' ?>>BS Hospitality Management</option><option value="BS Tourism Management" <?= $program === 'BS Tourism Management' ? 'selected' : '' ?>>BS Tourism Management</option></optgroup><optgroup label="Education, Arts and Sciences"><option value="Bachelor of Physical Education" <?= $program === 'Bachelor of Physical Education' ? 'selected' : '' ?>>Bachelor of Physical Education</option><option value="BS Psychology" <?= $program === 'BS Psychology' ? 'selected' : '' ?>>BS Psychology</option><option value="AB English Language Studies" <?= $program === 'AB English Language Studies' ? 'selected' : '' ?>>AB English Language Studies</option></optgroup><optgroup label="Engineering and Technology"><option value="BS Architecture" <?= $program === 'BS Architecture' ? 'selected' : '' ?>>BS Architecture</option><option value="BS Civil Engineering" <?= $program === 'BS Civil Engineering' ? 'selected' : '' ?>>BS Civil Engineering</option><option value="BS Computer Engineering" <?= $program === 'BS Computer Engineering' ? 'selected' : '' ?>>BS Computer Engineering</option><option value="BS Information Technology" <?= $program === 'BS Information Technology' ? 'selected' : '' ?>>BS Information Technology</option></optgroup></select></div>
          <div class="field span-2"><label for="reg_email">School email</label><input id="reg_email" type="email" name="email" value="<?=e($email)?>" placeholder="name@nu-baliwag.edu.ph" autocomplete="email" required></div>
          <div class="field"><label for="reg_password">Password</label><input id="reg_password" type="password" name="password" placeholder="At least 6 characters" autocomplete="new-password" required></div>
          <div class="field"><label for="confirm_password">Confirm password</label><input id="confirm_password" type="password" name="confirm_password" placeholder="Re-enter your password" autocomplete="new-password" required></div>
        </div>
        <div class="password-note"><strong>Password requirements</strong><span>At least 6 characters. Use a password you do not use elsewhere.</span></div>
        <label class="terms"><input type="checkbox" required><span>I agree to the ETW terms of use and student data privacy policy.</span></label>
        <button class="auth-primary" type="submit">Create Student Account</button>
      </form>
      <div class="register-prompt">Already have an account? <a href="index.php">Sign in</a></div>
    </div>
  </section>
</div>
</body>
</html>
