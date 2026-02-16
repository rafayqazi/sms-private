---
name: Project Standards & UX Guidelines
description: Mandatory standards for UI consistency and AJAX-first processing.
---

# Project Standards & UX Guidelines

This document defines the core engineering and design standards for the School Management System (SMS). Every AI model working on this project MUST follow these guidelines to ensure consistency, security, and a premium user experience.

## 1. Interaction Strategy (AJAX-First)

**CRITICAL: Avoid full-page redirects for processing or verification tasks.**

- **Background Verification**: All sensitive operations (password checks, data validation) must be done via AJAX using the `fetch` API.
- **No Native Dialogs**: NEVER use browser-native `alert()`, `confirm()`, or `prompt()`. These "Localhost says..." boxes look unprofessional. Always use the project's custom modal components (`showModal` or `showConfirmationModal` from `footer.php`) for notifications and user confirmation.
- **In-Modal Error Handling**: Validation errors (e.g., "Incorrect Password") must be displayed directly within the active modal or form. Never redirect the user to a plain error page.
- **Silent Processing**: Destructive actions like "System Reset" or "Data Restore" should show progress indicators (e.g., spinning icons) within the modal.
- **Background Downloads**: For file exports (like CSV or ZIP backups), use a hidden `<iframe>` as a target to prevent the page from flickering, refreshing, or opening empty tabs.

## 2. UI & Theme Consistency

**Maintain the "Premium Dashboard" aesthetic across all pages.**

- **Framework**: Use **Tailwind CSS**. Avoid writing custom CSS in separate files; use utility classes where possible.
- **Layout**: Use card-based layouts with large border-radius (`rounded-2xl` or `rounded-3xl`) and subtle shadows.
- **Typography**: Focus on high-contrast, bold headings (`font-black`) and wide letter spacing for smaller uppercase labels.
- **Color Palettes**:
    - **Primary/Success**: Emerald (`emerald-600`).
    - **Secondary/Destructive**: Orange (`orange-600`) or Red (`red-600`).
    - **Backgrounds**: Use clean whites for light mode and deep grays/slates for dark mode (`dark:bg-gray-900`).
- **Animations**: Use smooth transitions (e.g., `duration-300`) and scale transforms (`scale-95` to `scale-100`) for modals and interactive elements.

## 3. Modal Implementation

All modals should follow the standard established in `pages/backup_restore.php`:
- Use `toggleModalAnimation(modalId, contentId, isOpen)` for consistent behavior.
- Use a backdrop blur (`backdrop-blur-md`) for the modal background.
- Ensure "Cancel" buttons are always present and properly scoped to avoid global function conflicts.

## 4. Security Standards

- **Backend Verification**: Every AJAX-triggered action must be re-verified on the server using `Database->verifyAdmin()` or similar robust methods.
- **CSRF & Session**: Ensure all API endpoints include `auth_session.php` to protect against unauthorized access.

## 5. Printing & PDF Standards

- **html2pdf.js Integration**: All PDF printing and generation within the software MUST use the **html2pdf.js** library. This ensures that documents are rendered consistently as high-quality PDFs without relying solely on the browser's print dialog, providing a more professional "Export to PDF" experience.
- **Consistent Filename Format**: All downloaded PDFs must follow a specific naming convention to ensure easy identification. The format should include descriptive labels and relevant metadata.
    - **Format**: `Name: [Student Name] , GR: [GR No] , Year: [Year] , [Document Type].pdf`
    - **Example**: `Name: Ali Khan , GR: 123 , Year: 2026 , SLC.pdf`
- **Configuration**: Always use high-quality rendering (e.g., `scale: 2`) and define precise page formats (e.g., `format: 'a4'`) to match official document standards.

