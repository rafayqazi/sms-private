<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Set session cookie security parameters
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
$db = new Database();
$settings = $db->getSchoolSettings();

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Verification
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if it's admin login
    $db = new Database();
    if ($db->verifyAdmin($username, $password)) {
        $_SESSION['user'] = $username;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['user_role'] = 'Admin';
        $_SESSION['username'] = $username;
        $_SESSION['teacher_id'] = null;
        $_SESSION['assigned_classes'] = [];
        $_SESSION['login_time'] = time(); // Set login timestamp
        $_SESSION['show_welcome_animation'] = true; // Trigger animation
        
        session_regenerate_id(true); // Prevent session fixation
        
        header("Location: index.php");
        exit;
    } else {
        // Check teacher login
        $db = new Database();
        $userRole = $db->getUserRoleByUsername($username);
        
        if ($userRole && password_verify($password, $userRole['password_hash'])) {
            // Get teacher details
            $teacher = $db->getTeacher($userRole['teacher_id']);
            
            $_SESSION['user'] = $username;
            $_SESSION['user_type'] = 'teacher';
            $_SESSION['user_role'] = $userRole['role']; // Admin or Editor
            $_SESSION['username'] = $username;
            $_SESSION['teacher_id'] = $userRole['teacher_id'];
            $_SESSION['teacher_name'] = $teacher ? $teacher['name'] : 'Teacher';
            $_SESSION['assigned_classes'] = $userRole['assigned_classes'] ? $userRole['assigned_classes'] : [];
            $_SESSION['login_time'] = time(); // Set login timestamp
            $_SESSION['show_welcome_animation'] = true; // Trigger animation
            
            session_regenerate_id(true); // Prevent session fixation
            
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid Username or Password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: '#4f46e5',
                        'primary-hover': '#4338ca',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-indigo-500 to-purple-600 min-h-screen flex items-center justify-center p-4 overflow-hidden relative">
    
    <!-- Decorative Circles -->
    <div class="absolute top-[-50px] left-[-50px] w-72 h-72 bg-white/10 rounded-full blur-sm z-0"></div>
    <div class="absolute bottom-[-100px] right-[-100px] w-96 h-96 bg-white/10 rounded-full blur-sm z-0"></div>

    <div class="w-full max-w-md bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/20 p-6 md:p-10 relative z-10 animate-[slideUp_0.6s_ease-out]">
        <div class="text-center mb-8">
            <img src="GBPS_LOGO.png?v=<?php echo time(); ?>" alt="GBPS Logo" class="w-24 h-24 object-contain mx-auto mb-4 drop-shadow-md hover:scale-105 transition-transform duration-300">
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">School Management System</h1>
            <p class="text-slate-500 font-medium"><?php echo htmlspecialchars($settings['school_name']); ?></p>
        </div>

        <div class="text-center mb-8">
            <h2 class="text-lg font-semibold text-slate-800 relative inline-block after:content-[''] after:block after:w-10 after:h-1 after:bg-primary after:mx-auto after:mt-2 after:rounded-full">Welcome Back</h2>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-100 text-red-600 p-4 rounded-xl mb-6 flex items-center gap-3 text-sm animate-[shake_0.5s_ease-in-out]">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <?php echo csrfInput(); ?>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Username</label>
                <div class="relative">
                    <input type="text" name="username" class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:border-primary focus:bg-white focus:ring-4 focus:ring-indigo-500/10 transition-all duration-300" placeholder="Enter your username" required autocomplete="username">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 peer-focus:text-primary transition-colors duration-300"></i>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" class="w-full pl-11 pr-11 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:border-primary focus:bg-white focus:ring-4 focus:ring-indigo-500/10 transition-all duration-300" placeholder="Enter your password" required autocomplete="current-password">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 peer-focus:text-primary transition-colors duration-300"></i>
                    <i class="fas fa-eye absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-slate-600 transition-colors duration-300" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-semibold py-4 rounded-xl shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/30 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300 flex items-center justify-center gap-2">
                <span>Sign In</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="text-center mt-8 text-sm text-slate-400">
            &copy; <?php echo date('Y'); ?> GBPS Ali Bux Jarwar. All rights reserved.
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    </script>
    
    <style>
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</body>
</html>
