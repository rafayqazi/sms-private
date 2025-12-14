<?php
require_once 'auth_session.php';
require_once 'functions.php';

// Determine if we're in a subdirectory
$base_path = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';

// Message Count Logic (Moved from sidebar)
$db_for_messages = new Database();
$currentUserId = isSuperAdmin() ? 'admin' : (isset($_SESSION['teacher_id']) ? $_SESSION['teacher_id'] : null);
$unreadCount = 0;
if ($currentUserId) {
    $unreadCount = $db_for_messages->getUnreadMessageCount($currentUserId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GBPS Ali Bux Jarwar - School Management System</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
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
                    },
                    keyframes: {
                        swing: {
                            '0%, 100%': { transform: 'rotate(0deg)' },
                            '20%': { transform: 'rotate(15deg)' },
                            '40%': { transform: 'rotate(-10deg)' },
                            '60%': { transform: 'rotate(5deg)' },
                            '80%': { transform: 'rotate(-5deg)' },
                        }
                    },
                    animation: {
                        swing: 'swing 1s ease-in-out infinite',
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
        <aside class="sidebar group w-64 bg-white border-r border-gray-200 flex flex-col fixed h-full overflow-y-auto transition-all duration-300 z-50 md:translate-x-0 -translate-x-full [&.collapsed]:w-16" id="sidebar">
            <div class="brand p-6 group-[.collapsed]:p-4 group-[.collapsed]:justify-center group-[.collapsed]:cursor-pointer text-xl font-bold text-indigo-600 border-b border-gray-200 flex items-center gap-3">
                <img src="<?php echo $base_path; ?>GBPS_LOGO.png" alt="Logo" class="w-10 h-10 object-contain">
                <span class="sidebar-text group-[.collapsed]:hidden text-sm leading-tight">GBPS Ali Bux Jarwar</span>
                <button id="sidebar-toggle" class="ml-auto text-gray-600 hover:text-indigo-600 transition-colors hidden md:block group-[.collapsed]:hidden" title="Toggle Sidebar">
                    <i class="fas fa-chevron-left text-lg"></i>
                </button>
            </div>
            
<!-- Login Status Indicator Removed (Moved to Top Bar) -->
            <nav class="p-4 flex flex-col gap-2 group-[.collapsed]:p-2 group-[.collapsed]:items-center">
                <a href="<?php echo $base_path; ?>index.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Dashboard">
                    <i class="fas fa-home w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Dashboard</span>
                </a>

                <!-- Students Dropdown (Admin Only) -->
                <?php if (isAdmin() || isSuperAdmin()): ?>
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'student_form.php', 'promote_students.php', 'alumni.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Students">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-user-graduate w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Students</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'student_form.php', 'promote_students.php', 'alumni.php']) ? '!flex' : ''; ?>">
                        <a href="<?php echo $base_path; ?>pages/students.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list w-4 text-center"></i> Student List
                        </a>
                        <a href="<?php echo $base_path; ?>pages/student_form.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'student_form.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-plus-circle w-4 text-center"></i> Admission
                        </a>
                        <a href="<?php echo $base_path; ?>pages/promote_students.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'promote_students.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-graduation-cap w-4 text-center"></i> Promote Students
                        </a>
                        <a href="<?php echo $base_path; ?>pages/alumni.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'alumni.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-user-check w-4 text-center"></i> Alumni
                        </a>
                        <a href="<?php echo $base_path; ?>pages/print_id_card.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'print_id_card.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                           <i class="fas fa-id-badge w-4 text-center"></i> Print Identity Card
                       </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Examination Dropdown -->
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['results.php', 'view_results.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Examination">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-poll w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Examination</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['results.php', 'view_results.php']) ? '!flex' : ''; ?>">
                        <a href="<?php echo $base_path; ?>pages/results.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-edit w-4 text-center"></i> Enter Marks
                        </a>
                        <a href="<?php echo $base_path; ?>pages/view_results.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'view_results.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-eye w-4 text-center"></i> View Results
                        </a>
                        <a href="<?php echo $base_path; ?>pages/exam_slips.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'exam_slips.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-id-card w-4 text-center"></i> Print Exam Slips
                        </a>
                    </div>
                </div>

                <!-- Attendance Dropdown -->
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Attendance">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-calendar-check w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Attendance</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php']) ? '!flex' : ''; ?>">
                        <a href="<?php echo $base_path; ?>pages/attendance.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-check-square w-4 text-center"></i> Mark Attendance
                        </a>
                        <a href="<?php echo $base_path; ?>pages/attendance_view.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'attendance_view.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list-alt w-4 text-center"></i> View Attendance
                        </a>
                    </div>
                </div>

                <!-- Teachers Dropdown (Admin Only) -->
                <?php if (isAdmin() || isSuperAdmin()): ?>
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['teacher_form.php', 'teacher_profile.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Teachers">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-chalkboard-teacher w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Teachers</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['teacher_form.php', 'teacher_profile.php']) ? '!flex' : ''; ?>">
                        <a href="<?php echo $base_path; ?>pages/teacher_profile.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_profile.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list w-4 text-center"></i> Teacher List
                        </a>
                        <a href="<?php echo $base_path; ?>pages/teacher_form.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_form.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-plus-circle w-4 text-center"></i> Add Teacher
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Parents Dropdown (Admin Only) -->
                <?php if (isAdmin() || isSuperAdmin()): ?>
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['parents.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Parents">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-users w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Parents</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['parents.php']) ? '!flex' : ''; ?>">
                        <a href="<?php echo $base_path; ?>pages/parents.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'parents.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list w-4 text-center"></i> Parents List
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages -->
<!-- Logic moved to top -->
                <a href="<?php echo $base_path; ?>pages/messages.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Messages">
                    <i class="fas fa-comments w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Messages</span>
                    <?php if ($unreadCount > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full group-[.collapsed]:absolute group-[.collapsed]:top-0 group-[.collapsed]:right-0"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Assign User Role (Admin Only) -->
                <?php if (isAdmin() || isSuperAdmin()): ?>
                <a href="<?php echo $base_path; ?>pages/assign_roles.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo basename($_SERVER['PHP_SELF']) == 'assign_roles.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Assign User Role">
                    <i class="fas fa-user-shield w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Assign User Role</span>
                </a>
                <?php endif; ?>

                <!-- Backup and Restore Dropdown (Admin Only) -->
                <?php if (isAdmin() || isSuperAdmin()): ?>
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['reset_app.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Backup and Restore">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-database w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Backup and Restore</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['reset_app.php']) ? '!flex' : ''; ?>">
                        <button onclick="openBackupModal()" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-orange-600 hover:text-orange-700 hover:bg-orange-50 transition-colors w-full text-left">
                            <i class="fas fa-download w-4 text-center"></i> Backup Data
                        </button>
                        <a href="<?php echo $base_path; ?>pages/reset_app.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-red-600 hover:text-red-700 hover:bg-red-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'reset_app.php' ? 'text-red-700 bg-red-50' : ''; ?>">
                            <i class="fas fa-exclamation-triangle w-4 text-center"></i> Reset App
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                <a href="<?php echo $base_path; ?>logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-600 font-medium hover:bg-red-50 transition-colors mt-auto w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center" title="Logout">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Logout</span>
                </a>
            </nav>
        </aside>
        
        <!-- Mobile Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

        <main class="main-content flex-1 md:ml-64 transition-all duration-300">
            <!-- Top Navigation Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-4 py-4 mb-6 md:px-8 md:mb-8 flex justify-between items-center sticky top-0 z-30">
                <div class="flex items-center gap-3 md:gap-4">
                    <button id="mobile-menu-btn" class="md:hidden text-gray-600 hover:text-indigo-600 transition-colors p-1">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <!-- Breadcrumbs or Page Title could go here -->
                </div>

                <div class="flex items-center gap-4 md:gap-6">
                    <!-- Notifications -->
                    <a href="<?php echo $base_path; ?>pages/messages.php" class="relative text-gray-500 hover:text-indigo-600 transition-colors group" title="Messages">
                        <i class="fas fa-bell text-xl group-hover:animate-swing"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full border-2 border-white animate-pulse">
                                <?php echo $unreadCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- User Profile Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center gap-2 md:gap-3 focus:outline-none">
                            <div class="text-right hidden md:block">
                                <div class="text-sm font-semibold text-gray-800"><?php echo getUserDisplayName(); ?></div>
                                <div class="text-xs text-gray-500"><?php echo getUserRoleBadge(); ?></div>
                            </div>
                            <div class="w-8 h-8 md:w-10 md:h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold border-2 border-indigo-50 transition-colors group-hover:border-indigo-200">
                                <?php echo strtoupper(substr(getUserDisplayName(), 0, 1)); ?>
                            </div>
                            <i class="fas fa-chevron-down text-xs text-gray-400 group-hover:text-indigo-600 transition-colors hidden sm:block"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 hidden group-hover:block hover:block transition-all transform origin-top-right z-50">
                            <div class="px-4 py-3 border-b border-gray-50 md:hidden">
                                <div class="text-sm font-semibold text-gray-800"><?php echo getUserDisplayName(); ?></div>
                                <div class="text-xs text-gray-500"><?php echo getUserRoleBadge(); ?></div>
                            </div>
                            
                            <?php if (isset($_SESSION['teacher_id'])): ?>
                                <a href="<?php echo $base_path; ?>pages/teacher_profile.php?id=<?php echo $_SESSION['teacher_id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                    <i class="fas fa-user mr-2 w-4"></i> My Profile
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?php echo $base_path; ?>pages/messages.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <i class="fas fa-envelope mr-2 w-4"></i> Messages
                                <?php if ($unreadCount > 0): ?>
                                    <span class="ml-auto text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full"><?php echo $unreadCount; ?></span>
                                <?php endif; ?>
                            </a>
                            
                            <div class="border-t border-gray-100 my-1"></div>
                            
                            <a href="<?php echo $base_path; ?>logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2 w-4"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content Wrapper -->
            <div class="px-4 py-4 md:px-8 md:py-6">
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Collapse Logic
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const brandSection = document.querySelector('.brand');
            
            // Load saved state from localStorage
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                setSidebarState(true);
            }
            
            // Toggle button click (only for collapsing since it's hidden when collapsed)
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent triggering brand click
                    const currentlyCollapsed = sidebar.classList.contains('collapsed');
                    setSidebarState(!currentlyCollapsed);
                });
            }

            // Brand section click to expand
            if (brandSection) {
                brandSection.addEventListener('click', function() {
                    if (sidebar.classList.contains('collapsed')) {
                        setSidebarState(false);
                    }
                });
            }
            
            function setSidebarState(collapsed) {
                if (collapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.remove('md:ml-64');
                    mainContent.classList.add('md:ml-16');
                    localStorage.setItem('sidebarCollapsed', 'true');
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('md:ml-16');
                    mainContent.classList.add('md:ml-64');
                    localStorage.setItem('sidebarCollapsed', 'false');
                }
            }
            
            // Dropdown Logic
            const toggles = document.querySelectorAll('.nav-dropdown-toggle');
            
            toggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    // If sidebar is collapsed, expand it first
                    if (sidebar.classList.contains('collapsed')) {
                        setSidebarState(false);
                    }

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
                    if(chevron) chevron.style.transform = 'rotate(180deg)';
                }
            });

            // Mobile Sidebar Logic
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

        // Backup Password Modal Logic
        function openBackupModal() {
            document.getElementById('backupModal').classList.remove('hidden');
        }

        function closeBackupModal() {
            document.getElementById('backupModal').classList.add('hidden');
        }
    </script>

    <!-- Secure Backup Modal -->
    <div id="backupModal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 animate-[scaleIn_0.3s_ease-out]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-lock text-orange-600"></i> Security Check
                </h3>
                <button onclick="closeBackupModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <p class="text-gray-600 mb-6">Please enter your password to authorize the database backup download.</p>
            
            <form action="<?php echo $base_path; ?>api/backup_data.php" method="POST" target="_blank" onsubmit="setTimeout(closeBackupModal, 1000)">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500" placeholder="Enter Admin Password">
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeBackupModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md transition-colors shadow-sm flex items-center gap-2">
                        <i class="fas fa-download"></i> Download Backup
                    </button>
                </div>
            </form>
        </div>
    </div>
