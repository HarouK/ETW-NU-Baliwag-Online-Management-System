# ETW NU Baliwag OMS — Stable Source Build

This build includes the current Student, Faculty, and Administrator features plus the final login/mobile/header stability pass.

## Local XAMPP
The included `config.php` automatically uses:
- host: `127.0.0.1`
- database: `etw_nu_baliwag`
- user: `root`
- password: empty

## InfinityFree
`config.php` automatically detects the hosted domain and switches to:
- host: `sql211.infinityfree.com`
- database: `if0_42956601_etw`
- user: `if0_42956601`

Before uploading to InfinityFree, set the hosted DB password in `config.php` at:
`PASTE_YOUR_INFINITYFREE_DB_PASSWORD_HERE`

Do not replace the working hosted database with the local XAMPP settings.

## Important
- `config.php` is the only environment-specific file.
- Do not import `database.sql` into InfinityFree without removing its local `CREATE DATABASE` / `USE` statements first.
- This source build keeps the existing role-specific navigation, gradebook routing, faculty account provisioning, messaging, notifications, class teams, assignments, attendance, important dates, and mobile layout.
