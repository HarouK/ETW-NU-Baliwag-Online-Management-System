<?php
require 'config.php'; require_role('student'); $title='Transaction History';$u=$_SESSION['user'];
try{
 $oldE=$pdo->prepare("SELECT e.id,e.status,e.created_at,s.code,s.name FROM enrollments e JOIN subjects s ON s.id=e.subject_id WHERE e.student_id=?");$oldE->execute([$u['id']]);
 foreach($oldE->fetchAll() as $r){$chk=$pdo->prepare("SELECT id FROM transaction_history WHERE user_id=? AND reference_type='enrollment' AND reference_id=? LIMIT 1");$chk->execute([$u['id'],$r['id']]);if(!$chk->fetch()){log_transaction($pdo,(int)$u['id'],'Enrollment '.ucfirst($r['status']),$r['code'].' — '.$r['name'],'enrollment',(int)$r['id']);}}
 $oldT=$pdo->prepare('SELECT id,ticket_no,title,status,created_at FROM tickets WHERE student_id=?');$oldT->execute([$u['id']]);
 foreach($oldT->fetchAll() as $r){$chk=$pdo->prepare("SELECT id FROM transaction_history WHERE user_id=? AND reference_type='ticket' AND reference_id=? LIMIT 1");$chk->execute([$u['id'],$r['id']]);if(!$chk->fetch()){log_transaction($pdo,(int)$u['id'],'Ticket Created',$r['title'].' ('.$r['ticket_no'].')','ticket',(int)$r['id']);}}
}catch(PDOException $e){}
$q=$pdo->prepare('SELECT * FROM transaction_history WHERE user_id=? ORDER BY created_at DESC');$q->execute([$u['id']]);$rows=$q->fetchAll();require 'header.php';?>
<div class="section"><div class="section-intro"><div><h2>Transaction History</h2><p class="muted">A record of your enrollment requests and administration tickets.</p></div><a class="btn" href="enrollment.php">Back to Enrollment</a></div><?php if(!$rows):?><p class="muted">No transactions have been recorded yet.</p><?php endif;?><?php foreach($rows as $r):?><div class="history-row"><div class="history-icon"><?=e(strtoupper(substr($r['action'],0,1)))?></div><div><b><?=e($r['action'])?></b><p><?=e($r['description'])?></p><small class="muted"><?=date('M d, Y · g:i A',strtotime($r['created_at']))?></small></div></div><?php endforeach;?></div><?php require 'footer.php';?>
