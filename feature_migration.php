<?php
// Shared prototype feature migrations. Included by config.php after the base schema is available.
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        offering_id INT NOT NULL,
        student_id INT NOT NULL,
        attendance_date DATE NOT NULL,
        status ENUM('Present','Late','Absent','Excused') NOT NULL DEFAULT 'Present',
        note VARCHAR(255) NULL,
        recorded_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_attendance (offering_id,student_id,attendance_date),
        FOREIGN KEY (offering_id) REFERENCES subject_offerings(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_attendance_student (student_id,attendance_date)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS gradebook_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        offering_id INT NOT NULL,
        student_id INT NOT NULL,
        assessment VARCHAR(120) NOT NULL,
        score DECIMAL(6,2) NULL,
        max_score DECIMAL(6,2) NOT NULL DEFAULT 100,
        feedback VARCHAR(255) NULL,
        recorded_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_gradebook (offering_id,student_id,assessment),
        FOREIGN KEY (offering_id) REFERENCES subject_offerings(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_gradebook_student (student_id,offering_id)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS important_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        description TEXT NULL,
        event_date DATE NOT NULL,
        event_type ENUM('Academic','Enrollment','Exam','Holiday','Other') NOT NULL DEFAULT 'Academic',
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_important_dates (event_date)
    )");
    // Seed a few clearly labeled prototype dates only when the table is empty.
    if ((int)$pdo->query("SELECT COUNT(*) FROM important_dates")->fetchColumn()===0) {
        $q=$pdo->prepare("INSERT INTO important_dates(title,description,event_date,event_type,created_by) VALUES(?,?,?,?,?)");
        $admin=$pdo->query("SELECT id FROM users WHERE role='admin' ORDER BY id LIMIT 1")->fetchColumn();
        $q->execute(['Enrollment deadline','Review and finalize enrollment requests.','2026-09-25','Enrollment',$admin?:null]);
        $q->execute(['Midterm examinations','Check the published examination schedule.','2026-10-10','Exam',$admin?:null]);
        $q->execute(['Final examinations','Final examination period.','2026-11-15','Exam',$admin?:null]);
    }
} catch (PDOException $e) {
    // Optional feature tables should not prevent the core prototype from loading.
}
