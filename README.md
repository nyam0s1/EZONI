🌾 Ezoni Farm ERP

A Digital Operating System for Modern Agriculture

EZONI Farm is a full-stack Farm Management System designed to digitize the chaotic operations of livestock and crop farming. It replaces manual record-keeping with a centralized, secure, and data-driven dashboard.

📸 System Previews
1. The Command Center (Dashboard)

Real-time metrics on livestock health, low-stock alerts, and daily revenue.

2. Financial Intelligence

Automated P&L reports with time-flexible filtering to analyze historical performance.

----

🚀 Key Features

🐄 Smart Livestock Tracking

* Lineage Management: Tracks parentage (Mother ID) to monitor breeding cycles and genetic health.
* Batch & Tagging: Supports individual tagging (e.g., COW-001) or batch management for flocks (e.g., FLOCK-A).
* Status Lifecycle: Manages states like `Active`, `Sick/Isolated`, `Deceased`, or `Sold` with audit logs.

💰 Financial Engine

* Net Profit Algorithms: Automatically calculates `(Sales Revenue) - (Feed/Input Costs)` to show true profitability.
* Visual Analytics: Integrated Chart.js to render income trends over custom date ranges.
* Inventory Cost Averaging: Tracks unit costs of inputs (feeds, meds) to calculate precise expenses.

🔐 Enterprise-Grade Security

* Role-Based Access Control (RBAC): Distinct `Admin` (System Config) vs. `User` (Day-to-Day) interfaces.
* Audit Trails: Logs every critical action (Login, Stock Adjustment, Sale) with `User ID`, `Timestamp`, and `IP`.
* Secure Auth: Uses PHP password hashing and HTTPS on Vercel.

📱 Progressive Web App (PWA)

* Mobile-First Design: Fully responsive Glassmorphism UI that works on smartphones in the field.
* Installable: Includes `manifest.json` for "Add to Home Screen" capability.

----

🛠️ Technical Architecture

The system is built on a **Modular View Architecture** using vanilla PHP, ensuring high performance with zero framework overhead.

* Backend: PHP (PDO Data Objects) for database abstraction and security.
* Database: PostgreSQL or MySQL with relational tables for users, animals, stock, sales and logs.
* Frontend: Custom CSS variables for theming (Dark Mode default) and CSS Grid/Flexbox for layout.

### Database Schema (Simplified)

* `users`: Stores credentials and role definitions.
* `animals`: Handles biological data, breed, and status.
* `stock`: Inventory levels with `min_threshold` triggers.
* `sales_logs` & `stock_logs`: The immutable ledger for all financial transactions.

----

👨‍💻 Author

Ian Nyamosi

Role: Full-Stack Developer & System Architect

LinkedIn Profile(https://www.google.com/search?q=https://linkedin.com/in/ian-nyamosi)


*Built with ❤️ and PHP in Nairobi, Kenya.*

## Vercel deployment

Vercel is the deployment target. The previous InfinityFree FTP deployment workflow has been removed. The app keeps its existing PHP screens, CSS, login, role navigation and farm workflows. A single routed function uses the [community PHP runtime](https://github.com/vercel-community/php), and PHP sessions are stored in the database so logins survive separate function instances.

This is a fresh installation. No records or accounts were recovered from the unavailable old database. The repository includes schemas for [PostgreSQL](database/schema_postgres.sql) and [MySQL](database/schema.sql) and a CLI initializer; the simplified schema above is only an overview. Starter categories and species can be edited through the existing **Admin > Settings** pages.

1. Import this repository into Vercel, use `main` as the production branch and the repository root as the source directory. The `android` branch also contains the Android app and deployment changes. Keep the repository's `vercel.json` configuration and set the framework preset to **Other**.
2. Add a Neon PostgreSQL database through Vercel's Marketplace and connect it to the project. The app reads the project's `DATABASE_URL` connection string. Use TLS in the connection string, and use a direct connection for initialization if the provider supplies separate direct and pooled URLs. For independent web and Android records, provision a separate database for each installation.
3. From a trusted terminal with PHP and `pdo_pgsql`, set `DATABASE_URL`, `INITIAL_ADMIN_USERNAME`, `INITIAL_ADMIN_EMAIL`, and `INITIAL_ADMIN_PASSWORD`, then run `php database/initialize.php` from the repository root. The initializer selects the matching schema and creates the first active administrator with a hashed password. See [`database/README.md`](database/README.md) for the full setup contract.
4. Clear the `INITIAL_ADMIN_*` variables after setup; keep them out of Vercel's application environment. Confirm that `DATABASE_URL` is available in the project's intended deployment environment, then deploy and verify login, navigation, record changes and reports.
5. Build Android with `-PezoniWebBaseUrl=<verified-https-deployment-url>/`. For a separate Android database, use a second Vercel project with its own database settings and pass that project's verified HTTPS address. See [`android/README.md`](android/README.md).

A hosted MySQL-compatible database remains supported. Use PHP with `pdo_mysql` and set `DB_HOST`, `DB_PORT` (default `3306`), `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and `DB_SSL_REQUIRED=true` instead of `DATABASE_URL`. If the provider supplies a CA certificate, set `DB_SSL_CA` to its readable PEM file path; on Vercel that path must refer to a file available to the deployed function. Run the same CLI initializer against the empty database.

Keep database and administrator credentials out of Git. `.env.example` documents the environment settings; PHP does not load `.env` files automatically. Registration continues to create pending users, which an administrator approves through the existing Users screen.
