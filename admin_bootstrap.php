<?php
require 'config.php';
require_role('admin');

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ticket_updates(id INT AUTO_INCREMENT PRIMARY KEY,ticket_id INT NOT NULL,status VARCHAR(30) NOT NULL,note VARCHAR(255) NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,INDEX idx_ticket_updates(ticket_id,created_at))");
    $cols=$pdo->query("SHOW COLUMNS FROM users LIKE 'account_status'")->fetch();
    if(!$cols) $pdo->exec("ALTER TABLE users ADD COLUMN account_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'");
    // Ticket workflow migration: Open → Ongoing → Resolved/Rejected.
    $pdo->exec("ALTER TABLE tickets MODIFY status ENUM('Open','In Progress','Ongoing','Resolved','Rejected') NOT NULL DEFAULT 'Open'");
    $pdo->exec("UPDATE tickets SET status='Ongoing' WHERE status='In Progress'");
    $pdo->exec("ALTER TABLE tickets MODIFY status ENUM('Open','Ongoing','Resolved','Rejected') NOT NULL DEFAULT 'Open'");
} catch(PDOException $e) {}

$admin_pages = ['admin.php','admin_students.php','admin_enrollment.php','admin_subjects.php','admin_faculty.php','admin_schedules.php','admin_tickets.php','admin_announcements.php','admin_inbox.php'];

if (!empty($_SESSION['admin_flash'])) {
    $admin_flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
} else {
    $admin_flash = ['msg'=>'','error'=>''];
}

if($_SERVER['REQUEST_METHOD']==='POST') {
    $action=$_POST['action']??'';
    $return=basename($_POST['return_to']??'admin.php');
    if(!in_array($return,$admin_pages,true)) $return='admin.php';
    try {
        if($action==='student_status') {
            $id=(int)$_POST['student_id']; $status=$_POST['account_status']??'Active';
            if(in_array($status,['Active','Inactive'],true)) {
                $q=$pdo->prepare("UPDATE users SET account_status=? WHERE id=? AND role='student'"); $q->execute([$status,$id]);
                $admin_flash=['msg'=>'Student account status updated.','error'=>''];
            }
        } elseif($action==='add_subject') {
            $code=trim($_POST['code']??''); $name=trim($_POST['name']??''); $units=max(1,min(12,(int)($_POST['units']??3)));
            $desc=trim($_POST['description']??''); $pre=trim($_POST['prerequisite']??'None');
            if($code===''||$name==='') throw new Exception('Subject code and name are required.');
            $q=$pdo->prepare('INSERT INTO subjects(code,name,units,description,prerequisite) VALUES(?,?,?,?,?)'); $q->execute([$code,$name,$units,$desc,$pre]);
            $admin_flash=['msg'=>'Subject added successfully.','error'=>''];
        } elseif($action==='edit_subject') {
            $id=(int)$_POST['subject_id']; $code=trim($_POST['code']??''); $name=trim($_POST['name']??''); $units=max(1,min(12,(int)($_POST['units']??3)));
            $desc=trim($_POST['description']??''); $pre=trim($_POST['prerequisite']??'None');
            if(!$id||$code===''||$name==='') throw new Exception('Subject code and name are required.');
            $q=$pdo->prepare('UPDATE subjects SET code=?,name=?,units=?,description=?,prerequisite=? WHERE id=?'); $q->execute([$code,$name,$units,$desc,$pre,$id]);
            $admin_flash=['msg'=>'Subject updated successfully.','error'=>''];
        } elseif($action==='add_offering') {
            $subject=(int)$_POST['subject_id']; $faculty=(int)($_POST['faculty_id']??0); $day=trim($_POST['day_name']??''); $start=$_POST['start_time']??''; $end=$_POST['end_time']??''; $room=trim($_POST['room']??''); $section=trim($_POST['section']??'');
            if(!$subject||!$day||!$start||!$end||!$room||!$section) throw new Exception('Complete all schedule fields.');
            if($start >= $end) throw new Exception('End time must be later than start time.');
            $q=$pdo->prepare('INSERT INTO subject_offerings(subject_id,faculty_id,day_name,start_time,end_time,room,section) VALUES(?,?,?,?,?,?,?)'); $q->execute([$subject,$faculty?:null,$day,$start,$end,$room,$section]);
            $admin_flash=['msg'=>'Schedule/section added successfully.','error'=>''];
        } elseif($action==='delete_offering') {
            $id=(int)$_POST['offering_id']; $pdo->prepare('DELETE FROM subject_offerings WHERE id=?')->execute([$id]); $admin_flash=['msg'=>'Schedule/section removed.','error'=>''];
        } elseif($action==='enrollment_action') {
            $id=(int)$_POST['enrollment_id']; $status=$_POST['enrollment_action']??'';
            if(in_array($status,['approved','rejected'],true)) {
                $q=$pdo->prepare("SELECT e.*,s.code,s.name subject,o.day_name,o.start_time,o.end_time,o.room,o.section,u.name faculty_name FROM enrollments e JOIN subjects s ON s.id=e.subject_id LEFT JOIN subject_offerings o ON o.id=e.offering_id LEFT JOIN users u ON u.id=o.faculty_id WHERE e.id=?"); $q->execute([$id]); $r=$q->fetch();
                if($r) {
                    if($status==='approved' && $r['day_name']) {
                        $c=$pdo->prepare("SELECT s.code,s.name FROM schedules sc JOIN subjects s ON s.id=sc.subject_id WHERE sc.student_id=? AND sc.day_name=? AND sc.start_time < ? AND sc.end_time > ? AND sc.subject_id<>?");
                        $c->execute([$r['student_id'],$r['day_name'],$r['end_time'],$r['start_time'],$r['subject_id']]);
                        if($conf=$c->fetch()) { throw new Exception('Enrollment was not approved because it conflicts with '.$conf['code'].' — '.$conf['name'].'.'); }
                    }
                    $pdo->prepare('UPDATE enrollments SET status=? WHERE id=?')->execute([$status,$id]);
                    if($status==='approved' && $r['day_name']) {
                        $check=$pdo->prepare('SELECT id FROM schedules WHERE student_id=? AND subject_id=? AND term_id=?'); $check->execute([$r['student_id'],$r['subject_id'],$r['term_id']]);
                        if(!$check->fetch()) { $ins=$pdo->prepare('INSERT INTO schedules(student_id,subject_id,day_name,start_time,end_time,room,term_id) VALUES(?,?,?,?,?,?,?)'); $ins->execute([$r['student_id'],$r['subject_id'],$r['day_name'],$r['start_time'],$r['end_time'],$r['room'],$r['term_id']]); }
                    }
                    $label=$r['code'].' — '.$r['subject'];
                    notify_user($pdo,(int)$r['student_id'],'Enrollment '.ucfirst($status),$label.' has been '.($status==='approved'?'approved. Your schedule has been updated.':'rejected by Administration. Please review your options.'),'Enrollment');
                    log_transaction($pdo,(int)$r['student_id'],'Enrollment '.ucfirst($status),$label,'enrollment',$id);
                    $admin_flash=['msg'=>'Enrollment request updated.','error'=>''];
                }
            }
        } elseif($action==='ticket_status') {
            $id=(int)$_POST['ticket_id']; $status=$_POST['ticket_status']??'';
            if(in_array($status,['Open','Ongoing','Resolved','Rejected'],true)) {
                $q=$pdo->prepare('SELECT * FROM tickets WHERE id=?'); $q->execute([$id]); $r=$q->fetch();
                if($r) {
                    $pdo->prepare('UPDATE tickets SET status=? WHERE id=?')->execute([$status,$id]);
                    $pdo->prepare('INSERT INTO ticket_updates(ticket_id,status,note) VALUES(?,?,?)')->execute([$id,$status,'Status updated by Administration.']);
                    notify_user($pdo,(int)$r['student_id'],'Ticket status updated',$r['ticket_no'].' — '.$r['title'].' is now '.$status.'.','Ticket');
                    log_transaction($pdo,(int)$r['student_id'],'Ticket Status Updated',$r['ticket_no'].' changed to '.$status,'ticket',$id);
                    $admin_flash=['msg'=>'Ticket status updated.','error'=>''];
                }
            }
        } elseif($action==='feedback_status') {
            $id=(int)$_POST['feedback_id']; $status=$_POST['feedback_action']??'';
            if(in_array($status,['New','In Review','Resolved'],true)) {
                $q=$pdo->prepare('SELECT * FROM feedback WHERE id=?'); $q->execute([$id]); $r=$q->fetch();
                if($r) {
                    $pdo->prepare('UPDATE feedback SET status=? WHERE id=?')->execute([$status,$id]);
                    if(!empty($r['student_id'])) { $u=$pdo->prepare('SELECT id FROM users WHERE student_id=? LIMIT 1'); $u->execute([$r['student_id']]); if($uid=$u->fetchColumn()) notify_user($pdo,(int)$uid,'Administration request updated','Your request "'.$r['subject'].'" is now '.$status.'.','Administration'); }
                    $admin_flash=['msg'=>'Administration inbox item updated.','error'=>''];
                }
            }
        } elseif($action==='publish_announcement') {
            $titleA=trim($_POST['announcement_title']??''); $body=trim($_POST['announcement_body']??''); $type=$_POST['announcement_type']??'General';
            if($titleA===''||$body==='') throw new Exception('Announcement title and message are required.');
            $q=$pdo->prepare('INSERT INTO announcements(title,body,type,author_id) VALUES(?,?,?,?)'); $q->execute([$titleA,$body,$type,$_SESSION['user']['id']]);
            $active=$pdo->query("SELECT id FROM users WHERE role='student' AND account_status='Active'")->fetchAll();
            foreach($active as $s) notify_user($pdo,(int)$s['id'],'New announcement',$titleA,'Announcement');
            $admin_flash=['msg'=>'Announcement published and student notifications sent.','error'=>''];
        } elseif($action==='delete_announcement') {
            $pdo->prepare('DELETE FROM announcements WHERE id=?')->execute([(int)$_POST['announcement_id']]); $admin_flash=['msg'=>'Announcement deleted.','error'=>''];
        }
    } catch(Throwable $ex) { $admin_flash=['msg'=>'','error'=>$ex->getMessage()]; }
    $_SESSION['admin_flash']=$admin_flash;
    header('Location: '.$return); exit;
}

function admin_flash_html() {
    global $admin_flash;
    if(!empty($admin_flash['msg'])) echo '<div class="success">'.e($admin_flash['msg']).'</div>';
    if(!empty($admin_flash['error'])) echo '<div class="auth-alert">'.e($admin_flash['error']).'</div>';
}
function admin_form_return($page) { return '<input type="hidden" name="return_to" value="'.e($page).'">'; }
