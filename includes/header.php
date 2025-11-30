<?php
require_once 'auth_session.php';
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GBPS Ali Bux Jarwar - School Management System</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#15803d', // Green-700
                        secondary: '#f59e0b', // Amber-500
                        accent: '#166534', // Green-800
                    }
                }
            }
        }
    </script>
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container flex min-h-screen bg-gray-50">
        <aside class="sidebar w-64 bg-white border-r border-gray-200 flex flex-col fixed h-full overflow-y-auto transition-transform duration-300 z-50 md:translate-x-0 -translate-x-full" id="sidebar">
            <div class="brand p-6 text-xl font-bold text-indigo-600 border-b border-gray-200 flex items-center gap-3">
                <img src="GBPS_LOGO.png" alt="Logo" class="w-10 h-10 object-contain">
                <span>GBPS Ali Bux</span>
            </div>
            <nav class="p-4 flex flex-col gap-2">
                <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                    <i class="fas fa-home w-5"></i> Dashboard
                </a>

                <!-- Students Dropdown -->
                <div class="flex flex-col">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors <?php echo in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'student_form.php', 'promote_students.php', 'alumni.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-user-graduate w-5"></i> <span>Students</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 <?php echo in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'student_form.php', 'promote_students.php', 'alumni.php']) ? '!flex' : ''; ?>">
                        <a href="students.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list w-4"></i> Student List
                        </a>
                        <a href="student_form.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'student_form.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-plus-circle w-4"></i> Admission
                        </a>
                        <a href="promote_students.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'promote_students.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-graduation-cap w-4"></i> Promote Students
                        </a>
                        <a href="alumni.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'alumni.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-user-check w-4"></i> Alumni
                        </a>
                    </div>
                </div>

                <!-- Attendance Dropdown -->
                <div class="flex flex-col">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors <?php echo in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-calendar-check w-5"></i> <span>Attendance</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 <?php echo in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php']) ? '!flex' : ''; ?>">
                        <a href="attendance.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-check-square w-4"></i> Mark Attendance
                        </a>
                        <a href="attendance_view.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'attendance_view.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list-alt w-4"></i> View Attendance
                        </a>
                    </div>
                </div>

                <!-- Teachers Dropdown -->
                <div class="flex flex-col">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors <?php echo in_array(basename($_SERVER['PHP_SELF']), ['teacher_form.php', 'teacher_profile.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-chalkboard-teacher w-5"></i> <span>Teachers</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 <?php echo in_array(basename($_SERVER['PHP_SELF']), ['teacher_form.php', 'teacher_profile.php']) ? '!flex' : ''; ?>">
                        <a href="teacher_profile.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_profile.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list w-4"></i> Teacher List
                        </a>
                        <a href="teacher_form.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_form.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-plus-circle w-4"></i> Add Teacher
                        </a>
                    </div>
                </div>

                <!-- Parents Dropdown -->
                <div class="flex flex-col">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors <?php echo in_array(basename($_SERVER['PHP_SELF']), ['parents.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-users w-5"></i> <span>Parents</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 <?php echo in_array(basename($_SERVER['PHP_SELF']), ['parents.php']) ? '!flex' : ''; ?>">
                        <a href="parents.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'parents.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list w-4"></i> Parents List
                        </a>
                    </div>
                </div>

                <a href="reset_app.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-600 font-medium hover:bg-red-50 transition-colors">
                    <i class="fas fa-exclamation-triangle w-5"></i> Reset App
                </a>
                <a href="actions/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-600 font-medium hover:bg-red-50 transition-colors mt-auto">
                    <i class="fas fa-sign-out-alt w-5"></i> Logout
                </a>
            </nav>
        </aside>
        
        <!-- Mobile Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

        <main class="main-content flex-1 md:ml-64 p-8">
            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="md:hidden mb-4 text-gray-600 hover:text-indigo-600">
                <i class="fas fa-bars text-2xl"></i>
            </button>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown Logic
            const toggles = document.querySelectorAll('.nav-dropdown-toggle');
            
            toggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const submenu = this.nextElementSibling;
                    const chevron = this.querySelector('.fa-chevron-down');
                    
                    // Toggle visibility
                    submenu.classList.toggle('hidden');
                    submenu.classList.toggle('flex');
                    
                    // Rotate chevron
                    if (submenu.classList.contains('flex')) {
                        chevron.style.transform = 'rotate(180deg)';
                    } else {
                        chevron.style.transform = 'rotate(0deg)';
                    }
                });
                
                // Set initial chevron state
                const submenu = toggle.nextElementSibling;
                if (!submenu.classList.contains('hidden')) {
                    const chevron = toggle.querySelector('.fa-chevron-down');
                    chevron.style.transform = 'rotate(180deg)';
                }
            });

            // Mobile Sidebar Logic
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const btn = document.getElementById('mobile-menu-btn');

            if (btn) {
                btn.addEventListener('click', () => {
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', () => {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                });
            }
        });
    </script>
