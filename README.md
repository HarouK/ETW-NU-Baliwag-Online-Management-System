# ETW – NU Baliwag Online Management System

PHP + MySQL prototype for XAMPP.

## Student features
- Dashboard with student information, quick actions, reminders, notifications, units, and active tickets.
- Academic Year/Semester selector for current and previous terms.
- Enrollment with subject details, teacher/schedule selection, conflict checking, enrollment status, total units, and projected tuition/assessment estimate.
- Weekly schedule calendar with teacher, room, section, and units.
- Academic records with previous-term sample grades and downloadable records.
- Printable/downloadable enrollment confirmation.
- Ticket search, status filters, newest/oldest sorting, and status timeline.
- Announcement search/filter and full announcement details.
- Notifications and transaction history.
- Profile editing and account security.
- Responsive/mobile-friendly layout.
- Contact Administration appears inside student workflows instead of the sidebar.

## Demo accounts
- Student: student@nu-baliwag.edu.ph / password
- Faculty: faculty@nu-baliwag.edu.ph / password
- Admin: admin@nu-baliwag.edu.ph / password

## XAMPP
Put the folder in `C:\xampp\htdocs`, start Apache and MySQL, then open:
`http://localhost/ETW_NU_Baliwag_OMS_PHP_HUMAN_UI/`

For an existing database, no re-import is needed. Feature tables/columns are upgraded automatically when the site is opened.

## Professor Requested Updates — September 19, 2026
- Messaging: pressing Enter sends the message; Shift+Enter inserts a new line.
- Added a Home/Dashboard button at the top-left for Student, Faculty/Professor, and Administrator pages.
- Assignment due date/time now accepts only the current or a future date/time, with both browser-side and server-side validation.
- Admin ticket management now uses four compact status actions: Rejected, Open, Ongoing, and Resolved. The large Update control was removed.
- Enrollment Confirmation / C.O.R. download is now a real PDF document and includes an NU-style shield/wordmark mark in the document header.
- Academic Records download is also provided as a PDF for consistency.
