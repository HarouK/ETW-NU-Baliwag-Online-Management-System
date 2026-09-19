<?php
require 'admin_bootstrap.php';
$title='Administration';
$stats=[
 'students'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
 'pending'=>(int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE status='pending'")->fetchColumn(),
 'tickets'=>(int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status IN ('Open','Ongoing')")->fetchColumn(),
 'faculty'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='faculty'")->fetchColumn(),
 'subjects'=>(int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn(),
 'announcements'=>(int)$pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn()
];
$recent=$pdo->query("SELECT action,description,created_at FROM transaction_history ORDER BY created_at DESC LIMIT 8")->fetchAll();
$pending=$pdo->query("SELECT e.*,u.name student,s.code,s.name subject FROM enrollments e JOIN users u ON u.id=e.student_id JOIN subjects s ON s.id=e.subject_id WHERE e.status='pending' ORDER BY e.created_at DESC LIMIT 5")->fetchAll();
$tickets=$pdo->query("SELECT t.*,u.name student FROM tickets t JOIN users u ON u.id=t.student_id ORDER BY t.created_at DESC LIMIT 5")->fetchAll();
require 'header.php'; admin_flash_html();
?>
<div class="section admin-dashboard-home" style="padding:18px 20px;margin-bottom:18px">
  <span class="eyebrow">ADMINISTRATION CONTROL CENTER</span><h2 style="margin:4px 0 3px">Welcome, Administrator</h2>
  <p class="muted">Overview of the NU Baliwag Online Management System. Use the sidebar to open each management window.</p>
</div>
<div class="faculty-quick" style="margin:0 0 18px"><a class="btn" href="admin_important_dates.php">Manage Important Dates</a><a class="btn" href="admin_audit.php">Open Audit Log</a><a class="btn" href="admin_students.php">Manage Users</a></div>
<div class="cards admin-stat-cards">
<?php foreach([['Students',$stats['students'],'blue','registered accounts'],['Pending Enrollment',$stats['pending'],'green','awaiting review'],['Open Tickets',$stats['tickets'],'orange','needs attention'],['Faculty',$stats['faculty'],'purple','faculty accounts'],['Subjects',$stats['subjects'],'blue','active subjects'],['Announcements',$stats['announcements'],'orange','published notices']] as $s): ?>
<div class="card stat"><span class="muted"><?=e($s[0])?></span><div class="num <?=e($s[2])?>"><?=e($s[1])?></div><span class="muted"><?=e($s[3])?></span></div>
<?php endforeach; ?>
</div>
<div class="dashboard-lower">
<section class="section"><div class="card-head"><div><span class="eyebrow">ENROLLMENT</span><h2>Pending Requests</h2></div><a class="small-link" href="admin_enrollment.php">View all →</a></div>
<?php if(!$pending): ?><p class="muted">No pending enrollment requests.</p><?php endif; ?>
<?php foreach($pending as $r): ?><div class="reminder-row"><span class="reminder-mark">E</span><span><b><?=e($r['student'])?></b><small><?=e($r['code'])?> — <?=e($r['subject'])?></small></span><span class="badge pending">Pending</span></div><?php endforeach; ?></section>
<section class="section"><div class="card-head"><div><span class="eyebrow">SUPPORT</span><h2>Recent Tickets</h2></div><a class="small-link" href="admin_tickets.php">View all →</a></div>
<?php if(!$tickets): ?><p class="muted">No tickets yet.</p><?php endif; ?>
<?php foreach($tickets as $t): ?><div class="reminder-row"><span class="reminder-mark">T</span><span><b><?=e($t['ticket_no'])?> — <?=e($t['title'])?></b><small><?=e($t['student'])?> · <?=date('M d, Y g:i A',strtotime($t['created_at']))?></small></span><span class="badge <?=strtolower(str_replace(' ','-',e($t['status'])))?>"><?=e($t['status'])?></span></div><?php endforeach; ?></section>
</div>
<section class="section"><div class="card-head"><div><span class="eyebrow">REPORTS &amp; ACTIVITY</span><h2>Recent System Activity</h2></div></div>
<?php if(!$recent): ?><p class="muted">No transaction activity yet.</p><?php endif; ?>
<?php foreach($recent as $r): ?><div class="reminder-row"><span class="activity-dot" aria-hidden="true"></span><span><b><?=e($r['action'])?></b><small><?=e($r['description'])?></small></span><small><?=date('M d, g:i A',strtotime($r['created_at']))?></small></div><?php endforeach; ?></section>
<?php require 'footer.php'; ?>
