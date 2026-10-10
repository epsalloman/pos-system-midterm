# Complete Point of Sale System

CodeIgniter 4 application for the IT0049 Midterm Project. It extends the existing `pos_system` database with staff authentication, product management, inventory-aware sales, and sales history while retaining customer and staff CRUD.

## Requirements

- PHP 8.2 or later with MySQLi and GD enabled
- Composer
- MySQL or MariaDB with an InnoDB database

## Local setup

1. Install dependencies from this project directory: `composer install`.
2. Copy `env` to `.env` if it does not already exist. Set `app.baseURL` and the `database.default.*` connection values for the `pos_system` database.
3. If starting from a clean database, import `pos_system.sql` first. The included migration then adds the midterm tables and authentication fields.
4. Run `php spark migrate`.
5. Start the local server with `php spark serve`, then open `http://localhost:8080/`.
6. Sign in using an existing username (for example `admin01`) and the one-time starter password `ChangeMe123!`. Every pre-existing staff account receives this initial password. Change each account's password from **Staff → Edit** before using the system.

Do not commit `.env`. For deployment, configure production environment variables, HTTPS, database credentials, and writable upload directories in the hosting service.

## Features and routes

- `/login` and `POST /logout`: staff authentication; active staff accounts only
- `/dashboard`: authenticated dashboard
- `/products`: add, list, edit, and archive products with display-ready image uploads
- `/customers`: create, list, edit, and delete customers
- `/users`: create, list, edit, and delete staff accounts when they have no sales; accounts with sales are archived so history stays valid. Passwords are hashed.
- `/sales/new`: record one-product sale with optional customer
- `/sales`: history shows product, customer, staff, quantity, total, and date

All management routes are protected by the `auth` filter. Forms use CSRF protection. Product and staff image uploads are checked and resized before display. Archived products and staff remain available to historical sales. Deleting a customer sets the customer reference in old sales to NULL.

Sale recording uses a database transaction and locks the selected product row. It checks available stock, reduces stock, and records the sale together; an insufficient-stock request is rejected without a sale record.

## First run checks

- Without a login, `/products`, `/customers`, `/users`, `/sales/new`, and `/sales` redirect to `/login`.
- Invalid credentials remain on the login page; logout destroys the staff session.
- Verify add/edit/archive or delete flows for products, customers, and staff.
- Test an image upload and reject a non-image or image larger than the stated limit.
- Record a sale at available stock; confirm stock decreases and the sale appears in history.
- Try to sell more than available stock; confirm the sale is rejected and stock is unchanged.

## Database

`pos_system.sql` is the starting database export. `app/Database/Migrations/2026-10-10-130000_AddPosProductsSalesAuthentication.php` upgrades it with a staff password column and active status, plus the `products` and `sales` tables. Run migrations after importing the base export; do not re-import the base SQL over an existing project database.
