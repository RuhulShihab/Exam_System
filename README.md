# পরীক্ষা কেন্দ্র / Exam Center (PHP)

একটি সম্পূর্ণ PHP-ভিত্তিক অনলাইন পরীক্ষা সিস্টেম — বাংলা ও ইংরেজি দুই ভাষায়, শিক্ষক লগইন করে প্রশ্ন তৈরি করবেন, শিক্ষার্থী কোড দিয়ে পরীক্ষা দেবে, এবং একজন অ্যাডমিন পুরো সিস্টেম নিয়ন্ত্রণ করবেন।

A complete PHP-based online exam system with a Bangla/English toggle, teacher accounts, student exams, and a full admin panel — now with 2FA, password reset, question banks, exam scheduling, audit logs, and more.

## চাহিদা / Requirements

- PHP 7.4+ with `pdo_sqlite` (bundled by default in almost every PHP install).
- No MySQL setup needed — a self-contained SQLite database is created automatically in `data/exam.sqlite`.
- (Optional) A working `mail()`/sendmail setup on the server if you want password-reset emails to actually be delivered — see "Email" below.

## চালানোর উপায় / How to run

```bash
cd exam_system
php -S localhost:8000
```

Then open `http://localhost:8000`. For Apache/Nginx, upload the whole `exam_system` folder and point your webroot at it; make sure `data/` is writable (`chmod 775 data`).

## ⚠️ প্রথমবার সেটআপ (আবশ্যক) / First-time setup (required)

1. যান: `http://localhost:8000/admin/setup.php`
2. নাম, ইমেইল ও পাসওয়ার্ড দিয়ে প্রথম admin অ্যাকাউন্ট তৈরি করুন (এই পেজ একবারই কাজ করে)।
3. এরপর `/admin/login.php` দিয়ে লগইন করুন।

Visit `/admin/setup.php` right after deploying to create the first admin account (it disables itself afterward). Then log in at `/admin/login.php`.

## গঠন / Project structure

```
exam_system/
├── index.php, toggle_lang.php
├── config/
│   ├── db.php            # SQLite connection, full schema, safe migrations
│   └── settings.php       # timezone, error-log setup, DB-backed site settings
├── includes/
│   ├── session_boot.php    # hardened session cookies
│   ├── auth.php             # login, CSRF, lockout, remember-me, password reset
│   ├── mailer.php           # send_app_mail() — PHP mail() with on-screen fallback
│   ├── totp.php             # dependency-free TOTP (2FA) implementation
│   ├── audit.php            # log_action() → audit_log table
│   ├── rate_limit.php       # per-IP throttle for login/register/forgot-password
│   ├── lang.php, header.php, footer.php
├── assets/
│   ├── style.css, exam.js
│   ├── exam_create.js, exam_bank_insert.js, exam_edit_prefill.js
├── teacher/
│   ├── register.php / login.php / logout.php
│   ├── forgot_password.php / reset_password.php
│   ├── dashboard.php                     # this teacher's own exams
│   ├── create_exam.php / save_exam.php
│   ├── edit_exam.php / update_exam.php   # edit an existing exam + its questions
│   ├── exam_action.php                   # delete own exam
│   ├── bank.php / bank_action.php        # personal question bank
│   ├── created.php / results.php
├── student/
│   ├── join.php / start.php / exam.php / submit.php / result.php
├── admin/
│   ├── setup.php / login.php / logout.php / verify_2fa.php
│   ├── forgot_password.php / reset_password.php
│   ├── dashboard.php                          # system-wide stats
│   ├── teachers.php + teacher_action.php      # approve/block/unblock/delete/reset password
│   ├── create_teacher.php
│   ├── exams.php + exam_action.php            # any teacher's exams
│   ├── submissions.php + submission_action.php
│   ├── security.php                           # enable/disable admin 2FA
│   ├── audit_log.php                          # recent activity log
│   ├── settings.php                           # site name
│   ├── backup.php                             # download a DB backup
│   └── _nav.php
└── data/                # SQLite DB + logs/ (auto-created; .htaccess blocks web access)
```

## ভূমিকা / Roles

- **Admin** — created once via `/admin/setup.php`. Full control: approve/block/delete teachers, create teachers directly, view/delete any exam or submission, enable 2FA, view the audit log, edit site settings, download backups.
- **Teacher** — signs up at `/teacher/register.php`, stays "pending" until an admin approves. Can create/edit/delete their own exams, schedule them, and build a reusable question bank.
- **Student** — no account needed; joins with name + exam code, same as before.

## নতুন ফিচার (Security & Account)

- **Forgot Password** — teacher and admin can request a reset link (`forgot_password.php`). If the server has no outbound mail configured, the link is shown on-screen instead (clearly marked as a dev fallback) so the flow still works everywhere.
- **Admin 2FA (TOTP)** — `admin/security.php` lets an admin turn on standard 6-digit authenticator-app codes (Google Authenticator, Authy, etc.), implemented in pure PHP — no external library or QR service. Enter the shown "manual key" into the app to link it.
- **Remember Me** — an optional 30-day persistent login using a hashed, single-use selector/validator cookie (not a raw session ID).
- **Audit Log** — `admin/audit_log.php` records logins, teacher approvals/blocks/deletes, password resets, exam create/edit/delete, 2FA changes, etc.
- **Rate limiting** — per-IP throttling on login, registration, and forgot-password endpoints, on top of the existing per-account lockout (5 wrong passwords → 15-minute lock).

## নতুন ফিচার (Teacher)

- **Edit Exam** — change title/duration/schedule and the full question set after creation (`edit_exam.php`).
- **Delete Exam** — teachers can remove their own exams (and results) directly from the dashboard.
- **Question Bank** — save reusable questions once (`teacher/bank.php`) and insert any of them into a new or existing exam with one click.
- **Exam Scheduling** — optional "available from" / "available until" times; students can't join before or after the window.

## নতুন ফিচার (Infrastructure)

- **Central settings** (`config/settings.php`) — timezone, and a small DB-backed `settings` table the admin can edit (site name) without touching code.
- **Safer error handling** — PHP errors are no longer shown to visitors; they're logged to `data/logs/app.log` instead (`display_errors` off, `log_errors` on).
- **Database backup** — `admin/backup.php` streams a timestamped copy of the SQLite file for download.
- **`.htaccess`** in `data/` blocks direct web access to the database file.

## নিরাপত্তা সারাংশ / Security summary

- Passwords hashed with bcrypt (`password_hash`/`password_verify`); reset tokens and remember-me validators are stored as SHA-256 hashes, never in plaintext.
- CSRF tokens on every state-changing form.
- Session cookies are `httponly` + `samesite=Lax`; session ID is regenerated on every login.
- All DB queries use prepared statements. Scores are always computed server-side.

## ইমেইল সম্পর্কে / About email

Password-reset emails are sent with PHP's built-in `mail()` function, which only works if your server has `sendmail`/Postfix configured (common on real hosting, often *not* configured on `php -S` local dev or bare containers). If sending fails, the app automatically falls back to showing the reset link directly on the confirmation page — so the feature is fully usable in any environment; just note that in production with real mail configured, that on-screen fallback naturally won't appear (only the real email will).

## সীমাবদ্ধতা / Remaining limitations (still worth knowing)

- No self-service email *verification* at signup (admin approval covers this instead).
- Tab-switch detection is still browser-event based (`visibilitychange`/`blur`), not full webcam/screen proctoring.
- Backups are on-demand (a button), not an automated scheduled job — wire up a cron hitting `admin/backup.php` (with its own auth) or a server-level cron `cp`/`sqlite3 .backup` if you want that.
- Before real production use: serve over HTTPS (cookies become `secure` automatically), and keep `data/` outside the public webroot if your host allows it, as extra defense beyond the included `.htaccess`.

## ✨ Modern UI Upgrade
This package includes a refreshed responsive UI, modern landing page, admin analytics dashboard, teacher performance cards, improved student exam interface with question navigation, and a visual result/performance summary. Existing SQLite data/schema and security flows are preserved.


## Modern Upgrade v3
Added student performance dashboard, leaderboard, printable certificates, teacher analytics,
admin announcements, random question order, answer progress indicator, responsive feature cards,
and improved navigation. Existing SQLite data is preserved through lightweight migrations.
