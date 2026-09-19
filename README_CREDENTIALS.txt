EcoSprout - initial login credentials (for development/testing)

Database: ecosprout_db

Initial user accounts created by setup.php:

1) Admin
   Username: admin@ecosprout
   Password: Admin@123
   Role: Admin

2) Manager
   Username: manager@ecosprout
   Password: Manager@123
   Role: Manager

3) User
   Username: user@ecosprout
   Password: User@123
   Role: User

Instructions:
- Start XAMPP (MySQL and Apache)
- Place this project in XAMPP's htdocs (already assumed: C:\xampp\htdocs\EcoSprout)
- Open http://localhost/EcoSprout/setup.php in your browser to create the database and users. If your MySQL root user has a password, edit setup.php and db.php to set $DB_PASS accordingly before running.
- After setup, open http://localhost/EcoSprout/ to see the login page. The login page now includes links to register and to request a password reset.
- Use the credentials above to login or create a new account via the Register link. On success you will be redirected to /user/success.php which displays a simple role-specific message.

Project structure (restructured):
- /admin        -> admin operations (users, bookings, sales report)
- /auth         -> authentication and password reset flow
- /catalog      -> plant catalogue, checkout, workshops/events
- /management   -> manager/admin operational pages
- /user         -> user dashboard and profile pages
- /assets/css   -> shared styles
- /assets/js    -> shared frontend scripts

Security notes (development only):
- Remove or protect setup.php after use to avoid re-running it in production.
- The example uses the default root MySQL account which is fine for local development but NOT for production.
