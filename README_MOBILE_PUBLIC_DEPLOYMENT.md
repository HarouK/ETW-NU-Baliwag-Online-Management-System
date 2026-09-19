# ETW – NU Baliwag OMS: Mobile + Public Deployment

## What was added in this update
- Student: Academic Progress, Grades, Attendance, Document Center, Important Dates.
- Faculty: Attendance recording and Gradebook with per-student assessment scores/feedback.
- Admin: Audit Log and Important Dates management.
- Unified Important Dates page for all authenticated roles.
- Notification dropdown retained and optimized for mobile.
- Responsive mobile layout across headers, forms, cards, tables, schedules, profile menu and notification dropdown.
- Existing Home button, clean bell, alphabetical profile navigation, ticket workflow, PDF C.O.R. and NU logo remain intact.

## Local XAMPP
1. Replace the project files under `htdocs/ETW_NU_Baliwag_OMS_PHP_HUMAN_UI`.
2. Start Apache and MySQL.
3. Open the existing local URL/port.
4. No database re-import is required because `config.php` automatically creates the new feature tables.

## Public deployment
This is a PHP + MySQL application, so it must be deployed to a host that supports server-side PHP and MySQL/MariaDB. Static-only hosts are not sufficient.

Suggested free prototype hosting: InfinityFree (PHP + MySQL + free SSL and a free subdomain). Create the hosting account, create a MySQL database, upload the project files to the web root, import `database.sql` if the host requires a fresh database, and update `config.php` with the host's database hostname, database name, username, and password.

For a professional custom address such as `etw-nubaliwag.com`, register a domain and point its DNS to the PHP/MySQL host. Keep database credentials out of public repositories and change the demo passwords before public access.
