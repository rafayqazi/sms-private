<?php
// pages/update_required.php
require_once '../includes/functions.php';

// Start session to check status
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect back if no update is actually available
if (!isset($_SESSION['updates_available']) || $_SESSION['updates_available'] === false) {
    header("Location: ../index.php");
    exit();
}

$db = new Database();
$settings = $db->getSchoolSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Required - School Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: {
                        primary: '#4f46e5',
                        'primary-hover': '#4338ca',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .animate-float { animation: float 3s ease-in-out infinite; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .progress-bar-fill {
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4 font-sans overflow-hidden">
    
    <!-- Decorative Background -->
    <div class="absolute inset-0 z-0">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/20 rounded-full blur-[120px]"></div>
    </div>

    <div class="relative z-10 w-full max-w-2xl">
        <div class="glass-panel p-8 md:p-12 rounded-[3rem] shadow-2xl text-center">
            
            <!-- Icon/Logo -->
            <div class="mb-8 flex justify-center">
                <div class="relative">
                    <div class="w-24 h-24 bg-gradient-to-tr from-orange-400 to-red-600 rounded-[2rem] flex items-center justify-center text-white text-4xl shadow-xl animate-float">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-white rounded-full flex items-center justify-center text-orange-600 shadow-md">
                        <i class="fas fa-sync-alt fa-spin"></i>
                    </div>
                </div>
            </div>

            <h1 class="text-3xl md:text-4xl font-black text-slate-800 mb-4 leading-tight">
                Software Update Required
            </h1>
            
            <p class="text-slate-500 text-lg mb-8 font-medium">
                To ensure system security and optimal performance, you must update your software before proceeding. Access is temporarily locked.
            </p>

            <div class="bg-slate-50 border border-slate-100 p-6 rounded-3xl mb-10 text-left">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 flex-shrink-0">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-800 uppercase tracking-wider text-xs mb-1">Mandatory Security Patch</h3>
                        <p class="text-slate-500 text-sm">Please click the button below to automatically download and install the latest updates from the server.</p>
                    </div>
                </div>
            </div>

            <!-- Update Controls -->
            <div id="update-action">
                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                    <button id="btnPerformUpdate" class="group relative w-full bg-slate-900 overflow-hidden text-white font-black py-5 rounded-2xl shadow-2xl hover:shadow-indigo-500/30 active:scale-[0.98] transition-all">
                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative flex items-center justify-center gap-3">
                            <span class="uppercase tracking-[0.2em] text-lg">Update & Unlock Now</span>
                            <i class="fas fa-bolt text-xs animate-pulse"></i>
                        </div>
                    </button>
                    <p class="mt-4 text-[10px] text-slate-400 font-bold uppercase tracking-widest italic">
                        Estimated time: 30-60 seconds depending on internet speed
                    </p>
                <?php else: ?>
                    <div class="bg-orange-50 border border-orange-100 p-6 rounded-2xl text-orange-800">
                        <i class="fas fa-user-shield text-3xl mb-3"></i>
                        <p class="font-black uppercase tracking-tight text-sm">Action Required by Administrator</p>
                        <p class="text-xs opacity-80 mt-1">Please ask the system administrator to login and perform the mandatory update.</p>
                        <a href="../login.php" class="mt-4 inline-block font-black text-orange-600 hover:text-orange-700 underline uppercase tracking-widest text-[10px]">Back to Login Screen</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Progress Section (Hidden initially) -->
            <div id="update-progress" class="hidden">
                <div class="mb-6">
                    <div class="flex justify-between items-end mb-3">
                        <div class="text-left">
                            <h3 id="progress-status" class="font-black text-slate-800 uppercase tracking-wider text-xs">Initializing Update...</h3>
                            <p class="text-slate-400 text-[10px] font-bold">Please do not refresh or close this window.</p>
                        </div>
                        <span id="progress-percent" class="text-indigo-600 font-black text-xl">0%</span>
                    </div>
                    <div class="w-full bg-slate-100 h-4 rounded-full overflow-hidden border border-slate-200">
                        <div id="progress-fill" class="progress-bar-fill w-0 h-full bg-gradient-to-r from-indigo-500 to-purple-600"></div>
                    </div>
                </div>
            </div>

            <!-- Footer Branding -->
            <div class="mt-12 pt-8 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-slate-50 rounded-lg">
                        <img src="../assets/branding/logo.png" alt="Logo" class="w-6 h-6 object-contain">
                    </div>
                    <div class="text-left">
                        <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-tighter leading-none"><?php echo htmlspecialchars($settings['school_name']); ?></h4>
                        <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-1">Secure Environment</p>
                    </div>
                </div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">
                    Developed by <span class="text-indigo-500 font-black">Rafay Qazi</span>
                </div>
            </div>

        </div>
    </div>

    <script>
        const btnPerformUpdate = document.getElementById('btnPerformUpdate');
        const updateAction = document.getElementById('update-action');
        const updateProgress = document.getElementById('update-progress');
        const progressFill = document.getElementById('progress-fill');
        const progressPercent = document.getElementById('progress-percent');
        const progressStatus = document.getElementById('progress-status');

        if (btnPerformUpdate) {
            btnPerformUpdate.addEventListener('click', function() {
                // Confirm update
                if (!confirm('Are you sure you want to perform the mandatory system update? This will automatically download and install new files.')) return;

                // Show progress UI
                updateAction.classList.add('hidden');
                updateProgress.classList.remove('hidden');

                // Step 1: Initialize
                updateUI("Checking dependencies...", 10);
                
                setTimeout(() => {
                    updateUI("Creating safety backup...", 25);
                    
                    // Call the actual update API
                    performActualUpdate();
                }, 1500);
            });
        }

        function updateUI(status, percent) {
            progressStatus.innerText = status;
            progressFill.style.width = percent + '%';
            progressPercent.innerText = percent + '%';
        }

        function performActualUpdate() {
            fetch('../api/perform_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateUI("Applying changes...", 80);
                    setTimeout(() => {
                        updateUI("Clearing cache...", 95);
                        setTimeout(() => {
                            updateUI("Software Updated Successfully!", 100);
                            alert("Software has been updated and unlocked! The page will now reload.");
                            window.location.href = "../index.php";
                        }, 1000);
                    }, 1500);
                } else {
                    updateUI("Update Failed!", 0);
                    alert("Error: " + data.message);
                    updateAction.classList.remove('hidden');
                    updateProgress.classList.add('hidden');
                }
            })
            .catch(error => {
                updateUI("Network Error!", 0);
                alert("A network error occurred. Please check your internet connection and try again.");
                updateAction.classList.remove('hidden');
                updateProgress.classList.add('hidden');
            });
        }
    </script>
</body>
</html>
