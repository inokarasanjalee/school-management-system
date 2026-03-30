# School Management System[my_ss_system]

A complete school management system that allows schools to register, manage their device inventory (computers, laptops, projectors, etc.), and send feedback to administrators.

## Features

### Authentication
- School registration with census number validation
- Email verification system
- Secure login with password hashing
- Password reset via email
- Session-based authentication

### Device Management
- Add new devices with details (type, model, serial number, condition, status)
- View all devices in a sortable table
- Edit device information
- Delete devices
- Real-time device search
- Device condition tracking (Excellent, Good, Fair, Needs Repair)
- Device status tracking (Active, In Repair, Retired)

###  Feedback 
- Send feedback to administrators
- Email notifications for feedback
- Subject and message fields

###  Profile Management
- View school information
- View account details
- Registration date tracking

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: Bootstrap 5, HTML5, CSS3
- **Email**: PHPMailer with Gmail SMTP
- **Icons**: Bootstrap Icons

## Installation Guide

### Prerequisites
- XAMPP / WAMP / LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Step 1: Setup Local Server
1. Install XAMPP (or your preferred local server)
2. Start Apache and MySQL services

### Step 2: Project Placement
Place the entire `my_ss_system` folder in your web server directory:
- **XAMPP**: `C:\xampp\htdocs\`
- **WAMP**: `C:\wamp\www\`
- **Linux**: `/var/www/html/`

### Step 3: Database Setup
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database named `school_system`
3. Import the `database.sql` file:
   - Click on `school_system` database
   - Click "Import" tab
   - Choose `database.sql` file
   - Click "Go"

### Step 4: Configure Database Connection
Open `db.php` and verify these settings (usually default):
```php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'school_system';