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
A comprehensive web-based School Management System designed specifically for **Government Boys Primary School Ali Bux Jarwar** to manage student records, attendance, teacher information, and academic progression.

### Technology Stack
- **Backend**: PHP 7.4+
- **Database**: CSV-based file storage
- **Frontend**: HTML5, TailwindCSS 3.0
- **JavaScript**: Vanilla JS, Chart.js
- **Icons**: Font Awesome 6.4.0
- **Fonts**: Google Fonts (Inter)

### Key Features
- Student admission and profile management
- Annual student promotion system with Alumni tracking
- Attendance management with real-time statistics
- Teacher and parent management
- AI-powered chatbot assistant
- Interactive dashboard with analytics
- Document upload and management
- Print-friendly student profiles

---

## System Architecture

### Architecture Pattern
The system follows a **Model-View-Controller (MVC)** inspired pattern:

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
│  - attendance.php            │
└──────────┬──────────────────┘
           │
           ↓
┌─────────────────────────────┐
│   Database Class (Model)     │
│  - includes/db.php           │
│  - CRUD operations           │
│  - Business logic            │
└──────────┬──────────────────┘
           │
           ↓
┌─────────────────────────────┐
│   CSV Files (Data Layer)     │
│  - data/database.csv         │
│  - data/teachers.csv         │
│  - data/attendance.csv       │
└─────────────────────────────┘
```

### Request Flow

1. **User Request** → Browser sends HTTP request
2. **Session Check** → `auth_session.php` validates user session
3. **Controller** → Page-specific PHP file processes request
4. **Model** → `Database` class interacts with CSV files
5. **View** → HTML is rendered with Tailwind CSS
6. **Response** → Complete page sent to browser

---

## Database Schema

### Students Table (database.csv)

| Field | Type | Description |
|-------|------|-------------|
| `id` | Integer | Primary key, auto-increment |
| `gr_no` | String | General Register number (unique) |
| `student_name` | String | Full name of student |
| `father_name` | String | Father's full name |
| `gender` | Enum | Male/Female |
| `date_of_birth` | Date | YYYY-MM-DD format |
| `admission_date` | Date | Date of admission |
| `current_class` | String | Kachi/One/Two/Three/Four/Five |
| `age` | Integer | Calculated from DOB |
| `b_form_no` | String | B-Form number (format: xxxxx-xxxxxxx-x) |
| `father_cnic` | String | Father's CNIC (format: xxxxx-xxxxxxx-x) |
| `father_contact` | String | Phone number |
| `district` | String | District name |
| `taluka` | String | Taluka/Tehsil name |
| `school_name` | String | School name |
| `semis_code` | String | SEMIS code |
| `is_active` | Boolean | 1=Active, 0=Inactive |
| `created_at` | DateTime | Record creation timestamp |
| `updated_at` | DateTime | Last modification timestamp |
| `father_cnic_front` | String | Path to CNIC front image |
| `father_cnic_back` | String | Path to CNIC back image |
| `b_form_img` | String | Path to B-Form image |
| `profile_image` | String | Path to profile photo |
| `previous_school` | String | Previous school name (if transfer) |
| `slc_img` | String | School Leaving Certificate image |
| `student_status` | Enum | Active/Alumni |
| `is_repeater` | Boolean | 0=Normal, 1=Repeating class |

### Teachers Table (teachers.csv)

| Field | Type | Description |
|-------|------|-------------|
| `id` | Integer | Primary key |
| `name` | String | Teacher's full name |
| `father_name` | String | Father's name |
| `gender` | Enum | Male/Female |
| `cnic` | String | CNIC number |
| `dob` | Date | Date of birth |
| `age` | Integer | Calculated age |
| `contact` | String | Phone number |
| `email` | String | Email address |
| `address` | String | Residential address |
| `designation` | String | Job title |
| `department` | String | Department name |
| `posting` | String | Current posting location |
| `basic_scale` | String | BPS grade (e.g., BPS-14) |
| `retirement_date` | Date | Expected retirement date |
| `payment_type` | String | Bank Account/Mobile Banking |
| `payment_no` | String | Account/mobile number |
| `iban` | String | Bank IBAN |
| `profile_image` | String | Path to profile photo |

### Attendance Table (attendance.csv)

| Field | Type | Description |
|-------|------|-------------|
| `date` | Date | Attendance date |
| `class` | String | Class name |
| `student_id` | Integer | Foreign key to students |
| `status` | Char | P=Present, A=Absent, L=Leave |
| `created_at` | DateTime | Record timestamp |

---

## Features & Modules

### 1. Dashboard (`index.php`)

**Purpose**: Central hub showing school overview and statistics

**Features**:
- Total student count (excluding Alumni)
- Gender-based statistics (Male/Female)
- Recent admissions list
- Class-wise attendance chart (interactive)
- Quick navigation to all modules

**Interactivity**:
- Clicking "Total Students" → Redirects to full student list
- Clicking "Male"/"Female" → Filters students by gender
- Clicking chart bars → Filters attendance by class

### 2. Student Management

#### Student List (`students.php`)
- **Filters**: Class, Gender, Search (Name/GR No)
- **Sorting**: GR Number (ascending/descending)
- **Display**: Profile photos, badges for repeaters and alumni
- **Actions**: View, Edit, Delete

#### Student Form (`student_form.php`)
- **Fields**: All student information fields
- **Validation**: 
  - Unique GR Number
  - Unique B-Form Number
  - CNIC format validation
  - Required field checks
- **File Uploads**: 
  - Profile photo
  - Father's CNIC (front & back)
  - B-Form image
  - School Leaving Certificate (for transfers)
- **Dynamic Fields**: Previous school info (only for non-Kachi admissions)

#### Student Profile (`student_profile.php`)
- Complete student information display
- Document gallery
- Print-friendly format (A4 certificate style)
- Linked parent information

#### Student Promotion (`promote_students.php`)
- **Class Filter**: Select class to promote
- **Bulk Actions**: Process multiple students at once
- **Options**:
  - **Pass**: Promote to next class
  - **Fail**: Mark as repeater (stay in same class)
  - **Stay**: Remain in class without repeater flag
- **Special Handling**: Class Five → Alumni status
- **Confirmation**: Requires user confirmation before applying

#### Alumni (`alumni.php`)
- Display all graduated students
- Shows graduation year
- Completely separate from active student lists
- View-only access to profiles

### 3. Attendance System

#### Mark Attendance (`attendance.php`)
- **Class Selection**: Choose class to mark attendance
- **Student List**: All students in selected class
- **Quick Marking**: 
  - Present (P)
  - Absent (A)
  - Leave (L)
- **Bulk Actions**: Mark All Present/Absent
- **Auto-save**: Stores attendance records

#### View Attendance (`attendance_view.php`)
- **Date Filter**: Select specific date
- **Class Filter**: Filter by class
- **Report Generation**: 
  - Present count
  - Absent count
  - Leave count
  - Attendance percentage
- **Student Details**: Click to view individual profiles
- **Export Ready**: Formatted for printing

### 4. Teacher Management

#### Teacher Registration (`teacher_form.php`)
- Complete teacher information
- CNIC validation
- Retirement date calculation
- Payment details (bank/mobile)
- Profile photo upload

#### Teacher Profile (`teacher_profile.php`)
- Display all teacher information
- Edit functionality
- Profile photo display

### 5. Parent Management (`parents.php`)

- Link parents to multiple children
- Father's CNIC as unique identifier
- Display all children of a parent
- Click child name → Navigate to student profile

### 6. AI Chatbot

**Location**: Fixed bottom-right corner on all pages

**Features**:
- Natural language queries about school data
- Suggested questions:
  - "Check today's attendance"
  - "Total students count"
  - "List absent students"
  - "Recent admissions"
  - "Teacher list"
- Context-aware responses
- Access to all student/teacher/attendance data

**Integration**: `api/chat.php` with AI context from `includes/ai_context.php`

---

## API Documentation

### 1. Chat API (`api/chat.php`)

**Endpoint**: `POST /api/chat.php`

**Request Body**:
```json
{
  "message": "How many students are there?"
}
```

**Response**:
```json
{
  "response": "There are 81 active students currently enrolled."
}
```

### 2. Student Data API (`api/get_students.php`)

**Endpoint**: `GET /api/get_students.php?class={class}&gender={gender}`

**Parameters**:
- `class` (optional): Filter by class
- `gender` (optional): Filter by gender

**Response**: JSON array of student objects

### 3. Attendance Data API (`api/get_attendance_data.php`)

**Endpoint**: `GET /api/get_attendance_data.php?date={date}&class={class}`

**Response**: Attendance records in JSON format

### 4. Attendance Report API (`api/get_attendance_report.php`)

**Endpoint**: `GET /api/get_attendance_report.php?date={date}&class={class}`

**Response**:
```json
{
  "date": "2025-11-30",
  "class": "One",
  "present": 12,
  "absent": 2,
  "leave": 0,
  "total": 14,
  "percentage": 85.7
}
```

### 5. Parent Lookup API (`api/get_parent.php`)

**Endpoint**: `GET /api/get_parent.php?cnic={cnic}`

**Response**: Parent information with enrolled children

### 6. Student Promotion API (`api/promote_student.php`)

**Endpoint**: `POST /api/promote_student.php`

**Request Body**:
```json
{
  "id": 123,
  "action": "pass|fail|stay"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Student promoted successfully"
}
```

### 7. CNIC Check API (`api/check_teacher_cnic.php`)

**Endpoint**: `GET /api/check_teacher_cnic.php?cnic={cnic}&exclude_id={id}`

**Purpose**: Validate unique teacher CNIC during registration

---

## File Structure

```
school-management-system/
│
├── actions/
│   ├── delete_student.php         # Delete student record
│   ├── delete_teacher.php         # Delete teacher record
│   └── logout.php                 # User logout
│
├── api/
│   ├── chat.php                   # AI chatbot endpoint
│   ├── check_teacher_cnic.php     # CNIC validation
│   ├── get_attendance_data.php    # Attendance data
│   ├── get_attendance_report.php  # Attendance reports
│   ├── get_parent.php             # Parent lookup
│   ├── get_students.php           # Student data
│   └── promote_student.php        # Promotion processing
│
├── assets/
│   ├── css/
│   │   └── chat.css              # Chatbot styles
│   ├── js/
│   │   ├── chat.js               # Chatbot logic
│   │   └── main.js               # Common JavaScript
│   └── img/
│       └── logo.jpg              # School logo
│
├── data/
│   ├── database.csv              # Students database
│   ├── teachers.csv              # Teachers database
│   ├── attendance.csv            # Attendance records
│   └── *.xlsx                    # Original Excel data
│
├── includes/
│   ├── db.php                    # Database class
│   ├── auth_session.php          # Session management
│   ├── functions.php             # Helper functions
│   ├── header.php                # Common header
│   ├── footer.php                # Common footer
│   └── ai_context.php            # AI context builder
│
├── uploads/                      # User uploaded files
│   ├── *.png, *.jpg              # Profile photos
│   └── GR-{number}-*.png         # Document images
│
├── index.php                     # Dashboard
├── students.php                  # Student list
├── student_form.php             # Add/Edit student
├── student_profile.php          # Student details
├── promote_students.php         # Annual promotion
├── alumni.php                   # Alumni directory
├── attendance.php               # Mark attendance
├── attendance_view.php          # View attendance
├── parents.php                  # Parent management
├── teacher_form.php             # Add/Edit teacher
├── teacher_profile.php          # Teacher details
├── login.php                    # Login page
├── reset_app.php                # Reset application
├── .gitignore                   # Git ignore rules
└── README.md                    # Project README
```

---

## Installation Guide

### Prerequisites
- **Web Server**: Apache 2.4+ (XAMPP recommended)
- **PHP**: Version 7.4 or higher
- **Browser**: Modern browser (Chrome, Firefox, Edge)

### Step-by-Step Installation

1. **Install XAMPP**
   ```
   Download from: https://www.apachefriends.org/
   Install to: C:\xampp
   ```

2. **Clone Repository**
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/rafayqazi/SMS-GBPS-ALI-BUX-JARWAR.git
   ```

3. **Configure Permissions** (Windows)
   - Right-click on project folder
   - Properties → Security
   - Add write permissions for `data/` and `uploads/` folders

4. **Start Apache**
   - Open XAMPP Control Panel
   - Start Apache

5. **Access Application**
   ```
   http://localhost/SMS-GBPS-ALI-BUX-JARWAR
   ```

6. **Initialize Data** (Optional)
   - Visit: `http://localhost/SMS-GBPS-ALI-BUX-JARWAR/generate_mock_data.php`
   - This creates sample students and teachers

### Configuration

**Database Files**: Located in `data/` folder
- Automatically created on first run
- CSV format for easy backup and portability

**Upload Directory**: `uploads/`
- Ensure write permissions
- Stores profile photos and documents

---

## User Guide

### For Administrators

#### Adding Students
1. Navigate to **Students → Admission**
2. Fill in all required fields (marked with *)
3. Upload profile photo and documents
4. Click "Save Student"

#### Managing Attendance
1. Go to **Attendance → Mark Attendance**
2. Select class from dropdown
3. Mark each student as Present/Absent/Leave
4. Click "Save Attendance"

#### Promoting Students (Annual)
1. Navigate to **Students → Promote Students**
2. Select class (e.g., "One")
3. For each student, choose:
   - **Pass**: Student moves to next class
   - **Fail**: Student repeats, marked as repeater
   - **Stay**: Student stays without repeater mark
4. Click "Apply Promotions"
5. Confirm action

#### Viewing Alumni
1. Go to **Students → Alumni**
2. View all graduates
3. Click eye icon to view profile

### For Teachers/Users

#### Using AI Assistant
1. Click chat icon (bottom-right)
2. Type question or click suggested question
3. Get instant information about students, attendance, etc.

#### Printing Student Profiles
1. Go to student profile
2. Click Print icon or Ctrl+P
3. Profile formatted as A4 certificate

---

## Developer Guide

### Adding New Features

#### 1. Creating a New Page

```php
<?php
require_once 'includes/auth_session.php';
require_once 'includes/db.php';
$db = new Database();

// Your logic here

include 'includes/header.php';
?>

<!-- Your HTML here -->

<?php include 'includes/footer.php'; ?>
```

#### 2. Database Operations

```php
// Read all students
$students = $db->readData();

// Filter students
$filtered = $db->filterStudents([
    'class' => 'One',
    'gender' => 'Male'
]);

// Add student
$db->addStudent($studentData);

// Update student
$db->updateStudent($id, $updatedData);

// Delete student
$db->deleteStudent($id);
```

#### 3. Creating API Endpoints

```php
<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$db = new Database();
// Process request
$data = /* your data */;

echo json_encode($data);
```

### Code Standards

- **Indentation**: 4 spaces
- **Naming**: camelCase for variables, PascalCase for classes
- **Comments**: Document complex logic
- **Security**: Always sanitize user input with `htmlspecialchars()`

### Database Class Methods

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `readData()` | - | Array | Get all students |
| `writeData($data)` | Array | void | Write all students |
| `addStudent($data)` | Array | Boolean | Add new student |
| `updateStudent($id, $data)` | Integer, Array | Boolean | Update student |
| `getStudent($id)` | Integer | Array | Get single student |
| `deleteStudent($id)` | Integer | Boolean | Delete student |
| `filterStudents($filters)` | Array | Array | Filter students |
| `getStudentsByClass($class)` | String | Array | Get class students |
| `promoteStudent($id, $action)` | Integer, String | Boolean | Promote student |
| `getAttendance($date, $class)` | String, String | Array | Get attendance |
| `saveAttendance($date, $class, $data)` | String, String, Array | Boolean | Save attendance |
| `getAttendanceStats()` | - | Array | Get statistics |

---

## Security & Privacy

### Data Protection

1. **Sensitive Data Exclusion**
   - Student/teacher databases not committed to Git
   - Uploads folder excluded from version control
   - `.gitignore` configured appropriately

2. **Session Management**
   - `auth_session.php` validates all page access
   - Session timeout after inactivity
   - Logout functionality available

3. **Input Validation**
   - CNIC format validation
   - B-Form uniqueness check
   - GR Number uniqueness check
   - SQL injection prevention (not applicable for CSV)
   - XSS prevention via `htmlspecialchars()`

4. **File Upload Security**
   - File type validation (images only)
   - File size limits
   - Sanitized filenames
   - Stored outside web root (recommended)

### Privacy Considerations

- Student data should only be accessible to authorized school staff
- Regular backups recommended for data safety
- CNIC and personal information should be handled according to local data protection laws

### Backup Strategy

```php
// Automated backup (recommended to run daily)
$db->backupData();
// Creates timestamped backup in /backups/ folder
```

---

## Troubleshooting

### Common Issues

**Issue**: Students not showing
- **Solution**: Check if `student_status != 'Alumni'` filter is active

**Issue**: Attendance not saving
- **Solution**: Verify write permissions on `data/attendance.csv`

**Issue**: Images not uploading
- **Solution**: Check `uploads/` folder permissions

**Issue**: Login not working
- **Solution**: Verify session configuration in `php.ini`

### Performance Optimization

- CSV files efficient up to ~10,000 records
- For larger schools, consider MySQL migration
- Enable PHP OpCache for better performance
- Use CDN for TailwindCSS in production

---

## Future Enhancements

### Planned Features
- [ ] Fee management module
- [ ] Report card generation
- [ ] SMS notifications to parents
- [ ] Multi-language support (Urdu/English)
- [ ] Mobile app version
- [ ] MySQL database migration option
- [ ] Advanced analytics dashboard
- [ ] PDF export for reports
- [ ] Email integration
- [ ] Role-based access control

### Migration to MySQL

For schools with >5000 students, MySQL is recommended:
- Convert CSV to MySQL tables
- Update `db.php` to use PDO/MySQLi
- Add indexes on GR Number, CNIC fields
- Implement proper relationships

---

## Support & Contact

For issues, suggestions, or contributions:
- **Repository**: https://github.com/rafayqazi/SMS-GBPS-ALI-BUX-JARWAR
- **Developer**: Rafay Qazi

---

## License

This project is developed for Government Boys Primary School Ali Bux Jarwar.
Free to use and modify for educational institutions.

---

*Documentation Version: 1.0*
*Last Updated: November 30, 2025*
