CREATE DATABASE IF NOT EXISTS etw_nu_baliwag CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE etw_nu_baliwag;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(150) UNIQUE NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('student','faculty','admin') NOT NULL DEFAULT 'student',
 student_id VARCHAR(30),
 program VARCHAR(120),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 avatar_path VARCHAR(255) NULL
);

CREATE TABLE subjects (
 id INT AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(30) NOT NULL UNIQUE,
 name VARCHAR(150) NOT NULL,
 units INT NOT NULL,
 section VARCHAR(50),
 faculty_id INT NULL,
 description TEXT NULL,
 prerequisite VARCHAR(180) NULL,
 FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE enrollments (
 id INT AUTO_INCREMENT PRIMARY KEY,
 student_id INT NOT NULL,
 subject_id INT NOT NULL,
 status ENUM('pending','approved','rejected') DEFAULT 'pending',
 offering_id INT NULL,
 term_id INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY unique_student_subject (student_id, subject_id),
 FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE tickets (
 id INT AUTO_INCREMENT PRIMARY KEY,
 ticket_no VARCHAR(40) UNIQUE NOT NULL,
 student_id INT NOT NULL,
 title VARCHAR(180) NOT NULL,
 description TEXT NOT NULL,
 status ENUM('Open','Ongoing','Resolved','Rejected') DEFAULT 'Open',
 office VARCHAR(100) DEFAULT 'Support Services',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE announcements (
 id INT AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL,
 body TEXT NOT NULL,
 type ENUM('General','Important','Event') DEFAULT 'General',
 author_id INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE subject_offerings (
 id INT AUTO_INCREMENT PRIMARY KEY,
 subject_id INT NOT NULL,
 faculty_id INT NULL,
 day_name VARCHAR(20) NOT NULL,
 start_time TIME NOT NULL,
 end_time TIME NOT NULL,
 room VARCHAR(80) NOT NULL,
 section VARCHAR(50) NOT NULL,
 FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
 FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE schedules (
 id INT AUTO_INCREMENT PRIMARY KEY,
 student_id INT NOT NULL,
 subject_id INT NOT NULL,
 day_name VARCHAR(20) NOT NULL,
 start_time TIME NOT NULL,
 end_time TIME NOT NULL,
 room VARCHAR(80) NOT NULL,
 term_id INT NULL,
 FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);


CREATE TABLE academic_terms (
 id INT AUTO_INCREMENT PRIMARY KEY,
 term_key VARCHAR(40) UNIQUE NOT NULL,
 label VARCHAR(120) NOT NULL,
 is_current TINYINT(1) NOT NULL DEFAULT 0,
 sort_order INT NOT NULL DEFAULT 0
);

INSERT INTO academic_terms(term_key,label,is_current,sort_order) VALUES
('2026-2027-2','2nd Semester AY 2026–2027',1,2),
('2026-2027-1','1st Semester AY 2026–2027',0,1);

CREATE TABLE academic_records (
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
 FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

INSERT INTO users(name,email,password_hash,role,student_id,program) VALUES
('Juan Dela Cruz','student@nu-baliwag.edu.ph',SHA2('password',256),'student','12345678','BS Information Technology'),
('ETW Administrator','admin@nu-baliwag.edu.ph',SHA2('password',256),'admin',NULL,'Administration'),
('Maria Santos','faculty@nu-baliwag.edu.ph',SHA2('password',256),'faculty',NULL,'BS Information Technology');

INSERT INTO subjects(code,name,units,section,faculty_id,description,prerequisite) VALUES
('ITP 101','Information Technology',3,'IT-101',3,'Introduction to information technology concepts, systems, and practical laboratory work.','None'),
('ITE 102','Introduction to Computing',3,'IT-101',3,'Fundamental computing concepts, digital systems, and introductory computer applications.','None'),
('MATH 101','College Algebra',3,'IT-101',3,'College-level algebraic concepts, equations, functions, and problem solving.','None'),
('ENG 101','Purposive Communication',3,'IT-101',3,'Communication principles and practical writing and speaking for academic and professional settings.','None'),
('NSTP 1','Civic Welfare Training Service',3,'IT-101',3,'Civic welfare training and community-oriented service activities.','None'),
('PATH-FIT 1','Movement Competency Training',2,'IT-101',3,'Movement competency, physical fitness, and wellness activities.','None'),
('ITP 102','Computer Programming 1',3,'IT-102',3,'Foundations of programming, algorithms, variables, control structures, and problem solving.','ITP 101 or equivalent'),
('ITE 103','Discrete Structures',3,'IT-103',3,'Discrete mathematical structures used in computing and information technology.','None'),
('ITP 103','Data Structures and Algorithms',3,'IT-103',3,'Data structures, algorithm analysis, and implementation of common abstract data types.','ITP 102 or equivalent'),
('GE 101','Art Appreciation',3,'GE-101',3,'Study and appreciation of visual, performing, and creative arts.','None');

INSERT INTO enrollments(student_id,subject_id,status,offering_id) VALUES
(1,1,'approved',1),(1,2,'approved',3),(1,3,'approved',5),(1,4,'approved',7),(1,5,'approved',9),(1,6,'approved',11);

INSERT INTO schedules(student_id,subject_id,day_name,start_time,end_time,room) VALUES
(1,1,'Monday','08:00:00','11:00:00','IT Lab 1'),
(1,6,'Monday','13:00:00','16:00:00','Gymnasium'),
(1,5,'Monday','16:00:00','17:30:00','Room 201'),
(1,2,'Tuesday','10:00:00','12:00:00','IT Lab 2');

INSERT INTO tickets(ticket_no,student_id,title,description,status,office) VALUES
('#TCK-2034-0023',1,'Enrollment Concern','I would like to ask about my enrollment request.','Open','Registrar''s Office'),
('#TCK-2034-0018',1,'Printer not Working','The printer in the IT laboratory is not working properly.','In Progress','IT Support'),
('#TCK-2034-0009',1,'Account Activation','My account activation request has been completed.','Resolved','IT Services');


INSERT INTO subject_offerings(subject_id,faculty_id,day_name,start_time,end_time,room,section) VALUES
(1,3,'Monday','08:00:00','11:00:00','IT Lab 1','IT-101'),
(1,3,'Wednesday','08:00:00','11:00:00','IT Lab 2','IT-102'),
(2,3,'Tuesday','10:00:00','12:00:00','IT Lab 2','IT-101'),
(2,3,'Thursday','10:00:00','12:00:00','Room 304','IT-102'),
(3,3,'Monday','09:00:00','12:00:00','Room 201','IT-101'),
(3,3,'Wednesday','13:00:00','16:00:00','Room 201','IT-102'),
(4,3,'Tuesday','08:00:00','11:00:00','Room 205','IT-101'),
(4,3,'Thursday','13:00:00','16:00:00','Room 205','IT-102'),
(5,3,'Monday','16:00:00','17:30:00','Room 201','IT-101'),
(5,3,'Friday','13:00:00','14:30:00','Room 202','IT-102'),
(6,3,'Monday','13:00:00','16:00:00','Gymnasium','IT-101'),
(6,3,'Wednesday','09:00:00','12:00:00','Gymnasium','IT-102'),
(7,3,'Tuesday','13:00:00','16:00:00','IT Lab 1','IT-102'),
(7,3,'Friday','08:00:00','11:00:00','IT Lab 2','IT-103'),
(8,3,'Wednesday','10:00:00','13:00:00','Room 302','IT-103'),
(8,3,'Thursday','08:00:00','11:00:00','Room 302','IT-104'),
(9,3,'Thursday','13:00:00','16:00:00','IT Lab 1','IT-103'),
(9,3,'Friday','13:00:00','16:00:00','IT Lab 1','IT-104'),
(10,3,'Tuesday','16:00:00','19:00:00','Room 305','GE-101'),
(10,3,'Saturday','08:00:00','11:00:00','Room 305','GE-102');

INSERT INTO announcements(title,body,type,author_id) VALUES
('Enrollment for 2nd Semester AY 2024–2025','Enrollment will start on May 20, 2024.','Important',2),
('Intramurals 2024','Join the annual NU Baliwag Intramurals!','Event',2),
('Class Suspension','Classes are suspended on May 17, 2024.','Important',2),
('ID Validation Schedule','Please check the ID validation schedule.','General',2);

CREATE TABLE IF NOT EXISTS feedback (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(150) NOT NULL,
 student_id VARCHAR(30) NULL,
 type ENUM('Feedback','Problem','Question','Suggestion') NOT NULL DEFAULT 'Feedback',
 subject VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 status ENUM('New','In Review','Resolved') NOT NULL DEFAULT 'New',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 avatar_path VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS notifications (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 title VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 type VARCHAR(30) NOT NULL DEFAULT 'General',
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_notifications_user (user_id,is_read,created_at)
);

CREATE TABLE IF NOT EXISTS transaction_history (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 action VARCHAR(80) NOT NULL,
 reference_type VARCHAR(40) NULL,
 reference_id INT NULL,
 description TEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_history_user (user_id,created_at)
);

CREATE TABLE IF NOT EXISTS ticket_updates (
 id INT AUTO_INCREMENT PRIMARY KEY,
 ticket_id INT NOT NULL,
 status VARCHAR(30) NOT NULL,
 note VARCHAR(255) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
 INDEX idx_ticket_updates(ticket_id,created_at)
);


INSERT INTO academic_records(user_id,term_id,subject_id,teacher,grade,remarks) VALUES
(1,2,2,'Maria Santos','1.50','Passed'),
(1,2,3,'Maria Santos','1.75','Passed'),
(1,2,4,'Maria Santos','1.75','Passed'),
(1,2,6,'Maria Santos','1.25','Passed'),
(1,2,10,'Maria Santos','2.00','Passed');

-- Class Teams / Assignments / Online Group Meetings
CREATE TABLE IF NOT EXISTS course_teams (
 id INT AUTO_INCREMENT PRIMARY KEY,
 offering_id INT NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (offering_id) REFERENCES subject_offerings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS assignments (
 id INT AUTO_INCREMENT PRIMARY KEY,
 team_id INT NOT NULL,
 title VARCHAR(180) NOT NULL,
 description TEXT NULL,
 due_at DATETIME NULL,
 points INT NOT NULL DEFAULT 100,
 created_by INT NOT NULL,
 attachment_path VARCHAR(500) NULL,
 attachment_link VARCHAR(1000) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (team_id) REFERENCES course_teams(id) ON DELETE CASCADE,
 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_assignments_team_due (team_id,due_at)
);

CREATE TABLE IF NOT EXISTS assignment_submissions (
 id INT AUTO_INCREMENT PRIMARY KEY,
 assignment_id INT NOT NULL,
 student_id INT NOT NULL,
 submission_text TEXT NULL,
 file_path VARCHAR(255) NULL,
 submission_link VARCHAR(1000) NULL,
 status ENUM('Submitted','Late','Graded') NOT NULL DEFAULT 'Submitted',
 grade INT NULL,
 feedback TEXT NULL,
 submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY unique_assignment_student (assignment_id,student_id),
 FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
 FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_submission_student (student_id,submitted_at)
);

CREATE TABLE IF NOT EXISTS team_meetings (
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
);


CREATE TABLE IF NOT EXISTS team_materials (
 id INT AUTO_INCREMENT PRIMARY KEY,
 team_id INT NOT NULL,
 title VARCHAR(180) NOT NULL,
 description TEXT NULL,
 material_type ENUM('Material','Syllabus') NOT NULL DEFAULT 'Material',
 file_path VARCHAR(500) NULL,
 link_url VARCHAR(1000) NULL,
 created_by INT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (team_id) REFERENCES course_teams(id) ON DELETE CASCADE,
 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_team_materials_team_created (team_id,created_at)
);

INSERT IGNORE INTO course_teams(offering_id) SELECT id FROM subject_offerings;

-- ETW vNext academic features
CREATE TABLE IF NOT EXISTS attendance_records (
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
);
CREATE TABLE IF NOT EXISTS gradebook_entries (
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
);
CREATE TABLE IF NOT EXISTS important_dates (
 id INT AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL,
 description TEXT NULL,
 event_date DATE NOT NULL,
 event_type ENUM('Academic','Enrollment','Exam','Holiday','Other') NOT NULL DEFAULT 'Academic',
 created_by INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_important_dates (event_date)
);


-- Private messaging attachments
CREATE TABLE IF NOT EXISTS private_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  body TEXT NULL,
  attachment_type VARCHAR(20) NULL,
  attachment_name VARCHAR(255) NULL,
  attachment_path VARCHAR(500) NULL,
  attachment_url TEXT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_pm_sender_receiver (sender_id,receiver_id,created_at),
  INDEX idx_pm_receiver_read (receiver_id,is_read,created_at)
) ENGINE=InnoDB;
