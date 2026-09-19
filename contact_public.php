<?php
require 'config.php';
$success = '';
$error = '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$student_id = trim($_POST['student_id'] ?? '');
$type = $_POST['type'] ?? 'Feedback';
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    student_id VARCHAR(30) NULL,
    type ENUM('Feedback','Problem','Question','Suggestion') NOT NULL DEFAULT 'Feedback',
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('New','In Review','Resolved') NOT NULL DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        $error = 'Please complete your name, email, subject, and message.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($type, ['Feedback','Problem','Question','Suggestion'], true)) {
        $error = 'Please select a valid message type.';
    } else {
        $q=$pdo->prepare('INSERT INTO feedback (name,email,student_id,type,subject,message) VALUES (?,?,?,?,?,?)');
        $q->execute([$name,$email,$student_id ?: null,$type,$subject,$message]);
        $success='Your message has been sent to ETW administration. You may now return to the sign-in page.';
        $subject=$message='';
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Contact Administration | ETW – NU Baliwag</title><link rel="stylesheet" href="style.css"></head>
<body class="auth-page contact-public-page">
<div class="auth-shell public-contact-shell">
  <section class="auth-hero"><div class="hero-grid"></div><div class="hero-content">
    <div class="university-lockup"><div class="nu-seal">NU</div><div><strong>National University</strong><span>BALIWAG</span></div></div>
    <div class="system-name">ETW</div><div class="system-subtitle">Online Management System</div><div class="hero-line"></div>
    <h1>We're here to<br><strong>help.</strong></h1>
    <p>Send a question, report a problem, or share feedback with the ETW administration team.</p>
    <div class="hero-features"><span>Student Support</span><span>Problem Reports</span><span>Feedback</span></div>
  </div><div class="auth-hero-footer"><span>NU Baliwag · Student Services</span><span>© 2026</span></div></section>
  <section class="auth-panel"><div class="auth-panel-inner">
    <div class="help-link"><a href="index.php">← Back to sign in</a></div>
    <div class="auth-heading"><span class="eyebrow">STUDENT SUPPORT</span><h2>Contact administration</h2><p>Your message will be forwarded to the ETW administrator.</p></div>
    <?php if($error): ?><div class="auth-alert"><?=e($error)?></div><?php endif; ?>
    <?php if($success): ?><div class="auth-success"><?=e($success)?></div><?php endif; ?>
    <form method="post" class="auth-form contact-public-form">
      <div class="register-grid">
        <div class="field"><label>Full name</label><input name="name" value="<?=e($name)?>" placeholder="Juan Dela Cruz" required></div>
        <div class="field"><label>Email address</label><input type="email" name="email" value="<?=e($email)?>" placeholder="name@example.com" required></div>
        <div class="field"><label>Student ID <span class="muted">(optional)</span></label><input name="student_id" value="<?=e($student_id)?>" placeholder="e.g. 2021-00123"></div>
        <div class="field"><label>Message type</label><select name="type"><option <?= $type==='Feedback'?'selected':'' ?>>Feedback</option><option <?= $type==='Problem'?'selected':'' ?>>Problem</option><option <?= $type==='Question'?'selected':'' ?>>Question</option><option <?= $type==='Suggestion'?'selected':'' ?>>Suggestion</option></select></div>
        <div class="field span-2"><label>Subject</label><input name="subject" value="<?=e($subject)?>" placeholder="Briefly describe your concern" required></div>
        <div class="field span-2"><label>Message</label><textarea name="message" rows="6" placeholder="Tell administration what happened or what you would like to suggest..." required><?=e($message)?></textarea></div>
      </div>
      <button class="auth-primary" type="submit">Send Message</button>
    </form>
  </div></section>
</div></body></html>
