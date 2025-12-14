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
- **Attendance Insights**: Doughnut chart for daily status, bar chart for class-wise attendance.
- **Smart Date Logic**: Displays "Attendance Unmarked" if no data exists for the current day.
- **Quick Actions**: Add Student, Add Teacher, Backup Data.

### 2. Student Management
- **Admission (`pages/student_form.php`)**: 
  - Comprehensive form with image preview.
  - Parents auto-fill based on Father's CNIC.
  - Drag-and-drop style image uploads.
- **ID Cards (`pages/print_id_card.php`)**:
  - Enter GR Number to generate a standard credit-card sized ID.
  - Includes Photo, Details, and SEMIS Code.
  - Print-ready CSS for exact dimensions.
- **Promotion (`pages/promote_students.php`)**:
  - Bulk promote students to the next class.
  - Logic to handle "Pass", "Fail" (Repeater), and "Double Promotion".
  - Auto-moves Class 5 students to **Alumni**.

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

### 4. Data Security & Backup
- **Backup (`api/backup_data.php`)**:
  - Downloads the entire `data/` directory and `uploads/` as a ZIP file.
  - **Security**: Requires Admin Password validation before download.
- **Restore**: Manual restoration via replacing the `data` folder (currently manual).

### 5. Attendance
- **Daily Marking (`pages/attendance.php`)**:
  - Mark Present/Absent/Leave for whole class.
  - Quick "Mark All Present" button.
- **Reports**: View monthly and daily reports.

---

## API Documentation

### 1. Get Parent Info
- **Endpoint**: `GET /api/get_parent.php?cnic=XXXXX`
- **Use**: Autofills father's name and contact if the parent already exists in the system.

### 2. Promote Student
- **Endpoint**: `POST /api/promote_student.php`
- **Payload**: `{ student_ids: [], action: 'promote' }`
- **Logic**: Updates `current_class` and `is_repeater` flags.

### 3. Backup Data
- **Endpoint**: `POST /api/backup_data.php`
- **Payload**: `{ password: 'admin_password' }`
- **Response**: ZIP file download or 403 Forbidden.

---

## Installation Guide

1. **Setup Server**: Install XAMPP or any PHP environment.
2. **Clone Project**: Place files in `htdocs` or `www`.
3. **Permissions**: Ensure `data/` and `uploads/` folders are writable (777 or Read/Write).
4. **Access**: Navigate to `http://localhost/school-management-system`.

---

## Developer Guide

### Adding a New Subject
To add a new subject to the Result system:
1. Update `pages/results.php` HTML form.
2. Update `includes/db.php` -> `addResult()` method to handle the new field.
3. Update `pages/print_result.php` to display the new column.

### Customizing ID Card
Edit `pages/generate_id_card.php`:
- **CSS `@page`**: Controls the PDF page size (currently ID-1).
- **Layout**: Tailwind classes control the design. Update HTML structure here.

---

## Security & Privacy
- **Authentication**: Session-based login for Admin and Teachers.
- **Passwords**: Hashed storage (if implemented) or hardcoded for specific admin access.
- **Access Control**: Critical features (Backup, Reset) are protected by secondary password verification.

---
*Last Updated: December 14, 2025*
