<?php
require 'config.php';
require_role('faculty');
if (!isset($_SESSION['faculty_flash'])) $_SESSION['faculty_flash']=['msg'=>'','error'=>''];
$faculty_flash=$_SESSION['faculty_flash']; unset($_SESSION['faculty_flash']);
function faculty_form_return($page){return '<input type="hidden" name="return_to" value="'.e($page).'">';}
function faculty_flash_html(){global $faculty_flash;if(!empty($faculty_flash['msg']))echo '<div class="success">'.e($faculty_flash['msg']).'</div>';if(!empty($faculty_flash['error']))echo '<div class="auth-alert">'.e($faculty_flash['error']).'</div>';}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??''; $return=basename($_POST['return_to']??'faculty.php');
  $allowed=['faculty.php','faculty_classes.php','faculty_schedule.php','faculty_students.php','faculty_enrollment.php','faculty_announcements.php'];
  if(!in_array($return,$allowed,true))$return='faculty.php';
  try{
    $fid=(int)$_SESSION['user']['id'];
    if($action==='faculty_enrollment_action'){
      $eid=(int)($_POST['enrollment_id']??0);$status=$_POST['status']??'';
      if(!in_array($status,['approved','rejected'],true))throw new Exception('Invalid enrollment action.');
      $q=$pdo->prepare("SELECT e.*,u.name student,s.code,s.name subject,o.day_name,o.start_time,o.end_time,o.room,o.section,f.name faculty_name FROM enrollments e JOIN users u ON u.id=e.student_id JOIN subjects s ON s.id=e.subject_id LEFT JOIN subject_offerings o ON o.id=e.offering_id LEFT JOIN users f ON f.id=o.faculty_id WHERE e.id=? AND o.faculty_id=? LIMIT 1");$q->execute([$eid,$fid]);$r=$q->fetch();
      if(!$r)throw new Exception('Enrollment request not found or is not assigned to you.');
      if($status==='approved'&&$r['day_name']){
        $c=$pdo->prepare("SELECT s.code,s.name FROM schedules sc JOIN subjects s ON s.id=sc.subject_id WHERE sc.student_id=? AND sc.day_name=? AND sc.start_time < ? AND sc.end_time > ? AND sc.subject_id<>? AND (sc.term_id IS NULL OR sc.term_id=(SELECT term_id FROM enrollments WHERE id=?)) LIMIT 1");$c->execute([$r['student_id'],$r['day_name'],$r['end_time'],$r['start_time'],$r['subject_id'],$eid]);
        if($conf=$c->fetch())throw new Exception('Cannot approve: the schedule conflicts with '.$conf['code'].' — '.$conf['name'].'.');
      }
      $pdo->prepare('UPDATE enrollments SET status=? WHERE id=?')->execute([$status,$eid]);
      if($status==='approved'&&$r['day_name']){
        $check=$pdo->prepare('SELECT id FROM schedules WHERE student_id=? AND subject_id=? AND (term_id=? OR term_id IS NULL)');$check->execute([$r['student_id'],$r['subject_id'],$r['term_id']]);
        if(!$check->fetch()){$ins=$pdo->prepare('INSERT INTO schedules(student_id,subject_id,day_name,start_time,end_time,room,term_id) VALUES(?,?,?,?,?,?,?)');$ins->execute([$r['student_id'],$r['subject_id'],$r['day_name'],$r['start_time'],$r['end_time'],$r['room'],$r['term_id']]);}
      }
      notify_user($pdo,(int)$r['student_id'],'Enrollment '.ucfirst($status),$r['code'].' — '.$r['subject'].' has been '.($status==='approved'?'approved by '.$_SESSION['user']['name'].'. Your schedule has been updated.':'rejected by '.$_SESSION['user']['name'].'. Please review another available schedule.'),'Enrollment');
      log_transaction($pdo,(int)$r['student_id'],'Enrollment '.ucfirst($status),$r['code'].' — '.$r['subject'],'enrollment',$eid);
      $_SESSION['faculty_flash']=['msg'=>'Enrollment request updated successfully.','error'=>''];
    } elseif($action==='faculty_publish_announcement'){
      $titleA=trim($_POST['announcement_title']??'');$body=trim($_POST['announcement_body']??'');$type=$_POST['announcement_type']??'General';$subjectId=(int)($_POST['subject_id']??0);
      if($titleA===''||$body==='')throw new Exception('Announcement title and message are required.');
      if(!in_array($type,['General','Important','Event'],true))$type='General';
      if($subjectId){$chk=$pdo->prepare('SELECT DISTINCT o.subject_id FROM subject_offerings o WHERE o.subject_id=? AND o.faculty_id=?');$chk->execute([$subjectId,$fid]);if(!$chk->fetch())throw new Exception('You can only publish class announcements for your assigned subjects.');}
      $cols=$pdo->query('SHOW COLUMNS FROM announcements')->fetchAll(PDO::FETCH_COLUMN,0);
      if(!in_array('target_subject_id',$cols,true))$pdo->exec('ALTER TABLE announcements ADD COLUMN target_subject_id INT NULL');
      $q=$pdo->prepare('INSERT INTO announcements(title,body,type,author_id,target_subject_id) VALUES(?,?,?,?,?)');$q->execute([$titleA,$body,$type,$fid,$subjectId?:null]);$aid=(int)$pdo->lastInsertId();
      if($subjectId){$s=$pdo->prepare("SELECT DISTINCT e.student_id FROM enrollments e JOIN subject_offerings o ON o.id=e.offering_id WHERE e.status='approved' AND o.subject_id=? AND o.faculty_id=?");$s->execute([$subjectId,$fid]);$students=$s->fetchAll();foreach($students as $st)notify_user($pdo,(int)$st['student_id'],'Class announcement',$titleA,'Announcement');}
      else {$students=$pdo->query("SELECT id FROM users WHERE role='student' AND COALESCE(account_status,'Active')='Active'")->fetchAll();foreach($students as $st)notify_user($pdo,(int)$st['id'],'New announcement',$titleA,'Announcement');}
      $_SESSION['faculty_flash']=['msg'=>'Announcement published'.($subjectId?' to enrolled students in the selected class':' to active students').'.','error'=>''];
    } elseif($action==='faculty_delete_announcement'){
      $aid=(int)($_POST['announcement_id']??0);$q=$pdo->prepare('DELETE FROM announcements WHERE id=? AND author_id=?');$q->execute([$aid,$fid]);$_SESSION['faculty_flash']=['msg'=>$q->rowCount()?'Announcement deleted.':'You can only delete your own announcements.','error'=>''];
    }
  }catch(Throwable $ex){$_SESSION['faculty_flash']=['msg'=>'','error'=>$ex->getMessage()];}
  header('Location: '.$return);exit;
}
