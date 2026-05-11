<?php
require_once 'auth_session.php';
require_once 'functions.php';

// Personalized Greeting Logic
$hour = date('H');
$timeGreeting = ($hour < 12) ? 'Good Morning' : 'Good Evening';
$username = $_SESSION['username'] ?? 'User';
if (isset($_SESSION['teacher_name'])) {
    $username = $_SESSION['teacher_name'];
}

// Determine if we're in a subdirectory
$base_path = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';

// Message Count & Profile Image Logic
$db_for_messages = new Database();
$headerSettings = $db_for_messages->getSchoolSettings();
$currentUserId = isSuperAdmin() ? 'admin' : (isset($_SESSION['teacher_id']) ? $_SESSION['teacher_id'] : null);
$unreadCount = 0;
$userProfileImage = null;

if (isset($_SESSION['user'])) {
    // 1. Get Unread Count
    $unreadCount = $db_for_messages->getUnreadMessageCount($currentUserId ?: 'admin');

    // 2. Resolve Profile Image
    // Priority: Session > Teacher Record > Default
    if (!empty($_SESSION['profile_image'])) {
        $cleanedPath = ltrim(str_replace('../', '', $_SESSION['profile_image']), '/');
        $userProfileImage = $base_path . $cleanedPath;
    }
    
    // Fallback for teachers if session was not updated yet
    if (!$userProfileImage && $currentUserId && $currentUserId !== 'admin') {
        $teacherRecord = $db_for_messages->getTeacher($currentUserId);
        if ($teacherRecord && !empty($teacherRecord['profile_image'])) {
            $cleanedPath = ltrim(str_replace('../', '', $teacherRecord['profile_image']), '/');
            $userProfileImage = $base_path . $cleanedPath;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($headerSettings['school_name']); ?> - School Management System</title>
    <link rel="icon" type="image/x-icon" href="<?php echo $base_path; ?>assets/branding/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        const APP_BASE_PATH = '<?php echo $base_path; ?>';
        tailwind.config = {
            darkMode: 'class',
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
    <style>
        /* Global Dark Mode Overrides for CDN Tailwind */
        .dark body { background-color: #030712 !important; color: #f3f4f6 !important; }
        .dark .bg-white { background-color: #111827 !important; }
        .dark .bg-gray-50 { background-color: #030712 !important; }
        .dark .text-gray-800, .dark .text-gray-900 { color: #f3f4f6 !important; }
        .dark .text-gray-700 { color: #d1d5db !important; }
        .dark .text-gray-600 { color: #9ca3af !important; }
        .dark .border-gray-100, .dark .border-gray-200, .dark .border-gray-300 { border-color: #1f2937 !important; }
        
        /* Interactive element fixes */
        .dark .hover\:bg-gray-50:hover { background-color: #1f2937 !important; }
        .dark .hover\:bg-indigo-50:hover { background-color: #312e81 !important; }
        .dark .shadow-sm, .dark .shadow-md, .dark .shadow-lg { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 0 2px 4px -1px rgba(0, 0, 0, 0.3) !important; }
        
        /* Table and Input fixes */
        .dark table { background-color: #111827 !important; }
        .dark th { background-color: #1f2937 !important; color: #e5e7eb !important; border-color: #374151 !important; }
        .dark td { border-color: #1f2937 !important; }
        .dark select, .dark input { background-color: #1f2937 !important; color: #f3f4f6 !important; border-color: #374151 !important; }

        /* Custom Fancy Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.5); 
            border-radius: 5px;
        }
        
        .dark ::-webkit-scrollbar-track {
            background: rgba(17, 24, 39, 0.5); 
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #4f46e5 0%, #15803d 100%); 
            border-radius: 5px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
        
        .dark ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #6366f1 0%, #22c55e 100%); 
            border: 2px solid transparent;
            background-clip: content-box;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #4338ca 0%, #166534 100%); 
            border: 2px solid transparent;
            background-clip: content-box;
        }
        
        .dark ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #4f46e5 0%, #15803d 100%); 
            border: 2px solid transparent;
            background-clip: content-box;
        }
    </style>
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        // Immediate theme check to prevent flashing
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="dark:bg-gray-950 transition-colors duration-300">
    <!-- Auto Update Logic (Banner Removed per user request) -->
    <?php if (isAdmin()): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const CHECK_INTERVAL = 24 * 60 * 60 * 1000; // 24 hours
            const LAST_CHECK_KEY = 'sys_last_update_check';
            
            const lastCheck = parseInt(localStorage.getItem(LAST_CHECK_KEY) || '0');
            const now = Date.now();

            // Perform check if time elapsed
            if (now - lastCheck > CHECK_INTERVAL) {
                // Background check
                fetch('<?php echo $base_path; ?>api/check_update.php')
                    .then(res => res.json())
                    .then(data => {
                        localStorage.setItem(LAST_CHECK_KEY, now.toString());
                        // We strictly rely on session-based checks now for the dashboard
                    })
                    .catch(e => console.error('Update Check failed (background)', e));
            }
        });
    </script>
    <?php endif; ?>

    <div class="app-container flex min-h-screen bg-gray-50 dark:bg-gray-950">
        <?php if (isset($_SESSION['user'])): ?>
        <aside class="sidebar group w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col fixed h-full overflow-y-auto transition-all duration-300 z-50 md:translate-x-0 -translate-x-full [&.collapsed]:w-16" id="sidebar">
            <div class="brand min-h-[5rem] p-4 group-[.collapsed]:justify-center group-[.collapsed]:cursor-pointer border-b border-gray-200 dark:border-gray-800 flex items-center gap-3 bg-gray-50/50 dark:bg-gray-900/50">
                <?php 
                $logoUrl = (!empty($headerSettings['school_logo']) && file_exists($base_path . $headerSettings['school_logo'])) 
                           ? $base_path . $headerSettings['school_logo'] 
                            : $base_path . 'assets/branding/logo.png'; 
                ?>
                <img src="<?php echo $logoUrl; ?>?v=<?php echo time(); ?>" alt="Logo" class="w-10 h-10 object-contain drop-shadow-sm transition-transform group-hover:scale-105 flex-shrink-0">
                <div class="flex flex-col group-[.collapsed]:hidden">
                    <span class="text-sm font-black text-gray-800 dark:text-gray-100 uppercase tracking-tight leading-tight" title="<?php echo htmlspecialchars($headerSettings['school_name']); ?>">
                        <?php echo htmlspecialchars($headerSettings['school_name']); ?>
                    </span>
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-500 uppercase tracking-widest mt-0.5">Management Portal</span>
                </div>
                <button id="sidebar-toggle" class="ml-auto text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors hidden md:block group-[.collapsed]:hidden p-1 rounded-md hover:bg-gray-200/50 dark:hover:bg-gray-800" title="Collapse Menu">
                    <i class="fas fa-chevron-left text-sm"></i>
                </button>
            </div>
            
<!-- Login Status Indicator Removed (Moved to Top Bar) -->
            <nav class="p-4 flex flex-col gap-2 group-[.collapsed]:p-2 group-[.collapsed]:items-center overflow-y-auto max-h-[calc(100vh-5rem)] custom-scrollbar">
                <!-- Dashboard -->
                <?php
                $isTeacherMenu = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher';
                $dashboardLink = $isTeacherMenu ? 'pages/teacher_dashboard.php' : 'index.php';
                $isDashboardActive = in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'teacher_dashboard.php']);
                ?>
                <a href="<?php echo $base_path . $dashboardLink; ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo $isDashboardActive ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Dashboard">
                    <i class="fas fa-home w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Dashboard</span>
                </a>

                <!-- Class Management -->
                <?php if (!isViewer() && !isset($_SESSION['teacher_id'])): ?>
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_classes.php']) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Class Management">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-chalkboard w-5 text-center"></i> <span class="group-[.collapsed]:hidden whitespace-nowrap">Class Management</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_classes.php']) ? '!flex' : ''; ?>">
                         <a href="<?php echo $base_path; ?>pages/manage_classes.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'manage_classes.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-layer-group w-4 text-center"></i> Manage Classes
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Students Dropdown -->
                <?php if ((isAdmin() || isSuperAdmin()) && !isViewer()): ?>
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'student_form.php', 'promote_students.php', 'alumni.php', 'print_id_card.php']) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Students">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-user-graduate w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Students</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'student_form.php', 'promote_students.php', 'alumni.php', 'print_id_card.php']) ? '!flex' : ''; ?>">
                        <a href="<?php echo $base_path; ?>pages/students.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list w-4 text-center"></i> Student List
                        </a>
                        <a href="javascript:void(0)" onclick="openAdmissionModal()" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'student_form.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
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
                
                <!-- Fees Section -->
                <?php if ((isAdmin() || isSuperAdmin()) && !isViewer()): ?>
                <a href="<?php echo $base_path; ?>pages/fees.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo basename($_SERVER['PHP_SELF']) == 'fees.php' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Fees">
                    <i class="fas fa-file-invoice-dollar w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Fees & Payments</span>
                </a>
                <?php endif; ?>

                <!-- Certificates -->
                <?php if ((isAdmin() || isSuperAdmin()) && !isViewer()): ?>
                <a href="<?php echo $base_path; ?>pages/certificates.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo basename($_SERVER['PHP_SELF']) == 'certificates.php' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Certificates">
                    <i class="fas fa-certificate w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Certificates</span>
                </a>
                <?php endif; ?>

                <!-- Teachers Dropdown (Admin Only) -->
                <?php if ((isAdmin() || isSuperAdmin()) && !isViewer()): ?>
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['teacher_form.php', 'teacher_profile.php', 'teacher_attendance.php', 'teacher_attendance_view.php']) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Teachers">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-chalkboard-teacher w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Teachers</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['teacher_form.php', 'teacher_profile.php', 'teacher_attendance.php', 'teacher_attendance_view.php']) ? '!flex' : ''; ?>">
                        <a href="<?php echo $base_path; ?>pages/teacher_profile.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_profile.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list w-4 text-center"></i> Teacher List
                        </a>
                        <a href="<?php echo $base_path; ?>pages/teacher_attendance.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_attendance.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-calendar-check w-4 text-center"></i> Mark Attendance
                        </a>
                        <a href="<?php echo $base_path; ?>pages/teacher_attendance_view.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_attendance_view.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-clipboard-user w-4 text-center"></i> Attendance Reports
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Parents Dropdown (Admin Only) -->
                <?php if ((isAdmin() || isSuperAdmin()) && !isViewer()): ?>
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['parents.php']) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Parents">
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

                <!-- Attendance Dropdown -->
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php']) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Attendance">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-calendar-check w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Attendance</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php']) ? '!flex' : ''; ?>">
                        <?php if (!isViewer()): ?>
                        <a href="<?php echo $base_path; ?>pages/attendance.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-user-check w-4 text-center"></i> Mark Attendance
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo $base_path; ?>pages/attendance_view.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'attendance_view.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list-alt w-4 text-center"></i> Attendance Reports
                        </a>
                    </div>
                </div>

                <!-- Examination Dropdown -->
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['results.php', 'view_results.php', 'exam_slips.php', 'exam_attendance.php']) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Examination">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-poll w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Examination</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['results.php', 'view_results.php', 'exam_slips.php', 'exam_attendance.php']) ? '!flex' : ''; ?>">
                        <?php if (!isViewer()): ?>
                        <a href="<?php echo $base_path; ?>pages/results.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'text-indigo-600 bg-gray-50 dark:text-indigo-400 dark:bg-gray-800' : ''; ?>">
                            <i class="fas fa-edit w-4 text-center"></i> Enter Marks
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo $base_path; ?>pages/view_results.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'view_results.php' ? 'text-indigo-600 bg-gray-50 dark:text-indigo-400 dark:bg-gray-800' : ''; ?>">
                            <i class="fas fa-eye w-4 text-center"></i> View Results
                        </a>
                        <?php if (!isViewer() && !isset($_SESSION['teacher_id'])): ?>
                        <a href="<?php echo $base_path; ?>pages/exam_slips.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'exam_slips.php' ? 'text-indigo-600 bg-gray-50 dark:text-indigo-400 dark:bg-gray-800' : ''; ?>">
                            <i class="fas fa-id-card w-4 text-center"></i> Print Exam Slips
                        </a>
                        <a href="<?php echo $base_path; ?>pages/exam_attendance.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'exam_attendance.php' ? 'text-indigo-600 bg-gray-50 dark:text-indigo-400 dark:bg-gray-800' : ''; ?>">
                            <i class="fas fa-clipboard-list w-4 text-center"></i> Examination Attendance
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Book Bank -->
                <?php if (!isset($_SESSION['teacher_id'])): ?>
                <a href="<?php echo $base_path; ?>pages/book_bank.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['book_bank.php', 'book_bank_actions.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Book Bank">
                    <i class="fas fa-book w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Book Bank</span>
                </a>
                <?php endif; ?>

                <!-- Inventory Dropdown -->
                <?php if (!isset($_SESSION['teacher_id'])): ?>
                <div class="flex flex-col w-full group-[.collapsed]:items-center">
                    <button class="nav-dropdown-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['inventory.php', 'inventory_form.php', 'categories.php', 'dead_stock.php']) ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Inventory">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-boxes w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Inventory</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.collapsed]:hidden"></i>
                    </button>
                    <div class="hidden flex-col pl-4 mt-1 space-y-1 w-full group-[.collapsed]:hidden <?php echo in_array(basename($_SERVER['PHP_SELF']), ['inventory.php', 'inventory_form.php', 'categories.php', 'dead_stock.php']) ? '!flex' : ''; ?>">
                        <a href="<?php echo $base_path; ?>pages/inventory.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-list w-4 text-center"></i> Dashboard
                        </a>
                        <?php if (!isViewer()): ?>
                        <a href="<?php echo $base_path; ?>pages/inventory_form.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'inventory_form.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-plus-circle w-4 text-center"></i> Add Item
                        </a>
                        <a href="<?php echo $base_path; ?>pages/categories.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-tags w-4 text-center"></i> Categories
                        </a>
                        <a href="<?php echo $base_path; ?>pages/dead_stock.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'dead_stock.php' ? 'text-indigo-600 bg-gray-50' : ''; ?>">
                            <i class="fas fa-book-dead w-4 text-center"></i> Dead Stock
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages -->
                <a href="<?php echo $base_path; ?>pages/messages.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 font-medium hover:bg-indigo-50 hover:text-indigo-600 transition-colors w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>" title="Messages">
                    <i class="fas fa-comments w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Messages</span>
                    <?php if ($unreadCount > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full group-[.collapsed]:absolute group-[.collapsed]:top-0 group-[.collapsed]:right-0"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Assign User Role & Extras (Admin Only) -->
                <?php if ((isAdmin() || isSuperAdmin()) && !isViewer()): ?>

                
                <a href="<?php echo $base_path; ?>pages/backup_restore.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['backup_restore.php', 'reset_app.php']) ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Backup and Restore">
                    <i class="fas fa-database w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Backup and Restore</span>
                </a>

                <a href="<?php echo $base_path; ?>pages/settings.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 dark:text-gray-400 font-medium hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors w-full group-[.collapsed]:px-2 group-[.collapsed]:justify-center <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400' : ''; ?>" title="Settings">
                    <i class="fas fa-cog w-5 text-center"></i> <span class="group-[.collapsed]:hidden">Settings</span>
                </a>
                <?php endif; ?>

                </a>
            </nav>
        </aside>
        <?php endif; ?>
        
        <!-- Mobile Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

        <main class="main-content flex-1 <?php echo isset($_SESSION['user']) ? 'md:ml-64' : 'md:ml-0'; ?> transition-all duration-300 dark:bg-gray-950">
            <!-- Top Navigation Bar -->
            <header class="bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-800 px-4 py-4 mb-6 md:px-8 md:mb-8 flex justify-between items-center sticky top-0 z-30">
                <div class="flex items-center gap-3 md:gap-4">
                    <?php if (isset($_SESSION['user'])): ?>
                    <button id="mobile-menu-btn" class="md:hidden text-gray-600 hover:text-indigo-600 transition-colors p-1">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-4 md:gap-6">
                    <!-- Dark Mode Toggle -->
                    <button id="dark-mode-toggle" class="relative p-2 text-gray-500 hover:text-indigo-600 transition-colors bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center border border-gray-200 dark:border-gray-700" title="Toggle Dark Mode">
                        <i id="theme-icon-moon" class="fas fa-moon text-lg block dark:hidden"></i>
                        <i id="theme-icon-sun" class="fas fa-sun text-lg hidden dark:block text-yellow-400"></i>
                    </button>

                    <?php if (isset($_SESSION['user'])): ?>
                    <!-- Notifications -->
                    <a href="<?php echo $base_path; ?>pages/messages.php" class="relative text-gray-500 hover:text-indigo-600 transition-colors group" title="Messages">
                        <i class="fas fa-bell text-xl group-hover:animate-swing"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full border-2 border-white animate-pulse">
                                <?php echo $unreadCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>


                    <?php if (isset($_SESSION['user'])): ?>
                    <!-- User Profile Dropdown -->
                    <div class="relative group">
                        <button id="user-menu-btn" class="flex items-center gap-2 md:gap-3 focus:outline-none">
                            <div class="text-right hidden md:block">
                                <div class="text-[10px] font-black text-indigo-500 dark:text-indigo-400 uppercase tracking-widest leading-none mb-1">
                                    <?php echo $timeGreeting; ?>, Welcome
                                </div>
                                <div class="text-sm font-black text-gray-800 dark:text-gray-100 leading-tight">
                                    <?php echo htmlspecialchars($username); ?>
                                </div>
                            </div>
                            <div class="w-8 h-8 md:w-10 md:h-10 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center font-black border-2 border-white dark:border-gray-800 shadow-sm transition-all group-hover:scale-110 group-hover:border-indigo-200 overflow-hidden shrink-0">
                                <?php if (!empty($userProfileImage)): ?>
                                    <img src="<?php echo htmlspecialchars($userProfileImage); ?>?v=<?php echo time(); ?>" alt="Profile" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-user text-lg md:text-xl"></i>
                                <?php endif; ?>
                            </div>
                            <i class="fas fa-chevron-down text-xs text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors hidden sm:block"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="user-menu-dropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 hidden group-hover:block hover:block transition-all transform origin-top-right z-50">
                            <div class="px-4 py-3 border-b border-gray-50 dark:border-gray-700 md:hidden">
                                <div class="text-[10px] font-black text-indigo-500 uppercase tracking-widest leading-none mb-1"><?php echo $timeGreeting; ?>, Welcome</div>
                                <div class="text-sm font-black text-gray-800 dark:text-white"><?php echo htmlspecialchars($username); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo getUserRoleBadge(); ?></div>
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
                            <a href="<?php echo $base_path; ?>pages/manage_classes.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <i class="fas fa-graduation-cap mr-2 w-4"></i> Manage Classes
                            </a>
                            <a href="<?php echo $base_path; ?>pages/print_slips.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <i class="fas fa-print mr-2 w-4"></i> Print Result Card
                            </a>
                            <a href="<?php echo $base_path; ?>pages/print_all_results.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <i class="fas fa-print mr-2 w-4"></i> Print All Marksheets
                            </a>
                            <a href="<?php echo $base_path; ?>pages/exam_slips.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <i class="fas fa-id-card mr-2 w-4"></i> Print Exam Slips
                            </a>
                            <a href="<?php echo $base_path; ?>pages/exam_attendance.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <i class="fas fa-clipboard-list mr-2 w-4"></i> Examination Attendance
                            </a>
                            
                            <a href="<?php echo $base_path; ?>pages/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <i class="fas fa-cog mr-2 w-4"></i> Settings
                            </a>
                            
                            <a href="<?php echo $base_path; ?>pages/school_settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <i class="fas fa-school mr-2 w-4"></i> School Settings
                            </a>
                            
                            <div class="border-t border-gray-100 my-1"></div>
                            
                            <a href="<?php echo $base_path; ?>logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2 w-4"></i> Logout
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center gap-3">
                        <a href="<?php echo $base_path; ?>login.php" class="px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition-colors">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </a>
                    </div>
                    <?php endif; ?>
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
            
            // User Dropdown Click Logic
            const userMenuBtn = document.getElementById('user-menu-btn');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');

            if (userMenuBtn && userMenuDropdown) {
                userMenuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenuDropdown.classList.toggle('hidden');
                });

                // Close on outside click
                document.addEventListener('click', function(e) {
                    if (!userMenuBtn.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                        userMenuDropdown.classList.add('hidden');
                    }
                });
            }

            // Dark Mode Toggle Logic
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', () => {
                    const isDark = document.documentElement.classList.toggle('dark');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                });
            }
        });
    </script>

