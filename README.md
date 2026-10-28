# Lectura — University Course Material Hub

[![Live Demo](https://img.shields.io/badge/demo-lectura.alwaysdata.net-success?style=for-the-badge&logo=googlechrome&logoColor=white)](https://lectura.alwaysdata.net)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.1-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-%3E%3D%205.7-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

> **🌐 Live Demo:** [https://lectura.alwaysdata.net](https://lectura.alwaysdata.net)

**Lectura** is a lightweight, secure, invite-only course-material hub tailored for university cohorts, academic departments, and study groups. It provides a centralized space to share lectures, assignments, summaries, past exams, and announcements with full support for bilingual Arabic (RTL) and English (LTR) interfaces, light and dark themes, and real-time live content updates without page reloads.

Built with **PHP 8.1+**, **PDO/MySQL**, **Bootstrap 5.3**, and vanilla JavaScript, Lectura requires **no frontend build steps** or node dependencies to run. All uploaded files reside securely outside the public web root and are streamed through access-controlled endpoints.

---

## Table of Contents

- [Live Demo](#-live-demo)
- [Key Features](#key-features)
- [Role-Based Access Control (RBAC)](#role-based-access-control-rbac)
- [Architecture & Tech Stack](#architecture--tech-stack)
- [Project Directory Structure](#project-directory-structure)
- [Getting Started (Local Development)](#getting-started-local-development)
  - [Prerequisites](#prerequisites)
  - [Installation Steps](#installation-steps)
  - [Initial Admin Account](#initial-admin-account)
- [Production Deployment (alwaysdata)](#production-deployment-alwaysdata)
- [Configuration Reference](#configuration-reference)
- [Security Model](#security-model)
- [Troubleshooting & FAQ](#troubleshooting--faq)
- [License](#license)

---

## 🌐 Live Demo

Visit the active deployment here:  
👉 **[https://lectura.alwaysdata.net](https://lectura.alwaysdata.net)**

---

## Key Features

- **🔐 Invite-Only Onboarding & Authentication:** Single-use, cryptographically secure invite tokens valid for 7 days. Automatic brute-force lockout locks accounts for 10 minutes after 5 consecutive failed attempts per email + IP.
- **👥 Multi-Role Authorization:** Four distinct roles (Admin, Lecturer, Assistant, Student) with strict server-side validation.
- **📁 Secure File Management:** Upload support for PDF, Microsoft Office (Word, PowerPoint, Excel), images (JPEG, PNG), and ZIP archives up to 10 MB. In-browser preview for PDFs and images; forced download attachment for documents and archives.
- **⚡ Live Content Refresh:** Automated periodic background polling (`public/api/live.php`) updates open views (Home, Course, Item, Archive) on the fly without refreshing the page or clearing the user's active search filters.
- **📢 Pinned Announcements & Deadlines:** Prominent broadcast banners for critical notices with countdown timers on pending assignment deadlines.
- **💬 WhatsApp Group Sharing:** Built-in cohort sharing workflow for authorized staff. One-click copy of formatted course/announcement updates and direct redirection to the cohort's official WhatsApp group.
- **📧 Notification Delivery:** Asynchronous, best-effort email dispatch using PHPMailer / SMTP for new materials and announcements to opted-in cohort members.
- **🎨 Modern Responsive UI & Themes:** Fully responsive from 320px mobile devices to 4K displays. Instant toggling between Light, Dark, and System Auto modes.
- **🌍 Bilingual RTL / LTR Support:** Native Arabic interface (RTL) using self-hosted **IBM Plex Sans Arabic** alongside a full English interface (LTR) using **Inter**.

---

## Role-Based Access Control (RBAC)

Lectura enforces strict, server-side access controls on every action:

| Capability | Admin | Lecturer | Assistant | Student |
|---|:---:|:---:|:---:|:---:|
| **Manage Users & Role Assignment** | ✅ | ❌ | ❌ | ❌ |
| **Generate & Revoke Invitations** | ✅ | ❌ | ❌ | ❌ |
| **Manage Academic Terms & Courses** | ✅ | ❌ | ❌ | ❌ |
| **Configure WhatsApp Group Sharing** | ✅ | ❌ | ❌ | ❌ |
| **Upload Materials to Any Course** | ✅ | ❌ | ✅ | ❌ |
| **Upload Materials to Assigned Courses** | ✅ | ✅ | ✅ | ❌ |
| **Create & Manage All Announcements** | ✅ | ❌ | ❌ | ❌ |
| **Create & Manage Own Announcements** | ✅ | ✅ | ❌ | ❌ |
| **Share Updates to WhatsApp Group** | ✅ | ✅ | ✅ | ❌ |
| **View & Download Materials & Notices** | ✅ | ✅ | ✅ | ✅ |

*Note: All content views require authentication. Guest access is limited strictly to `login.php`, `register.php`, and first-time `setup.php`.*

---

## Architecture & Tech Stack

- **Backend:** PHP 8.1+ (strict typing, PDO prepared statements).
- **Database:** MySQL 5.7+ or MariaDB 10.3+ (`utf8mb4_unicode_ci` charset, UTC timestamps).
- **Frontend:** HTML5, Vanilla JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons.
- **Typography:** Self-hosted *IBM Plex Sans Arabic* (SIL Open Font License) and *Inter* (OFL).
- **Mail Services:** PHPMailer 6.9+ with SMTP integration or native PHP `mail()` fallback.
- **Security:** CSRF token verification on state-changing requests, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, and strict Content Security Policy (CSP).

---

## Project Directory Structure

```text
Lectura/
├── config/
│   ├── .htaccess              # Prohibits direct HTTP access
│   ├── config.example.php     # Configuration template
│   └── config.php             # Local / Production config (ignored by git)
├── database/
│   ├── .htaccess
│   ├── schema.sql             # Full database schema definition
│   ├── seed.sql               # Initial administrator seed
│   └── migration_lecturer_group.sql # Additive migration for lecturer roles & sharing
├── lang/
│   ├── .htaccess
│   ├── ar.php                 # Arabic locale dictionary
│   └── en.php                 # English locale dictionary
├── public/                    # Web Document Root
│   ├── .htaccess              # URL routing, security headers, protected paths
│   ├── index.php              # Home dashboard & recent material feed
│   ├── login.php              # Authentication gateway
│   ├── logout.php             # Session teardown
│   ├── register.php           # Token-based onboarding
│   ├── setup.php              # Initial administrator setup wizard (self-disabling)
│   ├── download.php           # Secure file streaming handler
│   ├── course.php             # Course catalog and material listings
│   ├── item.php               # Detailed file view & inline previewer
│   ├── archive.php            # Past academic terms archive
│   ├── settings.php           # User profile, theme, language & password settings
│   ├── error.php              # Styled HTTP error responses (403, 404, 500)
│   ├── admin/                 # Management controllers (Admin & Staff)
│   │   ├── index.php          # Admin metrics overview
│   │   ├── terms.php          # Term management
│   │   ├── courses.php        # Course management & lecturer assignments
│   │   ├── upload.php         # Material upload form
│   │   ├── edit_item.php      # Material editor
│   │   ├── announcements.php  # Announcement management
│   │   ├── users.php          # User directory & role editor
│   │   ├── invites.php        # Invite generator
│   │   └── sharing.php        # Cohort WhatsApp group link configuration
│   ├── api/
│   │   └── live.php           # Lightweight JSON heartbeat for live updates
│   └── assets/                # Public assets
│       ├── css/lectura.css    # Custom CSS & theme tokens
│       ├── js/                # Application scripts (app, countdown, live, share)
│       ├── fonts/             # Self-hosted WOFF2 font bundles
│       └── img/               # Brand assets & favicons
├── src/                       # Core Application Logic
│   ├── .htaccess              # Block direct web access
│   ├── bootstrap.php          # Request initialization, sessions & security headers
│   ├── db.php                 # PDO singleton & database helpers
│   ├── auth.php               # User authentication, hashing & permission checks
│   ├── csrf.php               # Anti-CSRF token lifecycle
│   ├── helpers.php            # HTML escaping, date formatters & utilities
│   ├── i18n.php               # Localization & text-direction negotiation
│   ├── upload.php             # MIME verification, storage & upload management
│   ├── notify.php             # Event notifications & notification dispatch
│   ├── mailer.php             # SMTP delivery & email notification templates
│   ├── live.php               # Content versioning logic
│   ├── settings.php           # Database-backed configuration store
│   └── layout/                # View layouts, navigation, and reusable partials
├── storage/                   # Private Storage (Outside Public Root)
│   ├── .htaccess              # Direct access denied
│   ├── uploads/               # Obfuscated physical uploaded files
│   └── logs/                  # System error & audit logs
├── composer.json              # Dependency manifests
├── composer.lock
└── README.md
```

> ⚠️ **Important:** The web server document root must point to `public/`, **not** the project root. This ensures `config/`, `src/`, `storage/`, and dependencies remain inaccessible via direct URL.

---

## Getting Started (Local Development)

### Prerequisites

- **PHP 8.1 or higher** (extensions: `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `json`, `session`, optional: `intl`)
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Composer 2.x**
- **Apache** (with `mod_rewrite` and `mod_headers`) or **Nginx**

### Installation Steps

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/alaamohmeed/Lectura.git
   cd Lectura
   ```

2. **Install Composer Dependencies:**
   ```bash
   composer install
   ```

3. **Configure Environment:**
   Copy the sample configuration file:
   ```bash
   cp config/config.example.php config/config.php
   ```
   Open `config/config.php` and adjust your local settings:
   ```php
   'app' => [
       'base_url' => 'http://localhost/Lectura/public',
       'name' => 'Lectura',
       'default_lang' => 'ar',
       'timezone' => 'UTC',
       'debug' => true,
       'setup_secret' => 'local-development-secret-123',
   ],
   'db' => [
       'host' => 'localhost',
       'name' => 'lectura',
       'user' => 'root',
       'pass' => '',
       'charset' => 'utf8mb4',
   ],
   'mail' => [
       'use_smtp' => false,
   ],
   'session' => [
       'cookie_secure' => false, // false for HTTP local testing
   ],
   ```

4. **Initialize Database:**
   Create the database in MySQL:
   ```sql
   CREATE DATABASE lectura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   Import the schema:
   ```bash
   # On Windows (cmd.exe):
   cmd /c "mysql -u root -p --default-character-set=utf8mb4 lectura < database/schema.sql"

   # On Linux / macOS:
   mysql -u root -p --default-character-set=utf8mb4 lectura < database/schema.sql
   ```

5. **Set Directory Permissions:**
   Ensure `storage/uploads` and `storage/logs` are writable:
   ```bash
   chmod -R 775 storage/uploads storage/logs
   ```

6. **Serve the Application:**
   - **XAMPP / WampServer:** Place the folder inside `htdocs` and visit `http://localhost/Lectura/public/`.
   - **PHP Built-in Server (quick test):**
     ```bash
     php -S localhost:8000 -t public
     ```
     *(Then set `'base_url' => 'http://localhost:8000'` in `config/config.php`)*.

---

### Initial Admin Account

You can initialize your administrator account in one of two ways:

#### Option A: Import Seed File
```bash
cmd /c "mysql -u root -p --default-character-set=utf8mb4 lectura < database/seed.sql"
```
- **Email:** `alaa.m.swidi@gmail.com`
- **Temporary Password:** `Alaa@2026!`  
*(Make sure to change the password immediately under **Settings → Change Password**)*.

#### Option B: Web Setup Wizard
1. Navigate to `http://localhost/Lectura/public/setup.php`.
2. Enter the `setup_secret` specified in your `config/config.php`.
3. Provide your administrator name, email, and strong password.
4. Once completed, `setup.php` automatically disables itself.

---

## Production Deployment (alwaysdata)

Lectura is optimized for production hosting on platforms like **alwaysdata**:

1. **Web Site Configuration:**
   - In alwaysdata Admin, go to **Web → Sites**.
   - Set **Type** to `PHP`.
   - Set **Document Root** to: `~/www/Lectura/public` *(or your custom path)*.
   - Select **PHP version 8.1** or newer.
   - Force **HTTPS / SSL** (Let's Encrypt certificates are provided free).
   - In PHP settings, set `upload_max_filesize = 12M` and `post_max_size = 15M` to comfortably accommodate the 10 MB upload allowance.

2. **Database Provisioning:**
   - Go to **Databases → MySQL → Add a database**.
   - Create your database (e.g., `lectura_db`).
   - Import `database/schema.sql` (and `database/seed.sql` if desired) via phpMyAdmin or SSH CLI:
     ```bash
     mysql -h mysql-YOURACCOUNT.alwaysdata.net -u USER -p DATABASE < database/schema.sql
     ```

3. **Deploy Code & Configuration:**
   - Clone or upload the repository to your home folder.
   - Run `composer install --no-dev --optimize-autoloader`.
   - Copy `config/config.example.php` to `config/config.php`.
   - Set `'debug' => false` and `'cookie_secure' => true`.
   - Set `'base_url' => 'https://lectura.alwaysdata.net'` (without trailing slash).
   - Configure your alwaysdata SMTP mailbox credentials for reliable email delivery.

4. **Verify Storage Permissions:**
   ```bash
   chmod 755 storage storage/uploads storage/logs
   ```

---

## Configuration Reference

Key directives available in `config/config.php`:

| Key | Default | Description |
|---|---|---|
| `app.base_url` | `https://lectura.alwaysdata.net` | Canonical URL without trailing slash. Used for invite links and emails. |
| `app.name` | `Lectura` | Application name displayed across titles, navigation, and notification footers. |
| `app.default_lang` | `ar` | Default language for unauthenticated guests (`ar` or `en`). |
| `app.timezone` | `UTC` | Target timezone for user-facing date formats and deadline inputs. |
| `app.debug` | `false` | Enable verbose error traces (leave `false` in production). |
| `app.setup_secret` | *(random string)* | One-time secret key required to access `public/setup.php`. |
| `db.host` / `db.name` | — | MySQL / MariaDB host and database name. |
| `db.user` / `db.pass` | — | Database connection credentials. |
| `db.charset` | `utf8mb4` | Connection encoding. |
| `mail.use_smtp` | `false` | Enable SMTP delivery via PHPMailer; falls back to `mail()` when false. |
| `mail.host` / `mail.port` | `587` | SMTP server connection host and port. |
| `upload.path` | `../storage/uploads` | Absolute or relative path to private file storage directory. |
| `upload.max_size` | `10485760` (10 MB) | Maximum permitted file size in bytes. |
| `upload.allowed_types` | `[pdf, doc, docx, ...]` | Whitelist of verified MIME types. |
| `invite.token_length` | `32` | Cryptographic entropy for tokens (64 hex characters). |
| `invite.expiry_days` | `7` | Duration before an unredeemed invite link expires. |
| `auth.max_failed_attempts`| `5` | Maximum failed login attempts allowed before lockout. |
| `auth.lockout_minutes` | `10` | Temporary lockout duration in minutes. |
| `session.cookie_secure` | `true` | Restricts cookies to HTTPS connections (set `false` on HTTP localhost). |
| `session.cookie_httponly`| `true` | Prevents client-side scripts from reading session cookies. |
| `session.cookie_samesite`| `Lax` | Protects against Cross-Site Request Forgery (CSRF). |

---

## Security Model

Lectura implements defense-in-depth principles across all components:

- **Strict Access Barriers:** Uploaded files are assigned random hashed names and stored outside the public directory. Downloads are routed through `download.php` which validates active session state and role permissions prior to streaming bytes.
- **SQL Injection Prevention:** Every query uses PDO prepared statements with parameterized inputs. Emulated prepares are disabled.
- **Cross-Site Scripting (XSS):** All dynamic browser outputs pass through HTML escaping functions (`e()` and `ea()`).
- **Cross-Site Request Forgery (CSRF):** Synchronizer tokens are validated on all state-changing HTTP `POST` requests.
- **Strict Content Security Policy (CSP):** The application restricts resource loading exclusively to trusted CDNs (Bootstrap, Google Fonts) and local origins.
- **Atomic Concurrency:** Invitation redemption executes inside database transactions using row-level locking (`SELECT ... FOR UPDATE`) to prevent race conditions or duplicate account creation.

---

## Troubleshooting & FAQ

#### Q: Getting HTTP 500 error on new installation?
> Ensure required PHP extensions (`pdo_mysql`, `fileinfo`, `mbstring`) are enabled in your `php.ini`. Check `storage/logs/php_errors.log` or your web server error logs for stack traces.

#### Q: Login redirects back to the login page without logging in?
> If running on local HTTP (`http://localhost`), make sure `'cookie_secure' => false` in `config/config.php`. When running over HTTPS, `'cookie_secure'` must be `true`. Also verify `app.base_url` matches your exact browser address.

#### Q: File upload fails with "File size exceeds allowed limit"?
> In addition to the application's 10 MB limit, PHP imposes `upload_max_filesize` and `post_max_size` limits in `php.ini`. Increase both values (e.g., `upload_max_filesize = 12M` and `post_max_size = 15M`).

#### Q: Invitations show as expired or invalid immediately?
> Ensure your MySQL server system clock and PHP timezone are synchronized. Tokens expire exactly 7 days after the UTC timestamp recorded at generation.

---

## License

This project is open-source software licensed under the [MIT License](LICENSE).
