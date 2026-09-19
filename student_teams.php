<?php
require 'team_bootstrap.php'; require_role('student');
$title='My Teams'; $sid=(int)$_SESSION['user']['id'];
$q=$pdo->prepare("SELECT ct.id team_id,o.id offering_id,s.code,s.name,s.units,o.section,o.day_name,o.start_time,o.end_time,o.room,u.name faculty_name,COUNT(DISTINCT e2.student_id) student_count FROM course_teams ct JOIN subject_offerings o ON o.id=ct.offering_id JOIN subjects s ON s.id=o.subject_id LEFT JOIN users u ON u.id=o.faculty_id JOIN enrollments e ON e.offering_id=o.id AND e.student_id=? AND e.status='approved' LEFT JOIN enrollments e2 ON e2.offering_id=o.id AND e2.status='approved' GROUP BY ct.id,o.id,s.id,o.section,o.day_name,o.start_time,o.end_time,o.room,u.name ORDER BY s.code,o.section");
$q->execute([$sid]);$teams=$q->fetchAll(); require 'header.php';
?>
<section class="section"><div class="section-intro"><div><span class="eyebrow">MICROSOFT TEAMS-STYLE CLASS HUB</span><h2>My Teams</h2><p class="muted">Every subject you are enrolled in has its own class team for assignments, announcements, and online meetings.</p></div></div>
<div class="team-grid">
<?php foreach($teams as $t):?><a class="team-card" href="student_team.php?team=<?=e($t['team_id'])?>"><div class="team-card-top"><span class="team-icon">T</span><span class="team-code"><?=e($t['code'])?></span></div><h3><?=e($t['name'])?></h3><p>Section <?=e($t['section'])?> · <?=e($t['units'])?> units</p><div class="team-meta"><span><?=e($t['faculty_name']?:'Assigned Faculty')?></span><span><?=e($t['student_count'])?> students</span></div><div class="team-schedule"><?=e($t['day_name'])?> · <?=date('g:i A',strtotime($t['start_time']))?>–<?=date('g:i A',strtotime($t['end_time']))?> · <?=e($t['room'])?></div><b class="team-open">Open Team →</b></a><?php endforeach;?>
</div><?php if(!$teams):?><p class="muted">You do not have any approved subject teams yet.</p><?php endif;?></section>
<?php require 'footer.php';?>
