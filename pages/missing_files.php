<?php
/**
 * Critical Error Page - Displayed when required system files (.git) are missing.
 * Created by AR Software Solution
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Critical Error - Some Files Are Missing</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        }
        .vibrate {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
            transform: translate3d(0, 0, 0);
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-100 to-slate-200">
    
    <div class="max-w-2xl w-full">
        <!-- Main Error Card -->
        <div class="error-card rounded-3xl overflow-hidden vibrate">
            <!-- Header with Warning Icon -->
            <div class="bg-red-600 p-8 text-center relative">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative z-10">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 backdrop-blur-md border border-white/30">
                        <i class="fas fa-file-circle-exclamation text-white text-4xl animate-pulse"></i>
                    </div>
                    <h1 class="text-white text-3xl font-black tracking-tight mb-2">CRITICAL SYSTEM ERROR</h1>
                    <div class="h-1 w-20 bg-white/40 mx-auto rounded-full"></div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8 md:p-12 text-center">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-800 mb-4">Some files are missing!</h2>
                    <p class="text-slate-600 leading-relaxed text-lg">
                        The software cannot verify its integrity. It seems some critical system files have been deleted or corrupted. 
                        <strong>Please contact to developer</strong> immediately to restore your software.
                    </p>
                </div>

                <!-- Developer Contact Section -->
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 mb-8 text-left">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center flex-shrink-0">
                            <img src="../GBPS_LOGO.png" alt="AR Logo" class="w-10 h-10 object-contain">
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-xl tracking-tight">AR Software Solution</h3>
                            <p class="text-slate-500 font-medium">Excellence in Code & Design</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-100 shadow-sm">
                            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Developer</p>
                                <p class="text-slate-700 font-bold">Abdul Rafay Qazi</p>
                            </div>
                        </div>

                        <a href="https://wa.me/923000358189" target="_blank" class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-100 shadow-sm hover:border-green-500 transition-colors group">
                            <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div>
                                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">WhatsApp</p>
                                <p class="text-slate-700 font-bold">+92 300 0358189</p>
                            </div>
                        </a>
                    </div>

                    <!-- Social Media Links -->
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-4 px-1">Social Accounts</p>
                        <div class="flex flex-wrap gap-4">
                            <a href="https://web.facebook.com/rafeH.QAZI" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-full text-sm font-bold hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                <i class="fab fa-facebook"></i> Facebook
                            </a>
                            <a href="https://github.com/rafayqazi" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-full text-sm font-bold hover:bg-black transition-all shadow-sm">
                                <i class="fab fa-github"></i> GitHub
                            </a>
                            <a href="https://www.linkedin.com/in/abdulrafayqazi" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-bold hover:bg-blue-800 hover:text-white transition-all shadow-sm">
                                <i class="fab fa-linkedin"></i> LinkedIn
                            </a>
                            <a href="mailto:abdulrafehqazi@gmail.com" class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-full text-sm font-bold hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                <i class="fas fa-envelope"></i> Email
                            </a>
                        </div>
                    </div>
                </div>

                <p class="text-slate-400 text-xs text-center">
                    &copy; <?php echo date('Y'); ?> AR Software Solution. All rights reserved. <br>
                    Hardware ID Reference: <?php echo hash('crc32', php_uname('n')); ?>
                </p>
            </div>
        </div>
    </div>

</body>
</html>
