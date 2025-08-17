# TaskBuddy - Survey Rewards Platform

[![License: Apache 2.0](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1.svg)](https://mysql.com)

**TaskBuddy** is a comprehensive web platform that connects users with paid survey opportunities. Users can earn money by participating in surveys, manage their earnings, and withdraw funds through multiple payment methods. The platform features a robust admin panel for managing surveys, users, and financial transactions.

## 🌟 Features

### 👤 User Features

- **User Registration & Authentication**

  - Secure user registration with email verification
  - Session management with configurable timeouts
  - "Remember Me" functionality (30-day sessions)
  - Password security with bcrypt hashing

- **Survey Participation**

  - Browse available surveys with reward information
  - Interactive survey completion interface
  - JSON-based flexible question types (multiple choice, text, ratings)
  - Automatic reward calculation and balance updates

- **Financial Management**

  - Real-time balance tracking
  - Multiple withdrawal methods (Bank Transfer, Bkash)
  - Withdrawal history with status tracking
  - Transaction management system

- **Support System**

  - Ticket-based support system
  - Real-time messaging with admin responses
  - Ticket history and status tracking
  - Priority-based ticket classification

- **User Dashboard**
  - Comprehensive earnings overview
  - Task completion statistics
  - Quick access to all platform features
  - Responsive design for all devices

### 🛡️ Admin Features

- **User Management**

  - View and manage all registered users
  - User activity monitoring
  - Account status management

- **Survey Management**

  - Create and edit surveys with flexible question types
  - Survey status management (active/inactive)
  - Response tracking and analytics

- **Financial Administration**

  - Withdrawal request approval/rejection system
  - Balance verification and fraud prevention
  - Transaction history and reporting
  - Automated balance deduction on approved withdrawals

- **Support Management**

  - View and respond to user tickets
  - Ticket priority and status management
  - Admin reply system with user notifications

- **Administrative Tools**
  - Multiple admin account management
  - Secure admin authentication
  - Comprehensive dashboard with statistics

## 🏗️ System Architecture

### Frontend

- **Framework**: Vanilla PHP with modern HTML5/CSS3
- **Styling**: Tailwind CSS for responsive design
- **Icons**: Font Awesome 6.0
- **JavaScript**: ES6+ for interactive elements

### Backend

- **Language**: PHP 7.4+
- **Database**: MySQL 5.7+ with PDO
- **Session Management**: PHP Sessions with security configurations
- **Security**: Password hashing, CSRF protection, SQL injection prevention

### Database Schema

```sql
├── users (User accounts and authentication)
├── user_profiles (Extended user information)
├── admins (Administrator accounts)
├── surveys (Survey definitions and questions)
├── survey_responses (User survey submissions)
├── transactions (Financial transactions and withdrawals)
├── tickets (Support ticket system)
└── ticket_replies (Support conversation history)
```

## 🚀 Installation

### Prerequisites

- **XAMPP/WAMP/LAMP** with:
  - PHP 7.4 or higher
  - MySQL 5.7 or higher
  - Apache Web Server

### Setup Instructions

1. **Clone or Download the Repository**

   ```bash
   git clone https://github.com/wblake-dev/task-buddy.git
   # OR download and extract the ZIP file
   ```

2. **Move to Web Directory**

   ```bash
   # For XAMPP
   mv task-buddy /xampp/htdocs/task_buddy

   # For WAMP
   mv task-buddy /wamp64/www/task_buddy
   ```

3. **Database Setup**

   ```bash
   # Start Apache and MySQL services
   # Access phpMyAdmin or MySQL command line

   # Import the database schema
   mysql -u root -p < database_setup.sql

   # Or run the setup script
   php setup_database.php
   ```

4. **Configuration**

   - Update database credentials in configuration files if needed
   - Default credentials:
     - Host: `localhost`
     - Username: `root`
     - Password: (empty)
     - Database: `task_buddy_db`

5. **Default Admin Account**

   - Username: `admin`
   - Password: `password`
   - Secondary Admin: `H` / `8`

6. **Access the Application**
   - User Interface: `http://localhost/task_buddy/`
   - Admin Panel: `http://localhost/task_buddy/Admin/admin_login.php`

## 📁 Project Structure

```
task_buddy/
├── index.php                 # Main entry point (redirects to user/)
├── database_setup.sql        # Database schema and initial data
├── setup_database.php        # Database setup script
├── LICENSE                   # Apache 2.0 License
├── README.md                # This file
├──
├── config/
│   └── config.php           # Application configuration
├──
├── includes/
│   └── session_helper.php   # Session management utilities
├──
├── user/
│   ├── index.php           # User homepage and landing page
│   ├── profile.php         # User profile management
│   ├── db.php              # Database connection utilities
│   └── auth/
│       ├── login.php       # User authentication
│       ├── register.php    # User registration
│       ├── logout.php      # Session termination
│       └── change_password.php
├──
├── dashboard/
│   └── dashboard.php       # User dashboard interface
├──
├── surveys/
│   ├── survey.php          # Available surveys listing
│   ├── participate_survey.php
│   ├── submit_survey.php
│   ├── create_survey.php
│   └── edit_survey.php
├──
├── withdrawals/
│   ├── withdraw.php        # Withdrawal request form
│   └── withdraw_history.php # Withdrawal history
├──
├── tickets/
│   ├── open_ticket.php     # Create support ticket
│   ├── ticket_history.php  # User ticket history
│   └── ticket_detail.php   # Ticket conversation view
├──
└── Admin/
    ├── admin_login.php      # Admin authentication
    ├── admindashboard.php   # Admin dashboard
    ├── manage_users.php     # User management
    ├── manage_surveys.php   # Survey administration
    ├── create_survey.php    # Survey creation
    ├── admin_withdrawals.php # Withdrawal management
    ├── support_ticket.php   # Support ticket management
    ├── create_new_admin.php # Admin user creation
    └── logout.php          # Admin logout
```

## 🔧 Configuration

### Database Configuration

Located in multiple files for different modules:

```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "task_buddy_db";
```

### Session Configuration

Session security settings in `includes/session_helper.php`:

```php
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
```

### Timeout Settings

- Standard Session: 24 hours
- Remember Me: 30 days
- Admin Session: Browser session

## 💳 Payment Methods

The platform supports multiple withdrawal methods:

1. **Bank Transfer**

   - Requires bank name and account number
   - Manual admin approval process
   - Secure transaction logging

2. **Bkash (Mobile Financial Service)**
   - Bangladeshi mobile payment system
   - Requires Bkash number
   - Quick digital transfers

## 🔐 Security Features

- **Authentication Security**

  - Password hashing using PHP's `password_hash()`
  - Session fixation prevention
  - CSRF protection mechanisms
  - SQL injection prevention with PDO prepared statements

- **Financial Security**

  - Transaction verification before balance deduction
  - Double-spending prevention
  - Admin approval required for withdrawals
  - Comprehensive audit trails

- **Data Protection**
  - Input sanitization and validation
  - XSS prevention with `htmlspecialchars()`
  - Secure session management
  - Environment-specific configurations

## 🌐 Browser Support

- **Modern Browsers**: Chrome 60+, Firefox 55+, Safari 12+, Edge 79+
- **Mobile Responsive**: iOS Safari, Chrome Mobile, Samsung Internet
- **JavaScript**: ES6+ features used for enhanced functionality

## 📊 Admin Dashboard Features

### Statistics Overview

- Total registered users
- Active surveys count
- Pending withdrawal requests
- Total withdrawn amounts

### Real-time Management

- User account management
- Survey creation and editing
- Withdrawal approval workflow
- Support ticket responses

### Reporting

- Transaction history
- User activity logs
- Survey response analytics
- Financial reporting tools

## 🎯 User Journey

1. **Registration**: User creates account with email and password
2. **Profile Setup**: Complete profile information for survey matching
3. **Survey Participation**: Browse and complete available surveys
4. **Earnings**: Automatic balance updates upon survey completion
5. **Withdrawal**: Request payout through preferred payment method
6. **Support**: Access help through integrated ticket system

## 🛠️ Development

### Adding New Features

1. **Database Changes**: Update `database_setup.sql` with new schema
2. **Backend Logic**: Create PHP files in appropriate directories
3. **Frontend**: Use Tailwind CSS classes for consistent styling
4. **Security**: Implement proper validation and sanitization

### Code Style

- **PHP**: PSR-12 coding standards
- **HTML**: Semantic HTML5 elements
- **CSS**: Tailwind utility classes
- **JavaScript**: Modern ES6+ syntax

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature-name`
5. Submit a Pull Request

## 📝 License

This project is licensed under the Apache License 2.0 - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### For Users

- Use the in-app support ticket system
- Check the FAQ section on the homepage
- Contact admin through the platform

### For Developers

- Review the codebase documentation
- Check the database schema in `database_setup.sql`
- Examine the session management in `includes/session_helper.php`

### Common Issues

#### Installation Issues

- Ensure XAMPP/WAMP services are running
- Verify database credentials
- Check file permissions

#### Login Problems

- Clear browser cache and cookies
- Verify database connection
- Check session configuration

#### Payment Issues

- Verify user balance before withdrawal
- Check admin approval status
- Review transaction logs

## 🔄 Recent Updates

- Enhanced security features with session management
- Improved responsive design with Tailwind CSS
- Added comprehensive support ticket system
- Implemented secure payment processing
- Added admin dashboard with real-time statistics

## 🚧 Roadmap

- [ ] Email notification system
- [ ] Advanced survey analytics
- [ ] Mobile app development
- [ ] Payment gateway integration
- [ ] Multi-language support
- [ ] API development for third-party integrations

---

**TaskBuddy** - Turning opinions into earnings, one survey at a time! 💰

For more information, visit our [GitHub repository](https://github.com/wblake-dev/task-buddy) or contact the development team.
