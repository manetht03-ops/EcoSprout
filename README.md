# EcoSprout

EcoSprout is a plant nursery management application built with HTML, CSS, JavaScript, and PHP. It supports role-based access for Admin, Manager, and User accounts and includes a simplified local demo workflow for plant browsing, workshop registration, order checkout, and nursery administration.

## Current Status

The application has progressed beyond a basic login demo and now includes the following implemented areas:

- User authentication with role-based access control
- Login, logout, and session flow
- User registration and password reset flow
- Admin, Manager, and User dashboards
- Plant catalogue browsing
- Workshop browsing and registration
- Customer inquiry submission and management
- Order checkout flow with demo payment validation
- Admin user management screen
- Admin sales and bookings overview
- Shared navigation and cleaned-up UI layout

## Features Completed

### Authentication and Access Control
- Login page with email-based credentials
- Role-based dashboard routing for Admin, Manager, and User
- Session-based protection for authenticated pages
- Registration page for creating new user accounts
- Password reset request and reset confirmation flow
- Initial system data seeded through `setup.php`

### User Roles
Initial seeded roles are:

- Admin: `admin@ecosprout` / `Admin@123`
- Manager: `manager@ecosprout` / `Manager@123`
- User: `user@ecosprout` / `User@123`

### Payment Handling
The checkout flow has been hardened into a local demo payment gateway using a dedicated mock payment class.

Implemented behavior:
- validates cardholder name, payment method, card number, expiry date, and CVV
- rejects expired cards
- creates a payment reference for success tracking
- stores only masked card information and order/payment metadata instead of full card data
- keeps stock updates and order creation in a database transaction

This is suitable for a local project demo and is intentionally not connected to a real payment provider.

### UI Polishing and UX Cleanup
The interface has been improved to make the app feel more like a real portal instead of a plain list of links.

Improvements include:
- cleaner landing page layout
- shared navigation with session-safe logic
- dashboard redesign with summary cards and profile overview
- reduced duplication between dashboard links and nav menu
- responsive styling for smaller screens
- more consistent spacing, colors, and card-based layout

## Local Setup

EcoSprout is built using **HTML, CSS, JavaScript, and PHP**. To run the application locally, **XAMPP** is required.

### Prerequisites

* Install [XAMPP](https://www.apachefriends.org/)
* Make sure **Apache** and **MySQL** are available in XAMPP.

### Installation & Setup

1. Clone the EcoSprout repository into:

   ```text
   C:/xampp/htdocs/
   ```

2. Start **XAMPP**.

3. Start the following services from the XAMPP Control Panel:

   * **Apache**
   * **MySQL**

4. **Update the database credentials** in both `setup.php` and `db.php` according to your local MySQL configuration.

   Locate the following variables in both files:

   ```php
   $DB_USER = 'root';
   $DB_PASS = 'mysql';
   ```

   Replace them with your local MySQL credentials if needed.

   > **⚠️ Important:** The credentials included in `setup.php` and `db.php` are development credentials for local XAMPP usage and may need to be adjusted for your machine.

5. Open the following URL in your browser:

   ```text
   http://localhost/EcoSprout/setup.php
   ```

6. Run `setup.php` **once** to initialize the application's database and seed initial records.

7. After setup, access the application via:

   ```text
   http://localhost/EcoSprout/
   ```

   You can also refer to `README_CREDENTIALS.txt` for the initial credentials.

   > **⚠️ Important:** Do not run `setup.php` again after adding real data. It resets the demo database state and will remove newly created records.

## Project Structure

* `index.php` - login entry page
* `db.php` - database connection
* `setup.php` - database and seed setup
* `assets/css/` - shared styles
* `assets/js/` - frontend scripts
* `auth/` - authentication pages and password reset
* `catalog/` - plant catalogue, checkout, and workshops
* `user/` - dashboard, profile, customer actions
* `management/` - manager/admin management screens
* `admin/` - admin-only modules and reports
* `includes/` - shared helpers and gateway logic

## Notes and Future Work

The current application is ready as a functional local demo, but some items are still best treated as future improvements:

- Integrate a real payment provider for production use
- Add stronger security features like CSRF protection and input sanitization beyond current project scope
- Add real email sending for password reset and notifications
- Expand the reporting and analytics screens
- Finalize styling once a formal UI design is approved

## Application URL

* Login entry: `http://localhost/EcoSprout/`
