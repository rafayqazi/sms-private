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
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-0 overflow-hidden font-sans">
    <div class="flex w-full h-screen overflow-hidden">
        
        <!-- Left Side: Animation (60%) -->
        <div class="hidden lg:flex lg:w-3/5 bg-slate-900 relative items-center justify-center overflow-hidden">
            <canvas id="canvas" class="z-10"></canvas>
            
            <!-- Branding Overlay -->
            <div class="absolute bottom-12 left-12 z-20 animate-fade">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-white/5 backdrop-blur-md rounded-2xl border border-white/10 shadow-2xl">
                        <img src="assets/branding/logo.png?v=<?php echo time(); ?>" alt="Logo" class="w-12 h-12 object-contain">
                    </div>
                    <div>
                        <h2 class="text-white text-xl font-black tracking-tight leading-none"><?php echo htmlspecialchars($settings['school_name']); ?></h2>
                        <p class="text-indigo-400 text-xs font-black uppercase tracking-[0.3em] mt-2 opacity-70">Management System</p>
                    </div>
                </div>
            </div>

            <!-- Floating Tech Dots BG -->
            <div class="absolute inset-0 z-0 pointer-events-none opacity-20">
                <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/20 rounded-full blur-[120px]"></div>
                <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/20 rounded-full blur-[120px]"></div>
            </div>
        </div>

        <!-- Right Side: Login Form (40% or 100% on mobile) -->
        <div class="w-full lg:w-2/5 flex items-center justify-center bg-white p-8 md:p-12 relative overflow-y-auto">
            <div class="w-full max-w-sm animate-slideUp">
                
                <!-- School Branding (Visible on all devices) -->
                <div class="mb-10 text-center flex flex-col items-center">
                    <div class="mb-6 flex flex-col items-center">
                        <div class="w-24 h-24 bg-white p-4 rounded-full shadow-lg border border-slate-100 mb-4 flex items-center justify-center">
                            <img src="assets/branding/logo.png?v=<?php echo time(); ?>" alt="Logo" class="w-full h-full object-contain">
                        </div>
                        <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-tight uppercase mb-2">
                            <?php echo htmlspecialchars($settings['school_name']); ?>
                        </h1>
                        <?php if (!empty($settings['address_tagline'])): ?>
                            <p class="text-sm font-bold text-slate-500 uppercase tracking-widest px-8">
                                <?php echo htmlspecialchars($settings['address_tagline']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="w-16 h-1 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full mb-8"></div>

                    <div class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.4em] mb-2 pl-1">
                        <?php 
                            $h = date('H');
                            echo ($h < 12) ? 'Good Morning' : 'Good Evening';
                        ?>
                    </div>
                    <h2 class="text-4xl font-black text-slate-900 tracking-tighter mb-2">Secure Login</h2>
                    <p class="text-slate-500 font-medium">Authorized personnel only. Please verify your identity.</p>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-100 text-red-600 p-4 rounded-2xl mb-8 flex items-center gap-3 text-sm animate-shake">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                        <span class="font-black uppercase tracking-tight"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['update']) && $_GET['update'] === 'success'): ?>
                    <div class="bg-emerald-50 border border-emerald-100 text-emerald-600 p-4 rounded-2xl mb-8 flex items-center gap-3 text-sm animate-fade">
                        <i class="fas fa-check-circle text-lg"></i>
                        <span class="font-black uppercase tracking-tight">Software Updated Successfully! Please login.</span>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-6">
                    <?php echo csrfInput(); ?>
                    
                    <div class="group">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1 group-focus-within:text-indigo-600 transition-colors">Access Username</label>
                        <div class="relative">
                            <input type="text" name="username" required 
                                class="w-full px-6 py-4 bg-slate-50 border-2 border-slate-100 focus:border-indigo-600 focus:bg-white rounded-2xl text-slate-800 font-bold placeholder-slate-300 transition-all outline-none shadow-sm focus:shadow-indigo-500/10" 
                                placeholder="Username">
                            <i class="fas fa-id-badge absolute right-6 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-indigo-600 transition-colors"></i>
                        </div>
                    </div>

                    <div class="group">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-1 group-focus-within:text-indigo-600 transition-colors">Security Token</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required 
                                class="w-full px-6 py-4 bg-slate-50 border-2 border-slate-100 focus:border-indigo-600 focus:bg-white rounded-2xl text-slate-800 font-bold placeholder-slate-300 transition-all outline-none shadow-sm focus:shadow-indigo-500/10" 
                                placeholder="••••••••">
                            <button type="button" id="togglePassword" class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-300 hover:text-indigo-600 transition-colors">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>


                    <button type="submit" class="group relative w-full bg-slate-900 overflow-hidden text-white font-black py-5 rounded-2xl shadow-2xl shadow-slate-200/50 hover:shadow-indigo-500/30 active:scale-[0.98] transition-all mt-4">
                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative flex items-center justify-center gap-3">
                            <span class="uppercase tracking-[0.2em]">Authorize Access</span>
                            <i class="fas fa-shield-alt text-xs animate-pulse"></i>
                        </div>
                    </button>
                </form>

                <div class="mt-12 text-center text-[10px] uppercase tracking-widest">
                    <div class="font-black text-slate-900 mb-1">
                        &copy; <?php echo date('Y'); ?> System Secure Build &bull; v2.0
                    </div>
                    <div class="font-bold text-slate-400">
                        Developed by <span class="text-indigo-400">Rafay Qazi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Particle Animation Logic -->
    <script>
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d', { alpha: false });
        let particles = [];
        let mouse = { x: null, y: null, radius: 150 };

        window.addEventListener('mousemove', (e) => {
            let rect = canvas.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        });

        class Particle {
            constructor(x, y) {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.destX = x;
                this.destY = y;
                this.vx = (Math.random() - 0.5) * 4;
                this.vy = (Math.random() - 0.5) * 4;
                this.accX = 0;
                this.accY = 0;
                this.friction = 0.92;
                this.size = Math.random() * 2 + 1;
                this.color = `rgba(99, 102, 241, ${Math.random() * 0.5 + 0.5})`;
            }

            draw() {
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }

            update() {
                let dx = mouse.x - this.x;
                let dy = mouse.y - this.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < mouse.radius) {
                    let force = (mouse.radius - distance) / mouse.radius;
                    let dirX = dx / distance;
                    let dirY = dy / distance;
                    this.accX -= dirX * force * 4;
                    this.accY -= dirY * force * 4;
                }

                this.accX += (this.destX - this.x) * 0.008;
                this.accY += (this.destY - this.y) * 0.008;

                this.vx += this.accX;
                this.vy += this.accY;
                this.vx *= this.friction;
                this.vy *= this.friction;

                this.x += this.vx;
                this.y += this.vy;

                this.accX = 0;
                this.accY = 0;
            }
        }

        function init() {
            canvas.width = canvas.parentElement.clientWidth;
            canvas.height = canvas.parentElement.clientHeight;
            particles = [];

            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2 + 50; // Lifted slightly
            const scale = 1.35; // Reduced scale for a better fit
            const step = 7;

            function addCenteredRect(cx, cy, w, h, s, color) {
                for (let i = cx - w/2; i < cx + w/2; i += s) {
                    for (let j = cy - h/2; j < cy + h/2; j += s) {
                        particles.push(new Particle(centerX + i * scale, centerY + j * scale, color));
                    }
                }
            }

            // Colors (Vibrant & Professional)
            const cBody = '#f59e0b';  // Amber
            const cRoof = '#ef4444';  // Red
            const cWin  = '#ffffff';  // White
            const cDoor = '#78350f';  // Deep Brown
            const cPole = '#94a3b8';  // Slate

            // 1. Wings (Side Buildings)
            addCenteredRect(-120, 50, 180, 180, step, cBody); 
            addCenteredRect(120, 50, 180, 180, step, cBody);  

            // 2. Central Block
            addCenteredRect(0, 30, 120, 220, step, '#fbbf24'); 

            // 3. Roofs (Detailed Points)
            // Left & Right Roofs
            for (let side = -1; side <= 1; side += 2) {
                if (side === 0) continue;
                for (let i = -100; i <= 100; i += 7) {
                    let roofBaseY = -40;
                    let peakY = -100;
                    let slope = (100 - Math.abs(i)) * 0.4;
                    for (let j = roofBaseY - slope; j < roofBaseY; j += 7) {
                        particles.push(new Particle(centerX + (side * 120 + i) * scale, centerY + j * scale, cRoof));
                    }
                }
            }
            // Central Peak
            for (let i = -70; i <= 70; i += 6) {
                let roofBaseY = -80;
                let slope = (70 - Math.abs(i)) * 1.5;
                for (let j = roofBaseY - slope; j < roofBaseY; j += 6) {
                    particles.push(new Particle(centerX + i * scale, centerY + j * scale, cRoof));
                }
            }

            // 4. Windows (The Grid)
            const winRows = [-10, 50, 110];
            const winCols = [-175, -115, -65, 65, 115, 175];
            winRows.forEach(ry => {
                winCols.forEach(cx => {
                    addCenteredRect(cx, ry, 30, 35, 4, cWin);
                });
            });

            // 5. Door & Clock
            addCenteredRect(0, 100, 60, 85, 4, cDoor); // Main Entrance
            // Clock Circle
            for (let a = 0; a < Math.PI * 2; a += 0.2) {
                for (let r = 0; r < 35; r += 5) {
                    particles.push(new Particle(centerX + Math.cos(a)*r*scale, centerY + (Math.sin(a)*r - 100)*scale, cWin));
                }
            }

            // 6. Flagpole & Flag
            addCenteredRect(0, -210, 6, 60, 5, cPole);
            for (let i = 0; i < 65; i += 6) {
                for (let j = -245; j < -215; j += 6) {
                    particles.push(new Particle(centerX + i * scale, centerY + (j + Math.sin(i*0.2)*5) * scale, cRoof));
                }
            }
        }

        function animate() {
            ctx.fillStyle = '#0f172a'; 
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();
            }
            requestAnimationFrame(animate);
        }

        window.addEventListener('resize', init);
        init();
        animate();

        // Standard Login Scripts
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    </script>
    
    <style>
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes fade {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }
        .animate-fade { animation: fade 1.5s ease-out; }
        .animate-slideUp { animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
        .animate-shake { animation: shake 0.4s ease-in-out; }
        
        /* Custom scrollbar for form side if needed */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</body>
</html>
