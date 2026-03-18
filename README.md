# Sales Dashboard

A complete web-based sales management system with login, dashboard with dynamic charts, and full CRUD functionality.

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript, Bootstrap 5
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB
- **Charts:** Chart.js

## Folder Structure

```
sales-dashboard/
├── api/
│   └── chart-data.php      # API for dynamic chart data
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── chart-config.js
├── config/
│   ├── auth.php            # Session & auth helpers
│   └── db.php              # Database connection (PDO)
├── includes/
│   ├── header.php
│   ├── navbar.php
│   └── footer.php
├── pages/
│   ├── index.php           # Login page
│   ├── dashboard.php       # Dashboard with charts
│   ├── data.php            # Sales CRUD
│   ├── products.php        # Products CRUD
│   └── logout.php
├── sql/
│   ├── schema.sql          # Database schema + seed data
│   └── setup-users.php     # Set admin123 password
├── index.php               # Entry point (redirects)
└── README.md
```

## How to Run with XAMPP

### 1. Install XAMPP

- Download and install XAMPP from [apachefriends.org](https://www.apachefriends.org/)
- Start **Apache** and **MySQL** from the XAMPP Control Panel

### 2. Set Up the Project

1. Copy the `sales-dashboard` folder to:
   - **Windows:** `C:\xampp\htdocs\sales-dashboard`
   - **Mac/Linux:** `/Applications/XAMPP/htdocs/sales-dashboard` or `~/xampp/htdocs/sales-dashboard`

2. Open **phpMyAdmin** at: `http://localhost/phpmyadmin`

3. Create the database and import the schema:
   - Click **New** to create a database named `sales_dashboard`
   - Select the `sales_dashboard` database
   - Go to **Import** tab
   - Choose `sales-dashboard/sql/schema.sql`
   - Click **Go**

4. (Optional) Set password to `admin123`:
   - Open terminal/command prompt
   - Navigate to project: `cd C:\xampp\htdocs\sales-dashboard`
   - Run: `php sql/setup-users.php`

   Or use the default login: **admin / password**

### 3. Access the Application

- Open: **http://localhost/sales-dashboard/**
- Or: **http://localhost/sales-dashboard/pages/index.php**

### 4. Database Configuration

If your MySQL uses different credentials, edit `config/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sales_dashboard');
define('DB_USER', 'root');
define('DB_PASS', '');  // Set your MySQL password if needed
```

## Features

- **Login system** with admin/user roles
- **Dashboard** with summary cards, bar chart, pie chart, line chart
- **Filters** for period, product group, product (charts update dynamically)
- **CRUD** for sales data and products
- **Security:** PDO prepared statements (SQL injection protection)
- **Responsive UI** with Bootstrap 5
## License

MIT
