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
* Secure Auth: Implements Bcrypt Hashing for passwords and enforces **HTTPS/SSL** via `.htaccess`.

📱 Progressive Web App (PWA)

* Mobile-First Design: Fully responsive Glassmorphism UI that works on smartphones in the field.
* Installable: Includes `manifest.json` for "Add to Home Screen" capability.

----

🛠️ Technical Architecture

The system is built on a **Modular View Architecture** using vanilla PHP, ensuring high performance with zero framework overhead.

* Backend: PHP (PDO Data Objects) for database abstraction and security.
* Database: MySQL (Relational Schema) with normalized tables for `Users`, `Animals`, `Stock`, `Sales`, and `Logs`.
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
