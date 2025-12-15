# RMS - Remittance Management System

A comprehensive in-house remittance management platform built with Pure PHP + MySQLi, MVC-like architecture, and Bootstrap 5 UI.

## Features

- **Role-Based Access Control (RBAC)**: Admin, Branch Manager, and Agent roles with granular permissions
- **2FA Authentication**: SMS OTP-based two-factor authentication
- **Multi-Currency Support**: Handle remittances in multiple currencies
- **Commission Management**: Dynamic tier-based commission calculation
- **Approval Workflows**: Manager approval for large/flagged transactions
- **Settlement System**: Agent balance withdrawal and commission payout workflows
- **Audit Logging**: Complete activity tracking for compliance
- **Notifications**: SMS and Email alerts for critical events
- **Responsive UI**: Professional Bootstrap 5 interface

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- XAMPP/WAMP/LAMP (for local development)

### Setup Steps

1. **Clone/Copy Files**
   ```
   Copy all files to your web server directory
   Example: e:\xampp\htdocs\bin\mysql\bu\rms
   ```

2. **Create Database**
   ```bash
   # Import the schema file
   mysql -u root -p < database/schema.sql
   ```
   
   Or use phpMyAdmin:
   - Open phpMyAdmin
   - Click "Import"
   - Select `database/schema.sql`
   - Click "Go"

3. **Configure Database Connection**
   
   Edit `app/config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'rms_db');
   ```

4. **Configure Apache**
   
   Ensure `.htaccess` is enabled and mod_rewrite is active:
   ```apache
   a2enmod rewrite   # Ubuntu/Debian
   ```

5. **Set Permissions** (Linux/Mac only)
   ```bash
   chmod -R 755 .
   chmod -R 777 uploads (if exists)
   ```

6. **Access Application**
   ```
   http://localhost/bin/mysql/bu/rms
   ```

### Default Credentials

- **Username**: `admin`
- **Password**: `admin123`

⚠️ **IMPORTANT**: Change the default password immediately after first login!

## Directory Structure

```
rms/
├── app/
│   ├── config/          # Database and app configuration
│   ├── controllers/     # Application controllers
│   ├── helpers/         # Helper functions
│   └── autoload.php     # Core autoloader
├── assets/
│   ├── css/            # Stylesheets
│   └── js/             # JavaScript files
├── database/
│   └── schema.sql      # Database schema
├── views/
│   ├── partials/       # Reusable components (header, footer, sidebar)
│   └── *.php          # View files
├── .htaccess          # Apache configuration
└── index.php          # Entry point
```

## Database Schema

The system includes 12 tables:

1. **roles** - User roles (Admin, Manager, Agent)
2. **permissions** - System permissions
3. **role_permissions** - Role-permission mappings
4. **branches** - Branch information
5. **users** - User accounts
6. **user_accounts** - Agent balances and bank accounts
7. **commission_tiers** - Commission calculation tiers
8. **remittances** - Transaction records
9. **settlements** - Settlement requests and approvals
10. **notifications_log** - SMS/Email notification history
11. **activity_log** - Audit trail
12. **otp_codes** - 2FA OTP codes

## User Roles & Permissions

### Admin
- Full system access
- User and role management
- Branch management
- All reporting capabilities
- Account freeze/unfreeze

### Branch Manager
- View all transactions
- Approve/reject remittances
- Approve/reject settlements
- View reports
- Suspend users

### Agent
- Create remittances
- Request settlements
- View own transactions
- Basic reporting

## Development Status

- ✅ Phase 1: Admin Dashboard
- ✅ Phase 2: Database Schema
- ⏳ Phase 3: Auth & Authorization (Next)
- ⏳ Phase 4: Core Modules
- ⏳ Phase 5: Testing & Refinement

## Security Features

- Password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- CSRF protection (coming in Phase 3)
- Session management
- Account lockout after failed attempts
- 2FA support

## Support & Documentation

For issues or questions, refer to the code documentation or contact the development team.

## License

Internal use only - Proprietary
