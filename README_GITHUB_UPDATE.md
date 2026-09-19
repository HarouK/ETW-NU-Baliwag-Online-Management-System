# ETW – NU Baliwag Online Management System

Final stable PHP + MySQL prototype for the ETW Software Design project.

## Included
- Student, Faculty, and Administrator portals
- Authentication and role-based access
- Student enrollment with schedule conflict detection
- Faculty classes, schedules, attendance, gradebook, announcements, and teams
- Student class teams, materials, assignments, meetings, and submissions
- Private messaging with file/image attachments
- Tickets, administration inbox, notifications, announcements
- Academic progress, grades, attendance, documents, and important dates
- Admin management pages and audit-related views
- Responsive UI and NU Baliwag visual assets
- `database.sql` for the base database
- `DEMO_CONFLICT_DATA.sql` for optional conflict-testing/demo accounts

## Important security note
Do not commit real database passwords, API keys, or other secrets. The supplied `config.php` uses an environment variable for the hosted database password.

## Local XAMPP
1. Put the project files directly inside `htdocs/ETW_NU_Baliwag_OMS_PHP_HUMAN_UI/`.
2. Create/import the `etw_nu_baliwag` database using `database.sql`.
3. Local `config.php` uses MySQL on `127.0.0.1` with database `etw_nu_baliwag`, user `root`, and an empty password unless your local MySQL setup differs.

## InfinityFree
Use the hosted database credentials in `config.php` and provide the hosted DB password through `ETW_HOSTED_DB_PASSWORD` according to the hosting environment. Do not publish the real password in GitHub.

## Demo conflict data
`DEMO_CONFLICT_DATA.sql` is optional. Import it into the existing hosted/local database without dropping the database if you want the extra demo users, faculty, offerings, and intentional schedule conflicts used for testing.

## Updating GitHub later
If this project is already connected to GitHub Desktop:
1. Replace/update the local project files with your newer version.
2. Open GitHub Desktop and select this repository.
3. Review the changed files.
4. Enter a commit message such as `Update enrollment and messaging`.
5. Click **Commit to main** (or your current branch).
6. Click **Push origin**.

If the GitHub repository already has commits that are not on your computer, fetch/pull those changes before pushing so the histories stay synchronized.
