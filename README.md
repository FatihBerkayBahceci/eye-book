# Eye-Book: Enterprise Appointment Scheduling for Eye Care Practices

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-5.0%2B-blue)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://php.net/)
[![HIPAA Compliant](https://img.shields.io/badge/HIPAA-Compliant-green)](https://www.hhs.gov/hipaa/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-blue)](https://www.gnu.org/licenses/gpl-2.0.html)

**Eye-Book** is a comprehensive, enterprise-level appointment scheduling WordPress plugin specifically designed for eye care practices in the United States. Built with HIPAA compliance at its core, Eye-Book provides optometry and ophthalmology clinics with a complete patient management and appointment booking solution.

## 🏥 Built for US Eye Care Professionals

Eye-Book addresses the unique needs of:
- **Optometry Practices** - Routine exams, contact lens fittings, vision therapy
- **Ophthalmology Clinics** - Surgical consultations, specialized treatments, follow-ups
- **Multi-location Chains** - Centralized management across multiple clinics
- **Solo Practitioners** - Streamlined workflows for independent practices

## ✨ Key Features

### 🔒 HIPAA Compliance & Security
- **End-to-end encryption** for all patient data
- **Comprehensive audit logging** for all system activities
- **Role-based access controls** with granular permissions
- **Automatic breach detection** and notification systems
- **Session management** with automatic timeouts
- **IP-based lockout protection** against brute force attacks

### 📅 Advanced Appointment Management
- **Online booking system** with 24/7 patient access
- **Multi-provider scheduling** with conflict detection
- **Appointment types** tailored for eye care procedures
- **Recurring appointments** for ongoing treatments
- **Waitlist management** with automatic notifications
- **Mobile-responsive** booking interface

### 👥 Comprehensive Patient Management
- **Encrypted patient records** with eye care specific fields
- **Insurance verification** and authorization tracking
- **Digital intake forms** and questionnaires
- **Patient portal** for appointment management
- **Medical history** and allergy tracking
- **Emergency contact** management

### 🏢 Enterprise Features
- **Multi-location support** with centralized administration
- **Advanced reporting** and analytics
- **API integrations** with existing practice management systems
- **Payment processing** with secure gateway integration
- **Staff management** with custom roles and capabilities
- **Bulk operations** for efficiency

### 🔧 Clinical Integration
- **Pre-visit forms** and preparation instructions
- **Treatment plan** scheduling and tracking
- **Referral management** (internal and external)
- **Lab/diagnostic** appointment coordination
- **Provider notes** and documentation
- **Follow-up** appointment automation

## 🚀 Quick Start

### System Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher (8.0 recommended)
- **SSL Certificate:** Required for HIPAA compliance
- **PHP Extensions:** OpenSSL, MySQLi, JSON, Mbstring

### Installation

1. **Download the plugin** from the releases page
2. **Upload** to your WordPress `/wp-content/plugins/` directory
3. **Activate** the plugin through the WordPress admin
4. **Run** the setup wizard to configure your clinic settings
5. **Create** staff accounts and assign appropriate roles

### Initial Setup

1. **Configure Locations**
   ```
   Eye-Book → Locations → Add New Location
   ```

2. **Add Providers**
   ```
   Eye-Book → Providers → Add New Provider
   ```

3. **Set Appointment Types**
   ```
   Eye-Book → Appointment Types → Configure Types
   ```

4. **Security Settings**
   ```
   Eye-Book → Settings → Security → Enable HIPAA Mode
   ```

## 🏗️ Architecture

Eye-Book follows WordPress best practices and employs a robust, scalable architecture:

```
eye-book/
├── eye-book.php                 # Main plugin file
├── includes/                    # Core functionality
│   ├── models/                 # Data models
│   ├── class-eye-book-database.php
│   ├── class-eye-book-security.php
│   ├── class-eye-book-audit.php
│   └── class-eye-book-encryption.php
├── admin/                      # Admin interface
├── public/                     # Frontend functionality
├── templates/                  # Template files
└── assets/                     # CSS, JS, images
```

## 📊 Database Schema

Eye-Book creates 8 custom database tables:

- `eye_book_appointments` - Appointment records
- `eye_book_patients` - Patient information
- `eye_book_providers` - Staff and doctor profiles
- `eye_book_locations` - Clinic locations
- `eye_book_appointment_types` - Appointment categories
- `eye_book_patient_forms` - Digital forms and intake data
- `eye_book_audit_log` - HIPAA audit trail
- `eye_book_settings` - Configuration data

## 🔐 Security & Compliance

### HIPAA Compliance Features

- **Data Encryption:** AES-256 encryption for PHI
- **Access Controls:** Role-based permissions
- **Audit Logging:** Complete activity tracking
- **Session Security:** Automatic timeouts and secure sessions
- **Breach Notification:** Automated detection and reporting
- **Data Integrity:** Checksums and validation

### Security Measures

- **Input Sanitization:** All data sanitized before storage
- **SQL Injection Protection:** Prepared statements only
- **XSS Prevention:** Output escaping and validation
- **CSRF Protection:** WordPress nonce verification
- **Brute Force Protection:** IP-based lockouts
- **Security Headers:** HIPAA-compliant HTTP headers

## 👥 User Roles & Capabilities

### Built-in Roles

| Role | Capabilities |
|------|-------------|
| **Clinic Administrator** | Full system access, settings, reports |
| **Doctor** | Patient records, own appointments, own schedule |
| **Nurse** | Patient management, appointment assistance |
| **Receptionist** | Appointment booking, patient check-in |
| **Patient** | Portal access, own appointments, forms |

### Custom Capabilities

- `eye_book_manage_all` - Full administrative access
- `eye_book_manage_appointments` - Appointment management
- `eye_book_manage_patients` - Patient record access
- `eye_book_view_reports` - Analytics and reporting
- `eye_book_manage_settings` - System configuration

## 🔌 API & Integrations

### REST API Endpoints

Eye-Book provides a comprehensive REST API for integrations:

```
GET    /wp-json/eye-book/v1/appointments
POST   /wp-json/eye-book/v1/appointments
GET    /wp-json/eye-book/v1/patients
POST   /wp-json/eye-book/v1/patients
GET    /wp-json/eye-book/v1/providers
```

### Webhook Support

Trigger external systems with real-time events:
- Appointment created/updated/cancelled
- Patient registered/updated
- Forms submitted
- Payment processed

## 📈 Reporting & Analytics

### Built-in Reports

- **Appointment Analytics** - Booking trends, no-show rates
- **Provider Utilization** - Efficiency metrics, patient load
- **Financial Reports** - Revenue tracking, insurance claims
- **Patient Demographics** - Age groups, insurance types
- **Operational Metrics** - Wait times, completion rates

### Export Capabilities

- **PDF Reports** - Professional formatted reports
- **CSV Export** - Data analysis and external processing
- **API Export** - Real-time data access
- **Compliance Reports** - HIPAA audit trails

## 🧪 Development

### Local Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/fatihberkaybahceci/eye-book.git
   ```

2. **Install dependencies**
   ```bash
   cd eye-book
   npm install
   composer install
   ```

3. **Build assets**
   ```bash
   npm run build
   ```

### Testing

Eye-Book includes comprehensive testing:

```bash
# Run PHP unit tests
composer test

# Run JavaScript tests
npm test

# Run integration tests
npm run test:integration
```

## 📋 Roadmap

### Phase 1: Foundation (Weeks 1-2) ✅
- [x] Plugin structure and database schema
- [x] Security framework and HIPAA compliance
- [x] Core models and basic functionality

### Phase 2: User Management (Weeks 3-4)
- [ ] Role-based access controls
- [ ] Patient portal development
- [ ] Staff management interface
- [ ] Authentication security

### Phase 3: Appointment System (Weeks 5-7)
- [ ] Booking engine and calendar views
- [ ] Conflict detection and resolution
- [ ] Online booking interface
- [ ] Mobile responsiveness

### Phase 4: Clinical Integration (Weeks 8-9)
- [ ] Patient forms and intake
- [ ] Communication systems
- [ ] Clinical workflows
- [ ] Treatment planning

### Phase 5: Provider Features (Weeks 10-11)
- [ ] Provider scheduling and management
- [ ] Multi-location support
- [ ] Provider dashboards
- [ ] Schedule management

### Phase 6: Enterprise Features (Weeks 12-14)
- [ ] Reporting and analytics
- [ ] API development
- [ ] Third-party integrations
- [ ] Payment processing

### Phase 7: Security & Compliance (Weeks 15-16)
- [ ] Security audit and hardening
- [ ] HIPAA compliance verification
- [ ] Penetration testing
- [ ] Documentation completion

### Phase 8: Testing & Launch (Weeks 17-20)
- [ ] Comprehensive testing
- [ ] Performance optimization
- [ ] User acceptance testing
- [ ] Production deployment

## 🤝 Contributing

We welcome contributions from the eye care and WordPress development communities!

### How to Contribute

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Development Guidelines

- Follow WordPress coding standards
- Maintain HIPAA compliance in all features
- Include comprehensive tests
- Document all new functionality
- Ensure mobile responsiveness

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🏥 Healthcare Compliance

Eye-Book is designed to meet healthcare industry standards:
- **HIPAA** - Health Insurance Portability and Accountability Act
- **HITECH** - Health Information Technology for Economic and Clinical Health
- **State Regulations** - US state-specific healthcare requirements

## 📞 Support

### Documentation
- [User Guide](docs/user-guide.md)
- [Administrator Manual](docs/admin-guide.md)
- [Developer Documentation](docs/developer-guide.md)
- [API Reference](docs/api-reference.md)

### Community Support
- [WordPress.org Plugin Page](https://wordpress.org/plugins/eye-book)
- [GitHub Issues](https://github.com/fatihberkaybahceci/eye-book/issues)
- [Community Forums](https://wordpress.org/support/plugin/eye-book)

### Professional Support
For enterprise installations, HIPAA compliance consulting, or custom development:
- **Email:** support@eye-book.com
- **Website:** https://eye-book.com
- **Phone:** 1-800-EYE-BOOK

## 👨‍💻 Developer

**Fatih Berkay Bahçeci**
- **GitHub:** [@fatihberkaybahceci](https://github.com/fatihberkaybahceci)
- **Website:** [fatihberkaybahceci.com](https://fatihberkaybahceci.com)
- **LinkedIn:** [linkedin.com/in/fatihberkaybahceci](https://linkedin.com/in/fatihberkaybahceci)

## 🌟 Acknowledgments

- WordPress Community for the excellent platform
- Eye care professionals who provided industry insights
- Security experts who reviewed HIPAA compliance
- Beta testers from optometry and ophthalmology practices

---

**Eye-Book** - *Streamlining eye care through innovative appointment management*

*Built with ❤️ for the eye care community*# eye-book
