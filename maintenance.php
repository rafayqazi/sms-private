<?php
session_start();
// We don't include db.php here to avoid circular redirect if db.php handles the check
// But we might need settings to show school name
$settingsFile = __DIR__ . '/data/settings.json';
$school_name = "School Management System";
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    $school_name = $settings['school_name'] ?? $school_name;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo $school_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .gear-rotate {
            animation: rotate 10s linear infinite;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-3xl w-full text-center">
        <!-- Maintenance Illustration -->
        <div class="relative mb-8 flex justify-center">
            <div class="w-48 h-48 bg-indigo-100 rounded-full flex items-center justify-center animate-float relative z-10 shadow-2xl">
                <i class="fas fa-tools text-7xl text-indigo-600"></i>
            </div>
            <div class="absolute -top-4 right-1/4 gear-rotate">
                <i class="fas fa-cog text-5xl text-indigo-300 opacity-50"></i>
            </div>
            <div class="absolute bottom-0 left-1/4 gear-rotate" style="animation-direction: reverse;">
                <i class="fas fa-cog text-3xl text-indigo-400 opacity-40"></i>
            </div>
        </div>

        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4 tracking-tight">
            Under <span class="text-indigo-600">Maintenance</span>
        </h1>
        <p class="text-lg text-gray-600 mb-10 max-w-lg mx-auto leading-relaxed">
            We are currently updating <strong><?php echo $school_name; ?></strong> to bring you a better experience. We'll be back online shortly!
        </p>

        <!-- Developer Info Card -->
        <div class="glass-card rounded-3xl p-8 shadow-xl mb-8 transform transition hover:scale-[1.02]">
            <div class="flex flex-col md:flex-row items-center gap-6">
                <div class="relative shrink-0">
                    <div class="w-24 h-24 rounded-2xl bg-indigo-600 flex items-center justify-center overflow-hidden border-4 border-white shadow-lg">
                         <img src="assets/img/developer.jpg?v=<?php echo time(); ?>" alt="Abdul Rafay Qazi" class="w-full h-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=Abdul+Rafay+Qazi&background=4F46E5&color=fff'">
                    </div>
                </div>
                <div class="text-center md:text-left flex-1">
                    <h3 class="text-xl font-bold text-gray-900">Developed By Abdul Rafay Qazi</h3>
                    <p class="text-indigo-600 font-medium text-sm mb-3">AR Software Solutions</p>
                    <div class="flex flex-wrap justify-center md:justify-start gap-4">
                        <a href="https://wa.me/923000358189" class="flex items-center gap-2 text-gray-600 hover:text-green-600 transition-colors">
                            <i class="fab fa-whatsapp text-lg"></i>
                            <span class="text-xs font-semibold">+92 300 0358189</span>
                        </a>
                        <a href="mailto:abdulrafehqazi@gmail.com" class="flex items-center gap-2 text-gray-600 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-envelope text-lg"></i>
                            <span class="text-xs font-semibold">Contact Support</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap justify-center gap-4">
            <button onclick="window.location.reload()" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-2xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 flex items-center gap-2">
                <i class="fas fa-sync-alt"></i> Try Refreshing
            </button>
            <?php 
            // Only show login or update options if admin_check is requested or user is already admin
            $isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
            
            if (!$isAdmin && isset($_GET['admin_check'])): ?>
                <a href="login.php" class="px-8 py-3 bg-white text-indigo-600 font-bold rounded-2xl border-2 border-indigo-50 hover:bg-indigo-50 transition-all">
                    Admin Login
                </a>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <div class="w-full mt-8">
                    <div class="bg-white/50 backdrop-blur rounded-3xl p-6 border border-indigo-100 shadow-sm inline-block min-w-[320px]">
                        <h4 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider flex items-center justify-center gap-2">
                            <i class="fas fa-sync-alt text-indigo-600"></i> Software Update Center
                        </h4>
                        
                        <div id="update-status-area" class="hidden mb-4">
                            <div class="flex flex-col items-center gap-2">
                                <span id="update-icon" class="text-2xl"></span>
                                <span id="update-message" class="font-bold text-gray-700 text-sm"></span>
                                <p id="update-details" class="text-[10px] text-gray-400 font-mono"></p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <button type="button" onclick="checkUpdates()" id="btn-check-updates" class="px-6 py-2 bg-white text-indigo-600 border border-indigo-200 font-bold rounded-xl hover:bg-indigo-50 transition-all text-sm flex items-center justify-center gap-2">
                                <i class="fas fa-satellite-dish"></i> Check Updates
                            </button>
                            
                            <button type="button" onclick="confirmUpdate()" id="btn-perform-update" class="hidden px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-md text-sm flex items-center justify-center gap-2">
                                <i class="fas fa-download"></i> Install Update
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modern Modal -->
                <div id="updateModal" class="fixed inset-0 bg-slate-900/80 z-[100] hidden flex items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden scale-95 opacity-0 transition-all duration-300 transform" id="updateModalContent">
                        <div class="bg-amber-50 p-5 border-b border-amber-100 flex items-center gap-3">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-amber-500 shadow-sm">
                                <i class="fas fa-shield-alt text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-800">System Update</h3>
                                <p class="text-amber-600 text-[10px] font-bold uppercase">Confirm Action</p>
                            </div>
                        </div>
                        
                        <div class="p-5 space-y-3">
                            <p class="text-slate-600 text-xs leading-relaxed font-medium">
                                Pull latest updates from GitHub. Local changes may be overwritten.
                            </p>
                        </div>
                        
                        <div class="p-4 bg-slate-50 flex justify-end gap-2 border-t border-slate-100">
                            <button onclick="closeUpdateModal()" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-bold text-xs transition-colors">Cancel</button>
                            <button onclick="executeUpdate()" id="btn-execute-update" class="px-4 py-2 bg-slate-900 text-white rounded-lg font-bold text-xs shadow-lg flex items-center gap-2">
                                <i class="fas fa-bolt text-amber-400"></i> Update
                            </button>
                        </div>
                    </div>
                </div>

                <script>
                function checkUpdates() {
                    const btn = document.getElementById('btn-check-updates');
                    const originalText = btn.innerHTML;
                    const statusArea = document.getElementById('update-status-area');
                    const icon = document.getElementById('update-icon');
                    const msg = document.getElementById('update-message');
                    const details = document.getElementById('update-details');
                    const updateBtn = document.getElementById('btn-perform-update');
                    
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Checking...';
                    statusArea.classList.add('hidden');
                    updateBtn.classList.add('hidden');

                    fetch('api/check_update.php')
                        .then(response => response.json())
                        .then(data => {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                            statusArea.classList.remove('hidden');
                            
                            if (data.update_available) {
                                icon.className = 'fas fa-gift text-emerald-500 animate-bounce';
                                msg.className = 'font-bold text-emerald-700 text-sm';
                                msg.textContent = 'New Update Available!';
                                details.textContent = data.message;
                                updateBtn.classList.remove('hidden');
                            } else {
                                icon.className = 'fas fa-check-circle text-sky-500';
                                msg.className = 'font-bold text-slate-700 text-sm';
                                msg.textContent = 'Already Up to Date';
                                details.textContent = 'Running latest version.';
                            }
                        })
                        .catch(err => {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                            statusArea.classList.remove('hidden');
                            icon.className = 'fas fa-wifi text-red-400';
                            msg.className = 'font-bold text-red-600 text-sm';
                            msg.textContent = 'Connection Failed';
                            details.textContent = 'Offline or server error.';
                        });
                }

                function confirmUpdate() {
                    const modal = document.getElementById('updateModal');
                    const content = document.getElementById('updateModalContent');
                    modal.classList.remove('hidden');
                    setTimeout(() => {
                        content.classList.remove('scale-95', 'opacity-0');
                        content.classList.add('scale-100', 'opacity-100');
                    }, 10);
                }

                function closeUpdateModal() {
                    const modal = document.getElementById('updateModal');
                    const content = document.getElementById('updateModalContent');
                    content.classList.remove('scale-100', 'opacity-100');
                    content.classList.add('scale-95', 'opacity-0');
                    setTimeout(() => modal.classList.add('hidden'), 300);
                }


                function executeUpdate() {
                    const btn = document.getElementById('btn-execute-update');
                    const originalText = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-cog fa-spin"></i> Backing up & Updating...';

                    // 1. Trigger Auto Backup Download First
                    const dlFrame = document.createElement('iframe');
                    dlFrame.style.display = 'none';
                    dlFrame.src = 'api/backup_data_auto.php'; // Path is relative to maintenance.php (root)
                    document.body.appendChild(dlFrame);

                    // 2. Small delay to allow browser to register download start before performance-heavy update
                    setTimeout(() => {
                        fetch('api/perform_update.php', { method: 'POST' })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    btn.className = "px-4 py-2 bg-emerald-600 text-white rounded-lg font-bold text-xs shadow-lg flex items-center gap-2";
                                    btn.innerHTML = '<i class="fas fa-check"></i> Success!';
                                    setTimeout(() => window.location.reload(), 1500);
                                } else {
                                    btn.className = "px-4 py-2 bg-red-600 text-white rounded-lg font-bold text-xs shadow-lg flex items-center gap-2";
                                    btn.innerHTML = '<i class="fas fa-times"></i> Error';
                                    alert('Error: ' + data.message);
                                    btn.disabled = false;
                                    btn.innerHTML = originalText; 
                                }
                            })
                            .catch(err => {
                                btn.disabled = false;
                                btn.innerHTML = originalText;
                                alert('Network Error.');
                            });
                    }, 2000);
                }
                </script>
            <?php endif; ?>
        </div>

        <p class="mt-12 text-gray-400 text-sm">
            &copy; <?php echo date('Y'); ?> <?php echo $school_name; ?>. All rights reserved.
        </p>
    </div>
</body>
</html>
