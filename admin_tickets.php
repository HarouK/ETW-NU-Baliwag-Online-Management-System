<?php require 'admin_bootstrap.php'; $title='Ticket Management';
$filter=$_GET['status']??'All';
$allowed=['All','Rejected','Open','Ongoing','Resolved']; if(!in_array($filter,$allowed,true))$filter='All';
$sql="SELECT t.*,u.name student,u.student_id FROM tickets t JOIN users u ON u.id=t.student_id"; $params=[]; if($filter!=='All'){$sql.=' WHERE t.status=?';$params[]=$filter;} $sql.=' ORDER BY t.created_at DESC LIMIT 100'; $q=$pdo->prepare($sql);$q->execute($params);$tickets=$q->fetchAll(); require 'header.php'; admin_flash_html(); ?>
<section class="section">
<div class="card-head"><div><span class="eyebrow">SUPPORT</span><h2>Ticket Management</h2><p class="muted">Manage tickets using four compact status actions.</p></div><span class="muted">Latest 100 tickets</span></div>
<div class="ticket-status-filter">
<?php foreach($allowed as $st):?><a class="ticket-filter <?= $filter===$st?'active':'' ?>" href="admin_tickets.php<?= $st==='All'?'':'?status='.urlencode($st) ?>"><?=e($st==='Resolved'?'Resolved':$st)?></a><?php endforeach;?>
</div>
<div class="table-scroll"><table class="table"><tr><th>Ticket</th><th>Student</th><th>Posted</th><th>Status</th><th>Update Status</th></tr>
<?php foreach($tickets as $t): ?>
<tr><td><b><?=e($t['ticket_no'])?></b><br><?=e($t['title'])?></td><td><?=e($t['student'])?><br><span class="muted"><?=e($t['student_id']?:'')?></span></td><td><?=date('M d, Y g:i A',strtotime($t['created_at']))?></td><td><span class="badge <?=strtolower(str_replace(' ','-',e($t['status'])))?>"><?=e($t['status'])?></span></td><td><form method="post" class="ticket-status-actions">
<?=admin_form_return('admin_tickets.php'.($filter!=='All'?'?status='.urlencode($filter):''))?><input type="hidden" name="action" value="ticket_status"><input type="hidden" name="ticket_id" value="<?=e($t['id'])?>">
<?php foreach(['Rejected','Open','Ongoing','Resolved'] as $st):?><button type="submit" name="ticket_status" value="<?=e($st)?>" class="ticket-status-btn <?= $t['status']===$st?'active':'' ?>"><?=e($st)?></button><?php endforeach;?>
</form></td></tr>
<?php endforeach; ?>
<?php if(!$tickets):?><tr><td colspan="5" class="muted">No tickets found for this status.</td></tr><?php endif;?>
</table></div></section>
<?php require 'footer.php'; ?>