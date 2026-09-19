/* ETW NU Baliwag OMS - InfinityFree Demo Accounts + Enrollment Conflict Data
   Run this while selected database is: if0_42956601_etw
   Does NOT create/drop the database or delete existing records.
*/

/* 1) Demo faculty accounts */
INSERT INTO users (name,email,password_hash,role,student_id,program)
SELECT 'Jose Ramirez','faculty.jose@nu-baliwag.edu.ph',SHA2('password',256),'faculty',NULL,'BS Computer Engineering'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='faculty.jose@nu-baliwag.edu.ph');

INSERT INTO users (name,email,password_hash,role,student_id,program)
SELECT 'Angela Dela Cruz','faculty.angela@nu-baliwag.edu.ph',SHA2('password',256),'faculty',NULL,'BS Civil Engineering'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='faculty.angela@nu-baliwag.edu.ph');

INSERT INTO users (name,email,password_hash,role,student_id,program)
SELECT 'Carlo Villanueva','faculty.carlo@nu-baliwag.edu.ph',SHA2('password',256),'faculty',NULL,'BS Business Administration'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='faculty.carlo@nu-baliwag.edu.ph');

/* 2) Demo student accounts */
INSERT INTO users (name,email,password_hash,role,student_id,program)
SELECT 'Ana Reyes','student.ana@nu-baliwag.edu.ph',SHA2('password',256),'student','20260001','BS Computer Engineering'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='student.ana@nu-baliwag.edu.ph');

INSERT INTO users (name,email,password_hash,role,student_id,program)
SELECT 'Miguel Santos','student.miguel@nu-baliwag.edu.ph',SHA2('password',256),'student','20260002','BS Civil Engineering'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='student.miguel@nu-baliwag.edu.ph');

INSERT INTO users (name,email,password_hash,role,student_id,program)
SELECT 'Sofia Cruz','student.sofia@nu-baliwag.edu.ph',SHA2('password',256),'student','20260003','BS Information Technology'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='student.sofia@nu-baliwag.edu.ph');

INSERT INTO users (name,email,password_hash,role,student_id,program)
SELECT 'Liam Garcia','student.liam@nu-baliwag.edu.ph',SHA2('password',256),'student','20260004','BS Business Administration'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='student.liam@nu-baliwag.edu.ph');

/* 3) Distribute subjects among faculty */
UPDATE subjects SET faculty_id=(SELECT id FROM users WHERE email='faculty@nu-baliwag.edu.ph' LIMIT 1)
WHERE code IN ('ITP 101','ITE 102','ENG 101');

UPDATE subjects SET faculty_id=(SELECT id FROM users WHERE email='faculty.jose@nu-baliwag.edu.ph' LIMIT 1)
WHERE code IN ('MATH 101','ITP 102','ITE 103');

UPDATE subjects SET faculty_id=(SELECT id FROM users WHERE email='faculty.angela@nu-baliwag.edu.ph' LIMIT 1)
WHERE code IN ('NSTP 1','PATH-FIT 1','ITP 103');

UPDATE subjects SET faculty_id=(SELECT id FROM users WHERE email='faculty.carlo@nu-baliwag.edu.ph' LIMIT 1)
WHERE code='GE 101';

/* 4) Add subject offerings with different teachers/schedules.
      Includes intentional overlaps for conflict testing. */

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Monday','08:00:00','11:00:00','IT Lab 1','IT-101'
FROM subjects s JOIN users u ON u.email='faculty@nu-baliwag.edu.ph'
WHERE s.code='ITP 101'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Monday' AND o.start_time='08:00:00' AND o.end_time='11:00:00' AND o.section='IT-101');

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Monday','09:00:00','12:00:00','Room 302','IT-102'
FROM subjects s JOIN users u ON u.email='faculty.jose@nu-baliwag.edu.ph'
WHERE s.code='ITP 102'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Monday' AND o.start_time='09:00:00' AND o.end_time='12:00:00' AND o.section='IT-102');

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Tuesday','13:00:00','16:00:00','Room 302','IT-103'
FROM subjects s JOIN users u ON u.email='faculty.jose@nu-baliwag.edu.ph'
WHERE s.code='ITP 102'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Tuesday' AND o.start_time='13:00:00' AND o.end_time='16:00:00' AND o.section='IT-103');

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Monday','09:00:00','12:00:00','Room 201','IT-101'
FROM subjects s JOIN users u ON u.email='faculty.jose@nu-baliwag.edu.ph'
WHERE s.code='MATH 101'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Monday' AND o.start_time='09:00:00' AND o.end_time='12:00:00' AND o.section='IT-101');

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Wednesday','13:00:00','16:00:00','Room 201','IT-102'
FROM subjects s JOIN users u ON u.email='faculty.jose@nu-baliwag.edu.ph'
WHERE s.code='MATH 101'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Wednesday' AND o.start_time='13:00:00' AND o.end_time='16:00:00' AND o.section='IT-102');

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Monday','08:00:00','11:00:00','Room 205','IT-101'
FROM subjects s JOIN users u ON u.email='faculty@nu-baliwag.edu.ph'
WHERE s.code='ENG 101'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Monday' AND o.start_time='08:00:00' AND o.end_time='11:00:00' AND o.section='IT-101');

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Friday','13:00:00','14:30:00','Room 205','IT-102'
FROM subjects s JOIN users u ON u.email='faculty@nu-baliwag.edu.ph'
WHERE s.code='ENG 101'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Friday' AND o.start_time='13:00:00' AND o.end_time='14:30:00' AND o.section='IT-102');

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Tuesday','14:00:00','17:00:00','IT Lab 3','IT-103'
FROM subjects s JOIN users u ON u.email='faculty.angela@nu-baliwag.edu.ph'
WHERE s.code='ITP 103'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Tuesday' AND o.start_time='14:00:00' AND o.end_time='17:00:00' AND o.section='IT-103');

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Thursday','13:00:00','16:00:00','IT Lab 3','IT-104'
FROM subjects s JOIN users u ON u.email='faculty.angela@nu-baliwag.edu.ph'
WHERE s.code='ITP 103'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Thursday' AND o.start_time='13:00:00' AND o.end_time='16:00:00' AND o.section='IT-104');

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Wednesday','08:00:00','11:00:00','Room 101','CE-101'
FROM subjects s JOIN users u ON u.email='faculty.angela@nu-baliwag.edu.ph'
WHERE s.code='NSTP 1'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Wednesday' AND o.start_time='08:00:00' AND o.end_time='11:00:00' AND o.section='CE-101');

INSERT INTO subject_offerings (subject_id,faculty_id,day_name,start_time,end_time,room,section)
SELECT s.id,u.id,'Thursday','10:00:00','13:00:00','Room 305','BA-101'
FROM subjects s JOIN users u ON u.email='faculty.carlo@nu-baliwag.edu.ph'
WHERE s.code='GE 101'
AND NOT EXISTS (SELECT 1 FROM subject_offerings o WHERE o.subject_id=s.id AND o.day_name='Thursday' AND o.start_time='10:00:00' AND o.end_time='13:00:00' AND o.section='BA-101');

/* 5) Give each dummy student an existing approved subject + matching schedule.
      term_id is included because the conflict checker uses the current term. */

INSERT INTO enrollments (student_id,subject_id,status,offering_id,term_id)
SELECT u.id,s.id,'approved',o.id,t.id
FROM users u JOIN subjects s ON s.code='ITP 101'
JOIN subject_offerings o ON o.subject_id=s.id AND o.day_name='Monday' AND o.start_time='08:00:00' AND o.end_time='11:00:00' AND o.section='IT-101'
JOIN academic_terms t ON t.is_current=1
WHERE u.email='student.ana@nu-baliwag.edu.ph'
AND NOT EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id=u.id AND e.subject_id=s.id);

INSERT INTO schedules (student_id,subject_id,day_name,start_time,end_time,room,term_id)
SELECT u.id,s.id,o.day_name,o.start_time,o.end_time,o.room,t.id
FROM users u JOIN subjects s ON s.code='ITP 101'
JOIN subject_offerings o ON o.subject_id=s.id AND o.day_name='Monday' AND o.start_time='08:00:00' AND o.end_time='11:00:00' AND o.section='IT-101'
JOIN academic_terms t ON t.is_current=1
WHERE u.email='student.ana@nu-baliwag.edu.ph'
AND NOT EXISTS (SELECT 1 FROM schedules sc WHERE sc.student_id=u.id AND sc.subject_id=s.id AND sc.term_id=t.id);

INSERT INTO enrollments (student_id,subject_id,status,offering_id,term_id)
SELECT u.id,s.id,'approved',o.id,t.id
FROM users u JOIN subjects s ON s.code='MATH 101'
JOIN subject_offerings o ON o.subject_id=s.id AND o.day_name='Monday' AND o.start_time='09:00:00' AND o.end_time='12:00:00' AND o.section='IT-101'
JOIN academic_terms t ON t.is_current=1
WHERE u.email='student.miguel@nu-baliwag.edu.ph'
AND NOT EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id=u.id AND e.subject_id=s.id);

INSERT INTO schedules (student_id,subject_id,day_name,start_time,end_time,room,term_id)
SELECT u.id,s.id,o.day_name,o.start_time,o.end_time,o.room,t.id
FROM users u JOIN subjects s ON s.code='MATH 101'
JOIN subject_offerings o ON o.subject_id=s.id AND o.day_name='Monday' AND o.start_time='09:00:00' AND o.end_time='12:00:00' AND o.section='IT-101'
JOIN academic_terms t ON t.is_current=1
WHERE u.email='student.miguel@nu-baliwag.edu.ph'
AND NOT EXISTS (SELECT 1 FROM schedules sc WHERE sc.student_id=u.id AND sc.subject_id=s.id AND sc.term_id=t.id);

INSERT INTO enrollments (student_id,subject_id,status,offering_id,term_id)
SELECT u.id,s.id,'approved',o.id,t.id
FROM users u JOIN subjects s ON s.code='ITP 102'
JOIN subject_offerings o ON o.subject_id=s.id AND o.day_name='Tuesday' AND o.start_time='13:00:00' AND o.end_time='16:00:00' AND o.section='IT-103'
JOIN academic_terms t ON t.is_current=1
WHERE u.email='student.sofia@nu-baliwag.edu.ph'
AND NOT EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id=u.id AND e.subject_id=s.id);

INSERT INTO schedules (student_id,subject_id,day_name,start_time,end_time,room,term_id)
SELECT u.id,s.id,o.day_name,o.start_time,o.end_time,o.room,t.id
FROM users u JOIN subjects s ON s.code='ITP 102'
JOIN subject_offerings o ON o.subject_id=s.id AND o.day_name='Tuesday' AND o.start_time='13:00:00' AND o.end_time='16:00:00' AND o.section='IT-103'
JOIN academic_terms t ON t.is_current=1
WHERE u.email='student.sofia@nu-baliwag.edu.ph'
AND NOT EXISTS (SELECT 1 FROM schedules sc WHERE sc.student_id=u.id AND sc.subject_id=s.id AND sc.term_id=t.id);

INSERT INTO enrollments (student_id,subject_id,status,offering_id,term_id)
SELECT u.id,s.id,'approved',o.id,t.id
FROM users u JOIN subjects s ON s.code='ENG 101'
JOIN subject_offerings o ON o.subject_id=s.id AND o.day_name='Friday' AND o.start_time='13:00:00' AND o.end_time='14:30:00' AND o.section='IT-102'
JOIN academic_terms t ON t.is_current=1
WHERE u.email='student.liam@nu-baliwag.edu.ph'
AND NOT EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id=u.id AND e.subject_id=s.id);

INSERT INTO schedules (student_id,subject_id,day_name,start_time,end_time,room,term_id)
SELECT u.id,s.id,o.day_name,o.start_time,o.end_time,o.room,t.id
FROM users u JOIN subjects s ON s.code='ENG 101'
JOIN subject_offerings o ON o.subject_id=s.id AND o.day_name='Friday' AND o.start_time='13:00:00' AND o.end_time='14:30:00' AND o.section='IT-102'
JOIN academic_terms t ON t.is_current=1
WHERE u.email='student.liam@nu-baliwag.edu.ph'
AND NOT EXISTS (SELECT 1 FROM schedules sc WHERE sc.student_id=u.id AND sc.subject_id=s.id AND sc.term_id=t.id);

/* Done. Password for every demo account: password */
SELECT 'DEMO DATA READY' AS result;
SELECT name,email,role,student_id FROM users WHERE email IN (
 'faculty.jose@nu-baliwag.edu.ph','faculty.angela@nu-baliwag.edu.ph','faculty.carlo@nu-baliwag.edu.ph',
 'student.ana@nu-baliwag.edu.ph','student.miguel@nu-baliwag.edu.ph','student.sofia@nu-baliwag.edu.ph','student.liam@nu-baliwag.edu.ph'
) ORDER BY role,email;
