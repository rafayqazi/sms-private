# Private School Management System (SMS)

A complete school ERP and administrative platform designed for private schools, academies, colleges, and educational institutes. It helps manage admissions, fees, attendance, exams, certificates, books, inventory, parent communication, staff data, and reporting from a single local-first web application.

Built and maintained by AR Software Solutions.

## Overview

The Private School Management System is a modern web-based ERP for school operations with a strong focus on offline-first deployment and private institutional control. It is designed to run efficiently in local environments such as XAMPP or similar PHP setups while remaining adaptable for cloud hosting when needed.

This system covers the complete school lifecycle:

- Student admission and records
- Fee collection and financial reporting
- Attendance tracking
- Examinations and results
- Certificates and official documents
- Parent portal and messaging
- Staff and payroll management
- Book bank and inventory tools
- School settings and backup/restore
- Integrated AI assistant for operational queries

## Why this project

- Built specifically for private academic institutions
- Works in fully offline/local-network environments
- Focuses on practical administrative workflows rather than generic CRUD screens
- Provides printable reports and certificates for real-world institutional use
- Keeps data local and under institutional control

## Key Modules

| Module | Description |
| --- | --- |
| Student Information System | Admission, student records, bulk enrollment, profiles, sponsorship records, alumni tracking |
| Fees & Billing | Tuition, arrears, fee collection, ledger reports, defaulters, receipts |
| Attendance Management | Daily student and teacher attendance, reports, printable registers |
| Exam & Results | Roll slips, marks entry, grading, result cards, tabulation sheets |
| Certificates | Transfer, character, school leaving, testimonial, and print-pad options |
| Parent Portal | Secure access for parents to view academic and fee-related data |
| Staff Management | Teacher profiles, payroll, employee attendance, teacher dashboard |
| Book Bank | Textbook issue/return tracking and library inventory |
| Inventory & Assets | Asset registration, categories, dead stock tracking |
| Expenses | School spending categories and ledger records |
| Settings & School Profile | Branding, class setup, institution information, security preferences |
| Backup & Restore | Encrypted backup creation and safe recovery |
| AI School Assistant | Natural-language support queries for school operations |

## Technology Stack

- Backend: PHP 7.4+ / 8.x
- Frontend: HTML5, JavaScript, CSS
- Styling: Tailwind CSS
- Charts: Chart.js
- Icons: Font Awesome
- Printing: CSS print media and print-specific templates
- Deployment model: Local/offline-first web app, compatible with XAMPP and similar environments

## Core Features

### 1. Student Lifecycle Management

- Individual and bulk admission workflows
- Student photos and document archives
- Missing file/document tracking
- Student profile views and dossier generation
- Academic promotion and repeater management
- Alumni archive and restoration tools
- CSV export and reporting support

### 2. Fee and Accounts Management

- Multi-head fee structures
- Fee collection with real-time balance updates
- Defaulter and arrears analysis
- Financial reports by date, class, and individual student
- Receipt printing and payment history
- Alumni fee settlement reports

### 3. Attendance Tracking

- Student and staff attendance marking
- Quick present/absent/leave actions
- Attendance summary per class and date range
- Printable attendance sheets

### 4. Examination and Assessment

- Roll number slips generation
- Exam hall attendance registers
- Teacher marking award lists
- Grade and rank calculation
- Result card printing and class-wise tabulation

### 5. Certificates and Official Printing

- School leaving certificate
- Character certificate
- Testimonial certificate
- Transfer certificate
- Full print version and letterhead-pad optimized mode

### 6. Parent and Teacher Access

- Parent login system
- Teacher dashboards and access control
- Messaging and notices
- Viewable academic and fee records

### 7. Book Bank and Inventory

- Manage book stock and issue records
- Bulk book assignment to classes
- Teacher allocation tracking
- Inventory and dead stock management

### 8. Security, Roles, and Data Protection

- Role-based access control
- Secure admin verification workflows
- Backup and restore management
- License and update verification system
- Controlled application reset process

### 9. AI School Assistant

The system includes an AI-style assistant widget that can respond to natural-language queries such as:

- Show today's attendance summary
- How many fee defaulters are in Class 8?
- List students admitted this month
- Show staff presence today

## System Architecture

This repository uses a classic server-rendered PHP layout:

- `pages/` contains the main screen modules and management interfaces
- `api/` contains backend handlers and data actions
- `includes/` contains shared logic, session management, DB access, and utilities
- `assets/` contains UI and static resources

This structure allows the application to remain self-contained and easy to deploy in a lightweight local environment without a heavy framework.

## Repository Structure

```text
sms-private/
├── api/                  # AJAX/API endpoints for actions and reporting
├── includes/             # shared PHP code, database logic, auth, headers/footers
├── pages/                # user-facing admin, parent, and teacher screens
├── assets/               # frontend static assets
├── README.md             # project overview and setup guide
├── DOCUMENTATION.md      # documentation and product details
├── composer.json         # Composer metadata
├── index.php             # application entry
├── login.php             # login page
├── install.php           # installation flow
├── maintenance.php       # maintenance page
├── logout.php            # logout handler
├── parent_logout.php     # parent logout handler
├── start_system.vbs      # Windows launcher helper
├── splash.hta            # splash screen helper
└── ...                   # other feature scripts and utilities
```

## Installation

### Prerequisites

1. Windows, Linux, or macOS
2. Apache/PHP environment such as XAMPP
3. A browser such as Chrome, Edge, or Firefox

### Setup Steps

1. Clone or download the project into your web root.

```bash
git clone https://github.com/rafayqazi/sms-private.git
```

Example local path:

```text
C:\xampp\htdocs\sms-private\
```

2. Start Apache in XAMPP.

3. Open the app in a browser:

```text
http://localhost/sms-private
```

4. If this is a fresh installation, open:

```text
http://localhost/sms-private/install.php
```

5. Complete setup and configure admin credentials.

## Default Admin Access

The project documentation states that the default admin login is:

```text
Username: admin
Password: configured during installation
```

It is recommended to change the password immediately after setup.

## Quick Start Workflows

### Admitting a student
1. Open Students > Admission
2. Complete demographic and contact details
3. Assign class and section
4. Add photo and document files
5. Save the record

### Collecting fees
1. Go to Fees > Collect Fees
2. Search the student
3. Confirm the due amount
4. Record payment
5. Print the receipt

### Generating exam slips
1. Open Examinations > Exam Slips
2. Select the class and exam schedule
3. Generate slips and print them

### Creating a backup
1. Open Settings > Backup & Restore
2. Create encrypted system backup
3. Store the archive securely

## Security Notes

- The app is intended for private institutional use.
- Data is designed to remain under local or controlled deployment ownership.
- Backup and restore features support secure recovery workflows.
- Role restrictions are built into the application for sensitive administrative functions.

## Developer and Ownership

Developed by AR Software Solutions.

Lead Architect & Developer: Abdul Rafay Qazi

- GitHub: https://github.com/rafayqazi
- LinkedIn: https://www.linkedin.com/in/abdulrafayqazi
- Email: abdulrafehqazi@gmail.com

## License

This project is distributed for private institutional use. All rights are reserved by AR Software Solutions. Unauthorized redistribution, removal of attribution, or reuse outside the agreed usage scope is prohibited.

## Support

For customized feature requests, school-specific branding, multi-branch deployment requirements, or technical assistance, contact the project author through the channels listed above.

---

If you want, I can also help you turn this into a more premium README with:
- a product-style landing page layout
- screenshots placeholders
- a changelog section
- architecture diagrams
- a contribution guide
- a clearer installation troubleshooting section

I can update the file in the repository again with that version as well.