<?php
require 'config.php';
require_login();
function get_team_by_offering(PDO $pdo,int $offeringId): ?array {
  $q=$pdo->prepare("SELECT ct.*,o.subject_id,o.faculty_id,o.day_name,o.start_time,o.end_time,o.room,o.section,s.code,s.name,s.units,s.description FROM course_teams ct JOIN subject_offerings o ON o.id=ct.offering_id JOIN subjects s ON s.id=o.subject_id WHERE ct.offering_id=? LIMIT 1");
  $q->execute([$offeringId]); return $q->fetch() ?: null;
}
function student_can_access_team(PDO $pdo,int $teamId,int $studentId): bool {
  $q=$pdo->prepare("SELECT 1 FROM course_teams ct JOIN enrollments e ON e.offering_id=ct.offering_id WHERE ct.id=? AND e.student_id=? AND e.status='approved' LIMIT 1");
  $q->execute([$teamId,$studentId]); return (bool)$q->fetchColumn();
}
function faculty_can_access_team(PDO $pdo,int $teamId,int $facultyId): bool {
  $q=$pdo->prepare("SELECT 1 FROM course_teams ct JOIN subject_offerings o ON o.id=ct.offering_id WHERE ct.id=? AND o.faculty_id=? LIMIT 1");
  $q->execute([$teamId,$facultyId]); return (bool)$q->fetchColumn();
}
function ensure_team(PDO $pdo,int $offeringId): int {
  $q=$pdo->prepare('INSERT IGNORE INTO course_teams(offering_id) VALUES(?)');$q->execute([$offeringId]);
  $q=$pdo->prepare('SELECT id FROM course_teams WHERE offering_id=?');$q->execute([$offeringId]); return (int)$q->fetchColumn();
}
function team_notify_students(PDO $pdo,int $teamId,string $title,string $message,string $type='Class') {
  $q=$pdo->prepare("SELECT DISTINCT e.student_id FROM course_teams ct JOIN enrollments e ON e.offering_id=ct.offering_id WHERE ct.id=? AND e.status='approved'");$q->execute([$teamId]);
  foreach($q->fetchAll() as $r) notify_user($pdo,(int)$r['student_id'],$title,$message,$type);
}
function team_label(array $t): string { return $t['code'].' — '.$t['name'].' · Section '.$t['section']; }

function ensure_team_feature_schema(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS team_materials (id INT AUTO_INCREMENT PRIMARY KEY, team_id INT NOT NULL, title VARCHAR(180) NOT NULL, description TEXT NULL, material_type ENUM('Material','Syllabus') NOT NULL DEFAULT 'Material', file_path VARCHAR(500) NULL, link_url VARCHAR(1000) NULL, created_by INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (team_id) REFERENCES course_teams(id) ON DELETE CASCADE, FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE, INDEX idx_team_materials_team_created (team_id,created_at)) ENGINE=InnoDB");
  $cols=$pdo->query('SHOW COLUMNS FROM assignments')->fetchAll(PDO::FETCH_COLUMN,0);
  if(!in_array('attachment_path',$cols,true)) $pdo->exec("ALTER TABLE assignments ADD COLUMN attachment_path VARCHAR(500) NULL");
  if(!in_array('attachment_link',$cols,true)) $pdo->exec("ALTER TABLE assignments ADD COLUMN attachment_link VARCHAR(1000) NULL");
  $cols=$pdo->query('SHOW COLUMNS FROM assignment_submissions')->fetchAll(PDO::FETCH_COLUMN,0);
  if(!in_array('submission_link',$cols,true)) $pdo->exec("ALTER TABLE assignment_submissions ADD COLUMN submission_link VARCHAR(1000) NULL");
}
ensure_team_feature_schema($pdo);
function team_upload(array $file, string $dir, array $allowed, int $maxBytes, string $prefix): ?string {
  if(empty($file['name'])) return null;
  if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new Exception('The uploaded file could not be processed.');
  if(($file['size']??0)>$maxBytes) throw new Exception('Uploaded file is too large. Maximum size is 20 MB.');
  $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
  if(!in_array($ext,$allowed,true)) throw new Exception('That file type is not supported.');
  if(!is_dir($dir)) mkdir($dir,0775,true);
  $name=$prefix.'_'.date('YmdHis').'_'.bin2hex(random_bytes(5)).'.'.$ext;
  $dest=$dir.'/'.$name;
  if(!move_uploaded_file($file['tmp_name'],$dest)) throw new Exception('Could not save the uploaded file.');
  return $dest;
}
function valid_external_link(string $url): bool { return $url!=='' && filter_var($url,FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i',$url); }
