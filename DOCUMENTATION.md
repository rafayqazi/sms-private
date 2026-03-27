# School Management System - Technical Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Database Schema](#database-schema)
4. [Features & Modules](#features--modules)
5. [API Documentation](#api-documentation)
6. [File Structure](#file-structure)
7. [Installation Guide](#installation-guide)
8. [User Guide](#user-guide)
9. [Developer Guide](#developer-guide)
10. [Security & Privacy](#security--privacy)

---

## Project Overview

### Purpose
A comprehensive web-based School Management System designed specifically for **Government Boys Primary School Ali Bux Jarwar** to manage student records, attendance, examinations, ID cards, and academic progression.

### Technology Stack
- **Backend**: PHP 7.4+
- **Database**: CSV-based file storage (NoSQL approach)
- **Frontend**: HTML5, TailwindCSS 3.4 (CDN)
- **JavaScript**: Vanilla JS, Chart.js
- **Icons**: Font Awesome 6.4.0
- **Fonts**: Google Fonts (Roboto)

### Key Features
- **Student Management**: Admission, Profiles, Promotion, Alumni Tracking.
- **Examinations**: Mark Entry, Result Cards, Tabulation Sheets, Exam Slip Generation.
- **ID Cards**: Auto-generate printable Student ID Cards with barcodes/QR.
- **Attendance**: Daily marking with visual insights and dashboards.
- **Dashboard**: Real-time analytics, gender-wise counts, and attendance status.
- **Data Security**: Backup and Restore functionality, Password-protected critical actions.

---

## System Architecture

### Architecture Pattern
The system follows a bespoke **Model-View-Controller (MVC)** pattern tailored for flat-file storage:

```
┌─────────────┐
│   Browser   │
│  (View)     │
└──────┬──────┘
       │
       ↓
┌─────────────────────────────┐
│   PHP Pages (Controllers)    │
│  - index.php                 │
│  - students.php              │
│  - pages/generate_id_card.php│
└──────────┬──────────────────┘
           │
           ↓
┌─────────────────────────────┐
│   Database Class (Model)     │
│  - includes/db.php           │
│  - CRUD operations           │
│  - Data validation           │
└──────────┬──────────────────┘
           │
           ↓
┌─────────────────────────────┐
│   CSV Files (Data Layer)     │
│  - data/database.csv         │
│  - data/results.csv          │
│  - data/attendance.csv       │
└─────────────────────────────┘
```

---

## Database Schema

### Students Table (`database.csv`)
| Field | Description |
|-------|-------------|
| `id` | Unique Primary Key |
| `gr_no` | General Register Number (Unique) |
| `student_name` | Full Name |
| `father_name` | Father's Name |
| `gender` | Male/Female |
| `date_of_birth` | YYYY-MM-DD |
| `current_class` | Kachi, One, Two, Three, Four, Five |
| `father_cnic` | 13-digit CNIC (unique parent identifier) |
| `father_contact` | Mobile Number |
| `profile_image` | Path to student photo |
| `student_status` | 'Active' or 'Alumni' |
| `semis_code` | School SEMIS Code (Fixed/Configurable) |

### Results Table (`results.csv`)
| Field | Description |
|-------|-------------|
| `id` | Record ID |
| `student_id` | Foreign Key to Students |
| `class` | Class at time of exam |
| `exam_type` | Mid Term / Annual |
| `year` | Exam Year (e.g., 2025) |
| `english`, `math`, `urdu`, `sindhi`... | Subject Marks |
| `total_obtained` | Sum of marks |
| `grade` | A1, A, B, C, D, F |
| `other_subjects` | JSON string for extra subjects |

---

## Features & Modules

### 1. Dashboard (`index.php`)
**Central Hub**:
- **Attendance Insights**: Doughnut chart for today's student status.
- **Top Performers**: Displays the top 3 students based on exam results.
- **Birthdays**: Smart card showing today's birthdays and upcoming ones (next 15 days).
- **Inventory Alerts**: Real-time alerts for low-stock items.
- **Quick Actions**: One-click access to most common tasks like Add Student, Mark Attendance, Backup.

### 2. Student Management
- **Admission (`pages/student_form.php`)**: 
  - Comprehensive form with image preview.
  - Parents auto-fill based on Father's CNIC.
  - Drag-and-drop style image uploads.
- **Bulk Admission (`pages/bulk_admission.php`)**: Import multiple student records via CSV.
- **ID Cards (`pages/print_id_card.php`)**:
  - Enter GR Number to generate a standard credit-card sized ID.
  - Includes Photo, Details, and SEMIS Code.
  - Print-ready CSS for exact dimensions.
- **Promotion (`pages/promote_students.php`)**:
  - Bulk promote students to the next class.
  - Logic to handle "Pass", "Fail" (Repeater), and "Double Promotion".
  - Auto-moves Class 5 students to **Alumni**.
- **Alumni Network (`pages/alumni.php`)**:
  - Track graduated students.
  - **Bulk Restore**: Move alumni back to active student status if needed.

### 3. Examination System
- **Exam Slips (`pages/exam_slips.php`)**:
  - Generate printable date sheets for specific classes.
  - **Auto-fill Time**: Setting time for one subject auto-fills others.
  - **Smart Date**: Auto-increments dates for subsequent subjects.
- **Results Entry (`pages/results.php`)**:
  - Enter marks for core and optional subjects.
  - Auto-calculation of Total, Percentage, and Grade.
- **Print Marksheets (`pages/print_all_results.php`)**:
  - Bulk print result cards for an entire class.
  - formatted A4 layout (2 per page or 1 per page).

### 4. Fee Management (`pages/fees.php`)
- **Fee Structure**: Set Admission, Monthly, and Exam fees per class.
- **Collection**: Record payments with auto-generated receipts (`pages/print_receipt.php`).
- **Defaulters Tracker**: Real-time list of students with pending dues for the current month.
- **History**: Detailed collection logs with filtering by month and student.

### 5. Book Bank (`pages/book_bank.php`)
- **Textbook Inventory**: Track government-supplied free textbooks.
- **Issuance & Returns**: Manage book distribution to students and teachers.
- **Bulk Issue**: One-click issuance for an entire class.
- **Damaged Stock**: Register for books that are no longer usable.

### 6. Inventory & Assets (`pages/inventory.php`)
- **Stock Tracking**: Categorized list of school assets (Furniture, Electronics, etc.).
- **Dead Stock Register**: Dedicated module for disposing of broken or obsolete items with reasons and remarks.
- **Categories Management**: Organize items for better auditing.



### 8. Communication & Support
- **Messaging (`pages/messages.php`)**: Internal chat system between Teachers (Editors) and the Admin.
- **Licensing (`pages/license.php`)**: Legal ownership and developer information.
- **Update System**: Automated check for software updates and one-click installation.

---

## API Documentation

### 1. Student & Parent Data
- `GET /api/get_parent.php?cnic=XXXXX`: Autofills parent info.
- `GET /api/get_students.php`: Search and retrieve student records.

### 2. Fees & Collections
- `POST /api/collect_fee.php`: Process fee payment.
- `GET /api/get_fee_status.php?gr_no=XXX`: Check individual dues.
- `GET /api/get_defaulters.php?month=YYYY-MM`: Listing pending payments.

### 3. System & Admin
- `POST /api/backup_data.php`: Manual backup generation.
- `GET /api/backup_data_auto.php`: Cron-ready automated backup script.
- `POST /api/verify_admin_password.php`: Secure gate for critical actions.

---

## Installation Guide

1. **Setup Server**: Install XAMPP or any PHP environment (PHP 7.4+ recommended).
2. **Clone Project**: Place files in `htdocs` or `www`.
3. **Permissions**: Ensure `data/` and `uploads/` folders are writable (777 or Read/Write).
4. **Access**: Navigate to `http://localhost/sms-aqsa`.

---

## Developer Guide

### Role-Based Access Implementation
Use `canAccessPage($filename)` defined in `includes/auth_session.php` to protect pages.
Editors' access is governed by the `assigned_classes` array in their session.

### Adding a New Module
1. Create the UI in `pages/`.
2. Implement backend logic in `api/` or `includes/db.php`.


---

## Security & Privacy
- **Authentication**: Session-based login for Admin and Teachers.
- **Data Protection**: Critical features (Backup, Reset) are protected by secondary password verification.
- **CSRF Protection**: All POST forms include CSRF tokens for security.

---
*Last Updated: February 27, 2026*
