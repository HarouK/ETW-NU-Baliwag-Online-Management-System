<?php
session_start();

$host = '127.0.0.1';
$db   = 'etw_nu_baliwag';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed. Check XAMPP MySQL and config.php.');
}

// Automatic feature-table/column upgrades for the school prototype.
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(180) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(30) NOT NULL DEFAULT 'General',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_notifications_user (user_id, is_read, created_at)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS transaction_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(80) NOT NULL,
        reference_type VARCHAR(40) NULL,
        reference_id INT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_history_user (user_id, created_at)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS academic_terms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        term_key VARCHAR(40) UNIQUE NOT NULL,
        label VARCHAR(120) NOT NULL,
        is_current TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0
    )");
    $pdo->exec("INSERT IGNORE INTO academic_terms(term_key,label,is_current,sort_order) VALUES
        ('2026-2027-2','2nd Semester AY 2026–2027',1,2),
        ('2026-2027-1','1st Semester AY 2026–2027',0,1)");
    $curTerm = $pdo->query("SELECT id FROM academic_terms WHERE is_current=1 ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($curTerm) {
        foreach (['enrollments','schedules'] as $table) {
            $cols = $pdo->query("SHOW COLUMNS FROM {$table} LIKE 'term_id'")->fetch();
            if (!$cols) $pdo->exec("ALTER TABLE {$table} ADD COLUMN term_id INT NULL");
            $pdo->exec("UPDATE {$table} SET term_id=".(int)$curTerm." WHERE term_id IS NULL");
        }
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS course_teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        offering_id INT NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (offering_id) REFERENCES subject_offerings(id) ON DELETE CASCADE
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        title VARCHAR(180) NOT NULL,
        description TEXT NULL,
        due_at DATETIME NULL,
        points INT NOT NULL DEFAULT 100,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES course_teams(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_assignments_team_due (team_id,due_at)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS assignment_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assignment_id INT NOT NULL,
        student_id INT NOT NULL,
        submission_text TEXT NULL,
        file_path VARCHAR(255) NULL,
        status ENUM('Submitted','Late','Graded') NOT NULL DEFAULT 'Submitted',
        grade INT NULL,
        feedback TEXT NULL,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_assignment_student (assignment_id,student_id),
        FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_submission_student (student_id,submitted_at)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS team_meetings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        title VARCHAR(180) NOT NULL,
        description TEXT NULL,
        starts_at DATETIME NOT NULL,
        duration_minutes INT NOT NULL DEFAULT 60,
        meeting_url VARCHAR(500) NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES course_teams(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_meetings_team_start (team_id,starts_at)
    )");
    // Automatically create a team for every existing subject offering.
    $pdo->exec("INSERT IGNORE INTO course_teams(offering_id) SELECT id FROM subject_offerings");
    $pdo->exec("CREATE TABLE IF NOT EXISTS academic_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        term_id INT NOT NULL,
        subject_id INT NOT NULL,
        teacher VARCHAR(150) NULL,
        grade VARCHAR(20) NOT NULL,
        remarks VARCHAR(100) NOT NULL DEFAULT 'Completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (term_id) REFERENCES academic_terms(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        INDEX idx_records_user_term (user_id, term_id)
    )");
    $priorTerm = $pdo->query("SELECT id FROM academic_terms WHERE term_key='2026-2027-1' LIMIT 1")->fetchColumn();
    $studentId = $pdo->query("SELECT id FROM users WHERE role='student' ORDER BY id LIMIT 1")->fetchColumn();
    if ($priorTerm && $studentId) {
        $recordCount = $pdo->prepare('SELECT COUNT(*) FROM academic_records WHERE user_id=? AND term_id=?');
        $recordCount->execute([(int)$studentId,(int)$priorTerm]);
        if ((int)$recordCount->fetchColumn() === 0) {
            $seed = $pdo->prepare('INSERT INTO academic_records(user_id,term_id,subject_id,teacher,grade,remarks) VALUES(?,?,?,?,?,?)');
            $records = [
                [2,'Maria Santos','1.50','Passed'], [3,'Maria Santos','1.75','Passed'],
                [4,'Maria Santos','1.75','Passed'], [6,'Maria Santos','1.25','Passed'],
                [10,'Maria Santos','2.00','Passed']
            ];
            foreach ($records as $r) $seed->execute([(int)$studentId,(int)$priorTerm,(int)$r[0],$r[1],$r[2],$r[3]]);
        }
    }
    $subCols = $pdo->query("SHOW COLUMNS FROM subjects")->fetchAll(PDO::FETCH_COLUMN,0);
    if (!in_array('description',$subCols,true)) $pdo->exec("ALTER TABLE subjects ADD COLUMN description TEXT NULL");
    if (!in_array('prerequisite',$subCols,true)) $pdo->exec("ALTER TABLE subjects ADD COLUMN prerequisite VARCHAR(180) NULL");
    $pdo->exec("UPDATE subjects SET description=CASE code
        WHEN 'ITP 101' THEN 'Introduction to information technology concepts, systems, and practical laboratory work.'
        WHEN 'ITE 102' THEN 'Fundamental computing concepts, digital systems, and introductory computer applications.'
        WHEN 'MATH 101' THEN 'College-level algebraic concepts, equations, functions, and problem solving.'
        WHEN 'ENG 101' THEN 'Communication principles and practical writing and speaking for academic and professional settings.'
        WHEN 'NSTP 1' THEN 'Civic welfare training and community-oriented service activities.'
        WHEN 'PATH-FIT 1' THEN 'Movement competency, physical fitness, and wellness activities.'
        WHEN 'ITP 102' THEN 'Foundations of programming, algorithms, variables, control structures, and problem solving.'
        WHEN 'ITE 103' THEN 'Discrete mathematical structures used in computing and information technology.'
        WHEN 'ITP 103' THEN 'Data structures, algorithm analysis, and implementation of common abstract data types.'
        WHEN 'GE 101' THEN 'Study and appreciation of visual, performing, and creative arts.'
        ELSE description END,
        prerequisite=CASE code
        WHEN 'ITP 102' THEN 'ITP 101 or equivalent'
        WHEN 'ITP 103' THEN 'ITP 102 or equivalent'
        ELSE COALESCE(prerequisite,'None') END");
} catch (PDOException $e) {
    // Existing prototype remains usable if an optional upgrade is unavailable.
}

require_once __DIR__.'/feature_migration.php';

function require_login() { if (empty($_SESSION['user'])) { header('Location: index.php'); exit; } }
function require_role($roles) { require_login(); if (!in_array($_SESSION['user']['role'], (array)$roles, true)) { http_response_code(403); die('Access denied.'); } }
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function notify_user(PDO $pdo, int $userId, string $title, string $message, string $type='General') { try { $q=$pdo->prepare('INSERT INTO notifications(user_id,title,message,type) VALUES(?,?,?,?)'); $q->execute([$userId,$title,$message,$type]); } catch (PDOException $e) {} }
function log_transaction(PDO $pdo, int $userId, string $action, string $description, ?string $referenceType=null, ?int $referenceId=null) { try { $q=$pdo->prepare('INSERT INTO transaction_history(user_id,action,reference_type,reference_id,description) VALUES(?,?,?,?,?)'); $q->execute([$userId,$action,$referenceType,$referenceId,$description]); } catch (PDOException $e) {} }
function unread_notifications(PDO $pdo, int $userId): int { try { $q=$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0'); $q->execute([$userId]); return (int)$q->fetchColumn(); } catch (PDOException $e) { return 0; } }
function get_terms(PDO $pdo): array { try { return $pdo->query('SELECT * FROM academic_terms ORDER BY sort_order DESC')->fetchAll(); } catch (PDOException $e) { return []; } }
function current_term(PDO $pdo): ?array { try { $q=$pdo->query("SELECT * FROM academic_terms WHERE is_current=1 ORDER BY id DESC LIMIT 1"); return $q->fetch() ?: null; } catch (PDOException $e) { return null; } }
function selected_term(PDO $pdo): ?array {
    $terms=get_terms($pdo); if (!$terms) return null;
    $key=$_GET['term']??'';
    foreach($terms as $t) if($t['term_key']===$key) return $t;
    foreach($terms as $t) if((int)$t['is_current']===1) return $t;
    return $terms[0];
}
function money($amount): string { return '₱'.number_format((float)$amount,2); }
const PROTOTYPE_TUITION_PER_UNIT = 1500;
const PROTOTYPE_MISC_FEE = 3500;
