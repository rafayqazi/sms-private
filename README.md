# 🏫 Private School Management System (SMS)
### Powered by AR Software Solutions
**Lead Architect & Developer:** Abdul Rafay Qazi  
**Version:** 2.5 (Enterprise Edition)  
**Target Platform:** Private Schools, Academies, Colleges & Educational Institutes  

---

## 📌 Executive Overview

The **Private School Management System (SMS)** is an all-in-one digital ERP platform designed specifically for private schools and modern educational academies. Built by **AR Software Solutions**, this system completely replaces manual paper registers, physical ledgers, and fragmented spreadsheets with a unified, lightning-fast digital interface.

From student admissions and monthly fee billing to examination roll slips, certificates, library book banking, staff payroll, and a dedicated parent portal—every single administrative workflow is automated with precision.

> [!NOTE]
> Designed for **100% offline capability** (local network / intranet via XAMPP/PHP) or online cloud deployment. Your school data remains completely secure and under your private control.

---

## 🌟 Key Modules & Core Features

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       AR SOFTWARE SOLUTIONS - SMS ERP                       │
├────────────────────┬────────────────────┬───────────────────────────────────┤
│ 👨‍🎓 Students & Bio  │ 💰 Fees & Billing  │ 📝 Exams & Results                │
│ 📅 Attendance Hub  │ 👨‍🏫 Staff & Payroll│ 📜 Certificates (Pad & Full)      │
│ 📚 Book Bank / Lib │ 📦 Inventory/Assets│ 👪 Parent Portal & Messaging      │
│ 🔐 RBAC & Security │ 💾 Encrypted Backup│ 🤖 AI School Assistant            │
└────────────────────┴────────────────────┴───────────────────────────────────┘
```

---

### 👨‍🎓 1. Student Information System (SIS) & Lifecycle

* **Standard & Bulk Admission (`bulk_admission.php`):**
  * Individual admission with complete demographic data, contact info, emergency contacts, and past academic records.
  * Rapid **Bulk Admission** mode to enroll entire batches or transfer students in minutes.
  * Automated **General Register (GR) Number** allocation ensuring unique student identification.
* **Digital Student Dossier & Document Repository:**
  * Upload and archive student photos, B-Form scans, Father CNIC copies, and previous School Leaving Certificates (SLC).
  * Auto image optimization and secure local file storage.
* **Missing Documents Tracker (`missing_files.php`):**
  * Instant filter and audit tool to identify students with missing B-Forms, CNIC copies, or photos.
* **Dynamic Student Profile & Print Dossier:**
  * One-click printable student profile card for institutional records or parent meetings.
* **Academic Promotion & Repeater Tracking (`promote_students.php`):**
  * Year-end batch promotion utility (Pass / Fail / Retain).
  * Automatic repeater flagging with distinct badges and history tracking.
* **Alumni Network Management (`alumni.php`):**
  * Dedicated repository for graduated students.
  * Preserves completion year, final class, and academic history.
  * One-click or bulk restoration of alumni back to active classes if needed.
* **Data Portability:**
  * One-click CSV export of student databases for departmental or administrative submissions.

---

### 🪪 2. Student ID Card Generator (`generate_id_card.php`)

* **Automated Batch & Single ID Card Creation:**
  * Generates high-resolution, print-ready student identity cards.
  * Includes student photo, dynamic barcode / QR code, GR number, blood group, class, section, emergency contact, and authorized signature.
* **Branded Templates:**
  * Automatically applies institutional header, logo, and custom color themes from school settings.

---

### 💰 3. Comprehensive Fees & Accounts Management (`fees.php`)

* **Flexible Fee Structure Configuration:**
  * Set baseline tuition fees per class or customize individual fee structures per student.
  * Manage multiple fee heads: Monthly Tuition, Admission Fees, Examination Fees, Sports/Lab charges, and Miscellaneous.
* **Instant Fee Collection & Challan Printing (`print_receipt.php`):**
  * Single-click monthly fee collection with automatic balance / arrears calculation.
  * Thermal and standard paper receipt generation with student & school copy.
* **Arrears & Defaulters Management:**
  * Real-time defaulters directory with filtered lists by class and pending duration.
  * Automated arrears accumulation across billing cycles.
  * Debt clearance and fee concession adjustments (`clear_debt.php`).
* **Financial Ledgers & Reports:**
  * Date-range fee collection summaries, class-wise payment status, and individual fee ledgers (`print_fee_history.php`).
  * Dedicated Alumni Fee Clearing and settlement reports (`print_alumni_fees_report.php`).
  * Live revenue analytics and collection stat counters on dashboard.

---

### 📅 4. Student & Staff Attendance Engine

* **Smart Daily Attendance (`attendance.php`):**
  * Take daily student attendance in seconds with quick presets (**Mark All Present**, **Mark All Absent**, **Leave**).
  * Color-coded statuses: 🟢 Present, 🔴 Absent, 🟡 Leave.
* **Attendance Analytics & Drilldown (`attendance_view.php`):**
  * Calendar-based and date-range attendance logs.
  * Class-wise daily and monthly percentage metrics.
  * Printable Attendance Registers and Official Sheets (`print_online_attendance.php`).
* **Staff & Teacher Attendance (`teacher_attendance.php`):**
  * Dedicated daily attendance tracking for academic and administrative staff.
  * Staff-wise attendance logs, monthly duty percentages, and leave management.

---

### 📝 5. Examination, Grading & Assessment Hub

* **Examination Roll Number Slips (`exam_slips.php`, `print_slips.php`):**
  * Auto-generates formal exam admission slips with student photos, roll numbers, exam dates, subject schedules, and examination rules.
* **Exam Hall Attendance Registers (`exam_attendance.php`):**
  * Generates printable signature sheets for exam superintendents and invigilators.
* **Teacher Marking Award Lists (`award_list.php`, `marking_award_list.php`):**
  * Print blank evaluation award lists for teachers to enter marks manually.
  * Print finalized marking sheets for institutional archives.
* **Mark Entry & Tabulation (`results.php`):**
  * Subject-wise marks recording with automatic calculation of total marks, percentages, grades, and class ranks/positions.
* **Result Cards & Tabulation Sheets (`result_card.php`, `print_all_results.php`):**
  * Beautiful student result cards with grade scales and teacher remarks.
  * Full-class master tabulation sheets for annual result gazettes.

---

### 📜 6. Official Certificates & Letterhead Pad Printing (`certificates.php`)

* **Complete Certificate Suite:**
  * 🎓 **School Leaving Certificate (SLC)**
  * 📜 **Character Certificate**
  * 🏅 **Testimonial / Achievement Certificate**
  * 🔄 **Student Transfer Certificate**
* **Exclusive Dual Printing Technology:**
  * **Standard Full Print:** Generates complete ornate certificates including ornate borders, school headers, watermarks, and verification signatures.
  * **Pre-Printed Letterhead Pad Mode (`print_*_pad.php`):** Suppresses background graphics and headers so you can feed pre-printed school stationery/letterhead pads directly into the printer with perfect text alignment!

---

### 👨‍🏫 7. Staff & Teacher Management & Payroll

* **Comprehensive Teacher Profiles (`teacher_profile.php`):**
  * Detailed records: CNIC, qualifications, date of joining, designation, subject expertise, and bank account info.
* **Teacher Self-Service Portal (`teacher_dashboard.php`):**
  * Dedicated teacher login to view assigned classes, enter grades, and check attendance.
* **Staff Payroll & Salary Disbursal (`pay_salary.php`):**
  * Track monthly salary disbursements, advances, deductions, and payment records.

---

### 📚 8. Book Bank & Library Management (`book_bank.php`)

* **Book Stock Inventory:**
  * Catalog textbooks, reference books, and instructional materials with quantity and status tracking.
* **Student Book Distribution & Bulk Issue (`book_bank_bulk.php`):**
  * Issue complete textbook sets to entire classes with a single click.
  * Track returns, damages, and pending books.
* **Teacher Allocations (`book_bank_teacher_actions.php`):**
  * Record teaching curriculum sets and teacher reference copies.
* **Audit & Inventory Reports (`book_bank_report.php`):**
  * Real-time stock audit showing available, issued, and depleted book titles.

---

### 📦 9. Inventory, Fixed Assets & Dead Stock Management

* **School Assets Registry (`inventory.php`):**
  * Track furniture, IT hardware, laboratory apparatus, sports gear, and office equipment.
* **Category Management (`categories.php`):**
  * Organize assets by department, room, and category.
* **Dead Stock / Damaged Goods Registry (`dead_stock.php`):**
  * Formal tracking of broken, expired, or written-off school property for annual audits.

---

### 💸 10. School Expense Tracking (`expenses.php`)

* **Daily Expense Ledger:**
  * Log school expenditures (utilities, stationery, repairs, refreshments, maintenance).
* **Expense Categories (`expense_categories.php`):**
  * Categorize expenses for clean financial statements and monthly expenditure auditing.

---

### 👪 11. Parent Portal & Multi-Channel Communication

* **Parent Portal (`parent_portal.php`):**
  * Parents log in securely using their Father's CNIC and account credentials.
  * View real-time academic records, monthly fee status, attendance history, and report cards for all enrolled siblings in one dashboard.
* **Communication & Notice Board (`messages.php`):**
  * Broadcast administrative notices, fee reminders, exam circulars, and holiday alerts.
  * Direct bidirectional communication between parents and school administration.
* **Support Ticket Resolution (`resolve_ticket.php`):**
  * Manage parent inquiries, grievances, and support requests.

---

### ⚙️ 12. Academic Structure & Institutional Customization

* **Dynamic Class & Section Manager (`manage_classes.php`):**
  * Add, rename, remove, and sort custom classes (Playgroup, Nursery, KG, Class 1-10, O-Levels, etc.).
  * Multi-section support per class.
* **School Branding & Profile (`school_settings.php`):**
  * Customize school name, branch/campus, registration number, SEMIS/Affiliation code, phone, email, and address.
  * Upload custom institutional logo, header banner, and report card watermarks.

---

### 🔐 13. Security, RBAC & Data Protection

* **Role-Based Access Control (RBAC) (`assign_roles.php`):**
  * Multi-tiered access levels: **Administrator**, **Staff / Teacher**, and **Viewer**.
  * Restricts sensitive financial data and system configurations to authorized personnel.
* **Encrypted Backups & One-Click Restore (`backup_restore.php`):**
  * Create password-protected ZIP archives containing complete databases and uploaded documents.
  * Automated scheduled backup engine.
  * Seamless one-click data restoration.
* **Secure Factory Reset (`reset_app.php`):**
  * Protected with double-authenticated administrator password verification and mandatory pre-reset backup generation.
* **Software Licensing & Live Update Engine (`license.php`):**
  * Built-in cryptographic license verification and in-app system update installer.

---

### 🤖 14. Integrated AI School Assistant

* Floating intelligent assistant widget accessible across all pages.
* Understands natural-language queries:
  * *"Show me today's attendance summary"*
  * *"How many fee defaulters are in Class 8?"*
  * *"List all students admitted this month"*
  * *"Show staff presence today"*

---

## 🛠️ Technology Stack & Architecture

| Layer | Technology | Details |
|---|---|---|
| **Backend** | PHP 7.4 / 8.0+ | Fast, lightweight procedural and object-oriented architecture |
| **Data Engine** | Flat-File NoSQL / Structured CSV | Zero-configuration database, ultra-portable, easy backups |
| **Frontend UI** | HTML5, TailwindCSS 3.4, Vanilla JS | Modern, responsive, high-contrast, clean UI |
| **Charts & Analytics** | Chart.js | Interactive enrollment, fee, and attendance graphs |
| **Icons & Typography** | FontAwesome 6.4, Inter / Roboto | Professional typography and iconography |
| **Printing Engine** | Pure CSS `@media print` | High-fidelity pixel-perfect printing for certificates, ID cards, receipts, and slips |

---

## 🚀 Installation & Quick Start

### 📋 Prerequisites
1. **Operating System:** Windows 7/8/10/11, Linux, or macOS
2. **Server Environment:** [XAMPP](https://www.apachefriends.org/) (PHP 7.4 or PHP 8.x recommended)
3. **Web Browser:** Google Chrome, Microsoft Edge, or Mozilla Firefox

---

### ⚡ Installation Steps

1. **Extract or Clone:**
   Copy the project directory to your XAMPP web root:
   ```text
   C:\xampp\htdocs\sms-private\
   ```

2. **Start Apache Server:**
   * Open the **XAMPP Control Panel**.
   * Click **Start** on the **Apache** service.
   *(Note: MySQL is NOT required as the system uses an optimized high-speed flat-file data engine).*

3. **Open in Browser:**
   Navigate to:
   ```text
   http://localhost/sms-private
   ```
   Or use the included one-click Windows desktop launcher: `start_system.vbs`.

4. **Default Administrative Credentials:**
   * **Username:** `admin`
   * **Password:** Check your initial deployment credentials (or configured during installation via `install.php`).
   * *Remember to update your administrative password immediately in Settings.*

---

## 📖 Quick-Start Operational Workflows

### 1️⃣ Admitting a Student
1. Open **Students** → **Admission**.
2. Complete demographic details (Name, Father Name, CNIC, Phone, Date of Birth).
3. Select Class and Section.
4. Attach photo and document scans.
5. Click **Save Student**. System assigns the next consecutive GR number automatically.

### 2️⃣ Collecting Monthly Fees
1. Open **Fees** → **Collect Fees**.
2. Search for student by GR Number, Name, or select Class.
3. Verify current tuition fee and any previous arrears.
4. Enter amount paid and click **Collect Fee**.
5. Click **Print Receipt** for instant printable receipt.

### 3️⃣ Generating Roll Number Slips
1. Navigate to **Examinations** → **Exam Slips**.
2. Choose class, exam term, and date schedule.
3. Click **Generate Slips** → **Print All Slips**.

### 4️⃣ Printing Certificates on Pre-Printed Letterhead Pads
1. Navigate to **Certificates** → Choose Certificate Type (e.g., School Leaving).
2. Select student to populate certificate records.
3. Choose **Print on Letterhead Pad** to suppress template headers and print directly onto school letterhead paper.

### 5️⃣ Creating a System Backup
1. Go to **Settings** → **Backup & Restore**.
2. Click **Create Encrypted Backup**.
3. Save the downloaded `.zip` file to an external USB or secure storage.

---

## 👨‍💻 Developer & Commercial Information

```
══════════════════════════════════════════════════════════════════════════════
                      DEVELOPED BY AR SOFTWARE SOLUTIONS                      
══════════════════════════════════════════════════════════════════════════════
Lead Software Architect : Abdul Rafay Qazi (Full Stack Developer & Systems Designer)
Company                 : AR Software Solutions
Specialization          : School ERPs, Custom Web Applications & Enterprise Software
Official Email          : abdulrafehqazi@gmail.com
WhatsApp Support        : +92 300 0358189
GitHub Portfolio        : https://github.com/rafayqazi
LinkedIn                : https://www.linkedin.com/in/abdulrafayqazi
══════════════════════════════════════════════════════════════════════════════
```

For customized feature requests, school-specific branding modules, multi-branch network deployments, or technical inquiries, please reach out via the official contact channels above.

---

## 📜 License & Copyright

**Copyright &copy; 2025-2026 AR Software Solutions. All Rights Reserved.**  
This software is licensed for private institutional use. Unauthorized redistribution, decompilation, or removal of developer attribution credits is strictly prohibited under applicable software licensing terms.
