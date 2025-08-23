# Eye-Book Plugin Deployment Guide
**Enterprise Appointment System for Eye Care Practices**  
**Version:** 1.0.0  
**Developer:** Fatih Berkay Bahçeci  
**Last Updated:** August 20, 2025

---

## Table of Contents

1. [Pre-Deployment Requirements](#pre-deployment-requirements)
2. [System Requirements](#system-requirements)
3. [Installation Steps](#installation-steps)
4. [Configuration](#configuration)
5. [Security Setup](#security-setup)
6. [HIPAA Compliance Setup](#hipaa-compliance-setup)
7. [Testing & Verification](#testing--verification)
8. [Performance Optimization](#performance-optimization)
9. [Monitoring & Maintenance](#monitoring--maintenance)
10. [Backup & Recovery](#backup--recovery)
11. [Troubleshooting](#troubleshooting)
12. [Support & Updates](#support--updates)

---

## Pre-Deployment Requirements

### Environment Assessment
Before deploying Eye-Book, ensure your environment meets the following requirements:

#### WordPress Environment
- **WordPress Version:** 5.8 or higher (6.0+ recommended)
- **PHP Version:** 8.0 or higher (8.1+ recommended)
- **MySQL Version:** 5.7 or higher (8.0+ recommended)
- **Web Server:** Apache 2.4+ or Nginx 1.18+

#### Server Specifications
- **RAM:** Minimum 4GB (8GB+ recommended)
- **Storage:** Minimum 10GB free space (SSD recommended)
- **CPU:** Minimum 2 cores (4+ cores recommended)
- **Bandwidth:** Minimum 100Mbps connection

#### Security Requirements
- **SSL Certificate:** Required (TLS 1.3 recommended)
- **Firewall:** Configured with appropriate rules
- **Backup System:** Automated backup solution
- **Monitoring:** Server and application monitoring tools

---

## System Requirements

### Technical Requirements

```
WordPress: 5.8+
PHP: 8.0+ (with extensions: mysqli, openssl, curl, json, mbstring)
MySQL: 5.7+ or MariaDB 10.3+
Web Server: Apache 2.4+ or Nginx 1.18+
SSL: TLS 1.2+ certificate (TLS 1.3 recommended)
Memory Limit: 256MB+ (512MB recommended)
Max Execution Time: 300 seconds
Upload Limit: 50MB+
```

### PHP Extensions Required
```
- mysqli (database connectivity)
- openssl (encryption/SSL)
- curl (API communications)
- json (data processing)
- mbstring (string handling)
- gd or imagick (image processing)
- zip (backup functionality)
- bcmath (financial calculations)
```

### WordPress Plugins Compatibility
Eye-Book is compatible with most WordPress plugins but has been specifically tested with:
- Security plugins (Wordfence, Sucuri)
- Caching plugins (WP Rocket, W3 Total Cache)
- Backup plugins (UpdraftPlus, BackWPup)
- SEO plugins (Yoast, RankMath)

---

## Installation Steps

### Step 1: Download and Upload Plugin

1. **Download the Eye-Book Plugin**
   ```bash
   # If using git
   git clone https://github.com/your-repo/eye-book-plugin.git
   cd eye-book-plugin
   ```

2. **Upload to WordPress**
   ```bash
   # Upload via FTP to wp-content/plugins/
   # Or use WordPress admin dashboard: Plugins > Add New > Upload Plugin
   ```

3. **Set File Permissions**
   ```bash
   # Set appropriate file permissions
   chmod 644 eye-book/*.php
   chmod 755 eye-book/
   chmod 755 eye-book/assets/
   chmod 644 eye-book/assets/*
   ```

### Step 2: Database Preparation

1. **Create Database Backup**
   ```sql
   mysqldump -u username -p database_name > backup_before_eyebook.sql
   ```

2. **Verify Database User Permissions**
   ```sql
   SHOW GRANTS FOR 'wp_user'@'localhost';
   -- Ensure CREATE, ALTER, DROP, INSERT, UPDATE, DELETE, SELECT permissions
   ```

### Step 3: Activate Plugin

1. **WordPress Admin Dashboard**
   - Navigate to Plugins > Installed Plugins
   - Find "Eye-Book" in the list
   - Click "Activate"

2. **Verify Activation**
   - Check for "Eye-Book" menu in admin sidebar
   - Verify no error messages appear
   - Confirm database tables are created

### Step 4: Initial Configuration

1. **Access Eye-Book Settings**
   ```
   WordPress Admin > Eye-Book > Settings
   ```

2. **Run Setup Wizard**
   - Complete the guided setup process
   - Configure basic settings
   - Set up first location and provider

---

## Configuration

### Basic Configuration

#### 1. General Settings
```php
// Navigate to Eye-Book > Settings > General
Basic Information:
- Practice Name: Your Eye Care Practice
- Primary Location: Main clinic address
- Contact Information: Phone, email, website
- Timezone: Set to local timezone
- Date/Time Format: Choose preferred format
```

#### 2. Appointment Settings
```php
// Eye-Book > Settings > Appointments
Default Settings:
- Default Appointment Duration: 30 minutes
- Booking Window: 6 months ahead
- Cancellation Policy: 24 hours notice
- Reminder Settings: Email + SMS
- Business Hours: Configure for each location
```

#### 3. User Roles Configuration
```php
// Users > Roles (Eye-Book roles will be available)
Available Roles:
- Eye-Book Administrator: Full access
- Eye Care Provider: Patient records, appointments
- Nurse: Limited patient access, appointments
- Receptionist: Appointments, basic patient info
- Patient: Own records only
```

### Advanced Configuration

#### 1. Email Settings
```php
// Eye-Book > Settings > Notifications
SMTP Configuration:
- SMTP Server: smtp.yourdomain.com
- Port: 587 (TLS) or 465 (SSL)
- Authentication: Username/Password
- From Address: noreply@yourpractice.com
- From Name: Your Practice Name
```

#### 2. SMS Configuration
```php
// Eye-Book > Settings > SMS
Twilio Setup:
- Account SID: Your Twilio SID
- Auth Token: Your Twilio Auth Token
- Phone Number: Your Twilio phone number
- Message Templates: Customize SMS templates
```

#### 3. Payment Gateway Setup
```php
// Eye-Book > Settings > Payments
Stripe Configuration:
- Publishable Key: pk_live_...
- Secret Key: sk_live_...
- Webhook URL: yoursite.com/wp-admin/admin-ajax.php?action=eye_book_stripe_webhook

Square Configuration:
- Application ID: Your Square App ID
- Access Token: Your Square Access Token
- Environment: Production
```

---

## Security Setup

### 1. SSL/TLS Configuration

```apache
# Apache .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security Headers
Header always set X-Frame-Options SAMEORIGIN
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

### 2. WordPress Security Hardening

```php
// wp-config.php additions
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);
define('FORCE_SSL_ADMIN', true);
define('WP_AUTO_UPDATE_CORE', true);

// Security salts - generate new ones
define('AUTH_KEY',         'generate-unique-key');
define('SECURE_AUTH_KEY',  'generate-unique-key');
define('LOGGED_IN_KEY',    'generate-unique-key');
define('NONCE_KEY',        'generate-unique-key');
define('AUTH_SALT',        'generate-unique-salt');
define('SECURE_AUTH_SALT', 'generate-unique-salt');
define('LOGGED_IN_SALT',   'generate-unique-salt');
define('NONCE_SALT',       'generate-unique-salt');
```

### 3. Database Security

```sql
-- Create dedicated database user for Eye-Book
CREATE USER 'eyebook_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX ON eyebook_db.* TO 'eyebook_user'@'localhost';
FLUSH PRIVILEGES;

-- Enable SSL for database connections
-- Ensure MySQL/MariaDB is configured with SSL certificates
```

### 4. File System Security

```bash
# Set secure file permissions
chmod 755 wp-content/
chmod 755 wp-content/plugins/
chmod 755 wp-content/plugins/eye-book/
chmod 644 wp-content/plugins/eye-book/*.php

# Protect sensitive directories
echo "deny from all" > wp-content/plugins/eye-book/includes/.htaccess
echo "deny from all" > wp-content/uploads/eye-book/.htaccess
```

---

## HIPAA Compliance Setup

### 1. Enable HIPAA Mode

```php
// Navigate to Eye-Book > Settings > Security
HIPAA Compliance Settings:
☑ Enable HIPAA Compliant Mode
☑ Enable Data Encryption
☑ Enable Audit Logging
☑ Enable Session Timeout (15 minutes)
☑ Require Strong Passwords
☑ Enable Two-Factor Authentication
```

### 2. Configure Audit Logging

```php
// Eye-Book > Settings > Audit
Audit Configuration:
- Log Level: All Activities
- Retention Period: 7 years (2555 days)
- Log Location: Database + File System
- Real-time Alerts: Enable for high-risk events
- Backup Audit Logs: Enable
```

### 3. Data Encryption Setup

```php
// Eye-Book > Settings > Encryption
Encryption Settings:
- Algorithm: AES-256-CBC
- Key Rotation: Every 90 days
- Backup Encryption: Enable
- PHI Fields: Auto-detect and encrypt
```

### 4. User Access Controls

```php
// Configure role-based access
Administrator Role:
- Full system access
- User management
- Audit log access
- System configuration

Provider Role:
- Patient records (full access)
- Appointment management
- Clinical documentation
- Limited system settings

Nurse Role:
- Patient records (limited)
- Appointment viewing
- Basic patient updates
- No administrative access

Receptionist Role:
- Appointment scheduling
- Basic patient information
- Payment processing
- No medical records access
```

### 5. Business Associate Agreements

```php
// Eye-Book > Settings > BAA
Configure BAA Status:
- Payment Processors: ☑ Stripe BAA ☑ Square BAA
- Email Providers: ☑ SendGrid BAA
- SMS Providers: ☑ Twilio BAA
- Cloud Storage: ☑ AWS BAA
- Backup Services: ☑ Backup Provider BAA
```

---

## Testing & Verification

### 1. Functionality Testing

```bash
# Run automated tests
cd wp-content/plugins/eye-book/
composer install
./vendor/bin/phpunit

# Test suites will verify:
# - Database functionality
# - Appointment booking
# - Patient management
# - Security features
# - HIPAA compliance
# - Performance benchmarks
```

### 2. Security Testing

```bash
# Security scan
wp plugin verify-checksums eye-book

# Manual security checks:
# ☑ SSL certificate valid
# ☑ Security headers present
# ☑ File permissions correct
# ☑ Database access restricted
# ☑ Admin access secured
```

### 3. HIPAA Compliance Verification

```php
// Eye-Book > Tools > Compliance Check
Compliance Status:
☑ PHI Encryption: Active
☑ Audit Logging: Functional
☑ Access Controls: Configured
☑ Data Backup: Automated
☑ User Authentication: Secure
☑ Session Management: Compliant
☑ Breach Detection: Active
```

### 4. Performance Testing

```bash
# Performance benchmarks
# - Page load time: < 2 seconds
# - Database queries: < 100ms
# - API response time: < 200ms
# - Memory usage: < 256MB
# - Concurrent users: 50+ supported
```

### 5. User Acceptance Testing

```
Test Scenarios:
1. Patient Registration
2. Appointment Booking
3. Provider Schedule Management
4. Payment Processing
5. Appointment Reminders
6. Patient Portal Access
7. Reporting Functions
8. Data Export/Import
```

---

## Performance Optimization

### 1. Caching Configuration

```php
// Install and configure caching plugin
// Recommended: WP Rocket or W3 Total Cache

// Eye-Book specific cache settings:
Cache Exclusions:
- /eye-book/booking/*
- /eye-book/patient-portal/*
- /eye-book/api/*

Database Caching:
- Provider schedules: 1 hour
- Appointment types: 24 hours
- Location data: 24 hours
```

### 2. Database Optimization

```sql
-- Optimize Eye-Book tables
OPTIMIZE TABLE wp_eye_book_appointments;
OPTIMIZE TABLE wp_eye_book_patients;
OPTIMIZE TABLE wp_eye_book_providers;
OPTIMIZE TABLE wp_eye_book_locations;

-- Add custom indexes for better performance
CREATE INDEX idx_appointment_date ON wp_eye_book_appointments(start_datetime);
CREATE INDEX idx_patient_name ON wp_eye_book_patients(last_name, first_name);
CREATE INDEX idx_provider_schedule ON wp_eye_book_provider_schedules(provider_id, date);
```

### 3. Server Configuration

```apache
# Apache optimization
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

### 4. PHP Configuration

```ini
; php.ini optimizations
memory_limit = 512M
max_execution_time = 300
max_input_vars = 3000
upload_max_filesize = 50M
post_max_size = 50M
max_input_time = 300

; OPcache settings
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=12
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

---

## Monitoring & Maintenance

### 1. System Monitoring

```php
// Eye-Book > Dashboard > System Status
Monitor:
- Server Performance
- Database Performance
- Security Status
- HIPAA Compliance Status
- Backup Status
- Update Status
```

### 2. Automated Monitoring Setup

```bash
# Set up monitoring alerts
# Server monitoring (CPU, RAM, Disk)
# Application monitoring (response time, errors)
# Security monitoring (login attempts, threats)
# Database monitoring (query performance, locks)
```

### 3. Regular Maintenance Tasks

```
Daily:
☐ Check system status
☐ Review security logs
☐ Verify backup completion
☐ Monitor performance metrics

Weekly:
☐ Update WordPress core/plugins
☐ Review audit logs
☐ Check security alerts
☐ Verify API integrations

Monthly:
☐ Full security audit
☐ Performance optimization
☐ Database maintenance
☐ Compliance review
☐ User access review

Quarterly:
☐ Disaster recovery test
☐ Security penetration test
☐ Staff training update
☐ Documentation review
```

---

## Backup & Recovery

### 1. Backup Configuration

```php
// Eye-Book > Settings > Backup
Backup Settings:
- Schedule: Daily at 2:00 AM
- Retention: 90 daily, 12 monthly
- Storage: Local + Cloud (AWS S3/Azure)
- Encryption: AES-256
- Verification: Automated integrity checks
```

### 2. Recovery Procedures

```bash
# Recovery Process
1. Assess damage/issue
2. Stop affected services
3. Restore from latest backup
4. Verify data integrity
5. Test functionality
6. Resume operations
7. Document incident
```

### 3. Disaster Recovery Plan

```
RTO (Recovery Time Objective): 4 hours
RPO (Recovery Point Objective): 24 hours

Recovery Steps:
1. Activate DR team
2. Assess situation
3. Implement recovery plan
4. Restore from backup
5. Verify system functionality
6. Resume operations
7. Post-incident review
```

---

## Troubleshooting

### Common Issues

#### 1. Plugin Activation Errors
```
Error: "Plugin could not be activated"
Solution:
- Check PHP version (8.0+ required)
- Verify file permissions
- Check error logs
- Ensure database permissions
```

#### 2. Database Connection Issues
```
Error: "Database connection error"
Solution:
- Verify database credentials
- Check database server status
- Verify user permissions
- Test connection manually
```

#### 3. SSL/Security Errors
```
Error: "SSL certificate error"
Solution:
- Verify SSL certificate validity
- Check certificate chain
- Update certificate if expired
- Configure proper redirects
```

#### 4. Memory/Performance Issues
```
Error: "Memory limit exceeded"
Solution:
- Increase PHP memory limit
- Optimize database queries
- Enable caching
- Check for plugin conflicts
```

### Support Resources

#### 1. Error Logging
```php
// Enable WordPress debug logging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Eye-Book specific logs
wp-content/uploads/eye-book/logs/
```

#### 2. System Information
```php
// Eye-Book > Tools > System Info
- WordPress version
- PHP version and extensions
- Database version
- Server configuration
- Plugin versions
- Theme information
```

---

## Support & Updates

### Getting Support

#### 1. Documentation
- Plugin documentation: Available in plugin folder
- HIPAA compliance guide: `HIPAA_COMPLIANCE_DOCUMENTATION.md`
- Development plan: `DEVELOPMENT_PLAN.md`

#### 2. Support Channels
```
Priority Support:
- Email: support@eye-book.com
- Phone: [Support Phone Number]
- Hours: Business hours (9 AM - 6 PM EST)

Community Support:
- Forum: community.eye-book.com
- Knowledge Base: kb.eye-book.com
- Video Tutorials: tutorials.eye-book.com
```

### Updates

#### 1. Automatic Updates
```php
// Enable automatic updates (recommended)
Eye-Book > Settings > Updates
☑ Enable automatic security updates
☑ Enable automatic minor updates
☐ Enable automatic major updates (manual approval)
```

#### 2. Manual Updates
```bash
# Before updating:
1. Create full backup
2. Test on staging environment
3. Review changelog
4. Schedule maintenance window

# Update process:
1. Download latest version
2. Deactivate plugin
3. Replace plugin files
4. Activate plugin
5. Verify functionality
```

### Version History
```
Version 1.0.0 (August 20, 2025)
- Initial release
- Full HIPAA compliance
- Enterprise features
- Payment integration
- Comprehensive security
- Audit trail system
- Backup and recovery
```

---

## Conclusion

Eye-Book is now successfully deployed and configured for your eye care practice. This enterprise-level appointment management system provides:

✅ **Complete HIPAA Compliance** - Protect patient data with enterprise-grade security  
✅ **Advanced Scheduling** - Streamline appointment management and reduce no-shows  
✅ **Payment Integration** - Process payments securely with multiple gateways  
✅ **Audit Trail** - Comprehensive logging for compliance and security  
✅ **Multi-Location Support** - Manage multiple clinics from one system  
✅ **Patient Portal** - Empower patients with self-service capabilities  
✅ **Reporting & Analytics** - Make data-driven decisions  
✅ **Backup & Recovery** - Protect your data with automated backups  

For ongoing support, updates, and training, please contact our support team or visit our documentation portal.

---

**Document Control:**
- **Version:** 1.0.0
- **Last Updated:** August 20, 2025
- **Next Review:** February 20, 2026
- **Approved By:** [System Administrator]
- **Classification:** Internal Use Only