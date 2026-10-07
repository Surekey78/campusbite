# CampusBite

A PHP/MySQL campus food ordering and management app, designed to run directly from XAMPP. It uses a root-level `index.php` front controller, MySQL/MariaDB as the source of truth, and plain PHP, HTML, CSS, vanilla JavaScript, and `mysqli`—no framework, package manager, Node build, or separate `public` directory.

## Quick start (XAMPP on Windows)

1. Install/start XAMPP with **Apache** and **MySQL**. Use PHP 8.0 or later with `mysqli`/mysqlnd, `mbstring`, and `fileinfo` enabled (these are normally included/enabled in XAMPP).
2. Copy this project folder to `C:\xampp\htdocs\campusbite`. Confirm `C:\xampp\htdocs\campusbite\index.php` and `schema.sql` exist.
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Open `http://localhost/phpmyadmin/`, choose **Import**, select `C:\xampp\htdocs\campusbite\schema.sql`, and run the import. The script creates and selects the `campusbite` database, its tables, demo records, and database-stored sample food images.
5. Open `http://localhost/campusbite/`.

The default local XAMPP database settings are `localhost`, database `campusbite`, user `root`, blank password, port `3306`. If yours differ, set `CAMPUSBITE_DB_HOST`, `CAMPUSBITE_DB_NAME`, `CAMPUSBITE_DB_USER`, `CAMPUSBITE_DB_PASSWORD`, and/or `CAMPUSBITE_DB_PORT` in Apache's environment before starting it. Defaults are in `config/database.php`. Restart Apache after changing environment variables.

If Apache is using a nonstandard port, include it in the URL (for example `http://localhost:8080/campusbite/`). If the home page does not load, verify Apache and MySQL are running and the folder is named `campusbite` under `htdocs`.

## Demonstration accounts

All six seeded accounts use the temporary password **`password`**. Log in at `/campusbite/index.php?r=auth.login` (or use the Sign in link). The same complete account list is available in [`demo_accounts.txt`](demo_accounts.txt). Change the password for each account you keep; signed-in workspace users can use **Password & security** at the bottom of the sidebar. Do not expose the demo credentials on a live/public server.

| Role / status | Email | Password |
|---|---|---|
| Administrator | `admin@campusbite.test` | `password` |
| Student | `student@campusbite.test` | `password` |
| Student | `noah@campusbite.test` | `password` |
| Vendor — Campus Grill, active | `vendor@campusbite.test` | `password` |
| Vendor — Morning Mug, active | `mug@campusbite.test` | `password` |
| Vendor — Cebu Campus Bakes, storefront pending activation | `bakes@campusbite.test` | `password` |

Only administrators can create vendor accounts. Use **Admin → Vendors → Create vendor account** to enter the vendor login and storefront details; the administrator chooses whether its storefront starts Pending (closed) or Active. There is no public vendor sign-up or application route. The pre-seeded pending vendor can still demonstrate storefront activation. On an existing installation, back up the database and re-import the updated `schema.sql` so the profile-picture table is created and vendor roles receive the self-profile permission; seed statements use `INSERT IGNORE` and do not overwrite existing records or passwords. Keep a backup before any schema import or data change.

## Typical demonstration flow

1. As a visitor, browse and search foods, categories, and active vendor storefronts without signing in.
2. Sign in as a student, add items from one vendor to the cart, check out, and pay **cash at pickup**. Follow status changes under **My orders**; after completion, submit/edit a review for eligible items.
3. Sign in as an active vendor to manage foods/categories, record inventory movements, review incoming orders, update order status, and use the cash-only POS. The POS records its sale as a completed order.
4. Sign in as the administrator to create vendor accounts, activate/suspend storefronts, manage users and categories, moderate reviews/reports, edit CMS content/site settings, inspect inventory/reports, review audit entries, and select featured home-page foods under **Admin → Foods**.

Signed-in student, active or pending vendor, and admin accounts use role-specific left-side workspace navigation; on smaller screens it becomes a dismissible mobile drawer. Every signed-in role can update its own name, optional phone number, and profile picture under **Profile**. Names are read from the account record in order, review, vendor-owner, and administration views, so a saved name change appears across those views on the next load. Admins can also see profile pictures in **Admin → Users**. Phone numbers and profile pictures remain restricted to the owner and administrators; email changes are administrator-managed. Password and sign-out controls stay at the bottom of the sidebar. Visitors and other public pages keep the existing top navigation.

## Important behavior and scope

- The interface uses a high-contrast Neo-Brutalist palette and Cabinet Grotesk/Satoshi typography. Those web fonts are served by Fontshare when internet is available; system-font fallbacks keep the app readable offline. No font binaries are bundled in the project.
- **Payment is cash only.** The app does not integrate an online payment gateway, card processor, wallet, or delivery service.
- The home page shows up to three eligible featured foods, then up to three distinct foods with completed sales and up to three distinct foods with published student reviews. A featured pick must be listed, in stock, and owned by an active vendor; if the vendor has paused orders, the card is labeled and cannot be added to a cart. Administrators curate feature flags under **Admin → Foods**. Popularity counts both student pickups and vendor POS sales.
- A student checkout contains items from a single vendor. Inventory is checked and reserved in database transactions; valid cancellation/rejection releases reservations. POS sales also update stock transactionally.
- Application and database-session dates use Philippine time (Asia/Manila, UTC+8). Order outcomes are counted by order-created date; completed sales and completed-order metrics use completion dates. This distinction is shown in the vendor/admin reports.
- Users can upload, replace, or remove a profile picture from **Profile**. JPG, PNG, and WebP images up to 3 MiB and 3,000 × 3,000 pixels are stored with seeded/vendor food and CMS images in `media.data` (`LONGBLOB`) and served through the media route. Profile pictures are private to their owner and administrators unless deliberately reused as public content. There is no permanent `uploads` directory or Base64 image storage.
- Notifications are in-app and poll for updates every 12 seconds while a signed-in page is visible; new alerts update the unread badge and appear as a dismissible toast. No WebSocket or external push service is required. One-time action feedback (such as item, order, or settings changes) also appears as a dismissible popup; success/info messages auto-dismiss after a short delay, while error messages remain until dismissed. Email, SMS, native push, password-reset email, and external storage are not configured.
- Maintenance mode and other system settings are controlled by an administrator. Avoid enabling maintenance mode during a public demonstration.

## Security and data notes

The app uses password hashing, database-backed sign-in throttling keyed by a hashed email/IP pair, prepared `mysqli` statements, server-side role/permission checks, CSRF tokens on state-changing forms, session ID and CSRF-token regeneration at sign-in/password change, output escaping, validation, transaction-safe order/inventory operations, upload checks, and audit logging for administrative actions. New passwords must be 10 or more characters and no more than 72 bytes. Notification failures are logged after order/status database commits so they do not make saved transactions appear to have rolled back.

This project is intended as a local/demo campus app, not a hardened public production deployment. Before real use: replace demo credentials, configure a dedicated least-privilege database account and strong database password, use HTTPS, review campus privacy/security requirements, set backups and log rotation, test access controls, and have an administrator manage accounts. The standard XAMPP `root`/blank-password database configuration is for a local development machine only.

Application logs are written to `storage/logs/app.log` when Apache can write there. Keep the log directory non-public; the included `.htaccess` denies web access to it under Apache. Do not place database exports, credentials, or backups in the web root.
