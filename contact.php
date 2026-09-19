<?php
require 'config.php';
require_role('student');
$title = 'Contact Administration';
$u = $_SESSION['user'];
$success = '';
$error = '';

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
    $type = $_POST['type'] ?? 'Feedback';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($subject === '' || $message === '') {
        $error = 'Please enter a subject and message.';
    } elseif (!in_array($type, ['Feedback','Problem','Question','Suggestion'], true)) {
        $error = 'Please select a valid message type.';
    } else {
        $q = $pdo->prepare('INSERT INTO feedback (name,email,student_id,type,subject,message) VALUES (?,?,?,?,?,?)');
        $q->execute([$u['name'], $u['email'], $u['student_id'] ?? null, $type, $subject, $message]);
        $success = 'Your message has been sent to the ETW administration.';
    }
}
require 'header.php';
?>
<div class="section contact-section">
  <div class="section-intro">
    <div><span class="eyebrow">STUDENT SUPPORT</span><h2>Contact Administration</h2><p class="muted">Send feedback, report a problem, or ask a question. Your message will appear in the administrator's inbox.</p></div>
  </div>
  <?php if($success): ?><div class="success"><?=e($success)?></div><?php endif; ?>
  <?php if($error): ?><div class="alert"><?=e($error)?></div><?php endif; ?>
  <form method="post" class="contact-form">
    <div class="field"><label>Message type</label><select name="type"><option>Feedback</option><option>Problem</option><option>Question</option><option>Suggestion</option></select></div>
    <div class="field"><label>Subject</label><input name="subject" placeholder="What would you like to tell administration?" required></div>
    <div class="field"><label>Message</label><textarea name="message" rows="7" placeholder="Describe your feedback or concern in detail..." required></textarea></div>
    <div class="contact-meta"><span>Sending as <strong><?=e($u['name'])?></strong> · <?=e($u['email'])?></span><button class="btn" type="submit">Send to Administration</button></div>
  </form>
</div>
<?php require 'footer.php'; ?>
