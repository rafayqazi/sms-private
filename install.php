<?php
/**
 * SMS Installation Wizard
 * Premium Multi-Step Interface
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Wizard - SMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: { primary: '#4f46e5', secondary: '#10b981' }
                }
            }
        }
    </script>
    <style>
        .step-transition { transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade { animation: fadeIn 0.5s ease-out forwards; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 min-h-screen flex items-center justify-center p-4">

    <!-- Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-secondary/20 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-xl mx-auto bg-white rounded-[3rem] shadow-[0_20px_70px_rgba(0,0,0,0.08)] overflow-hidden border border-slate-100 animate-slideUp">
        <div class="px-10 pt-10 pb-6">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center text-white shadow-lg shadow-primary/20">
                        <i class="fas fa-rocket text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 tracking-tight">Installation Wizard</h2>
                        <p class="text-[10px] font-black text-primary uppercase tracking-[0.2em] opacity-70">Setup your digital hub</p>
                    </div>
                </div>
                <div class="text-right">
                    <span id="step-indicator" class="text-3xl font-black text-slate-200 block leading-none">01</span>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Stage</span>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="h-1.5 w-full bg-slate-100 rounded-full mb-8 overflow-hidden">
                <div id="progress-bar" class="h-full bg-gradient-to-r from-primary to-emerald-500 transition-all duration-500 rounded-full" style="width: 25%"></div>
            </div>

            <div id="step-title" class="text-sm font-black text-slate-400 uppercase tracking-widest mb-8 text-center bg-slate-50 py-3 rounded-2xl border border-slate-100">
                Welcome & Restore
            </div>            
            <!-- Steps Container -->
            <div id="steps-container" class="min-h-[350px]">
                
                <!-- Step 1: Welcome & Restore -->
                <div id="step-1" class="step-content animate-fade">
                    <div class="space-y-6 text-center">
                        <p class="text-slate-600 leading-relaxed">
                            Welcome to your new School Management System. Would you like to restore from a previous backup or start a fresh installation?
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4">
                            <div class="group relative">
                                <input type="file" id="backup_zip" class="hidden" accept=".zip" oninput="handleRestoreSelect(this)">
                                <div onclick="document.getElementById('backup_zip').click()" class="p-6 border-2 border-dashed border-slate-200 bg-white rounded-3xl group-hover:border-primary group-hover:bg-primary/5 transition-all h-full flex flex-col items-center justify-center gap-3 cursor-pointer">
                                    <i class="fas fa-file-zipper text-3xl text-slate-400 group-hover:text-primary transition-all duration-300"></i>
                                    <span class="font-bold text-slate-700 block" id="restore-text">Restore Backup</span>
                                    <span class="text-[10px] text-slate-500 uppercase font-black" id="restore-subtext">Upload ZIP File</span>
                                </div>
                                
                                <!-- Confirm Restore Button (Hidden initially) -->
                                <button id="btn-confirm-restore" type="button" onclick="executeRestore()" class="hidden absolute -bottom-4 left-1/2 -translate-x-1/2 bg-secondary text-white text-[10px] font-black px-4 py-2 rounded-full shadow-lg hover:bg-emerald-600 transition-all uppercase tracking-widest whitespace-nowrap z-20">
                                    Confirm & Upload
                                </button>
                            </div>
                            
                            <button type="button" onclick="nextStep()" class="w-full p-6 border-2 border-slate-200 bg-white rounded-3xl hover:border-primary hover:bg-primary/5 transition-all text-center flex flex-col items-center justify-center gap-3 cursor-pointer group">
                                <i class="fas fa-sparkles text-3xl text-slate-400 group-hover:text-primary transition-all duration-300"></i>
                                <span class="font-bold text-slate-700">Fresh Install</span>
                                <span class="text-[10px] text-indigo-500 uppercase font-black">recommended</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Super User Auth -->
                <div id="step-2" class="step-content hidden animate-fade">
                    <form id="form-step-2" onsubmit="verifySuperUser(event)" class="space-y-6">
                        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 flex gap-3 mb-6">
                            <i class="fas fa-user-shield text-amber-500 mt-1"></i>
                            <p class="text-xs text-amber-800 font-medium">Please enter the developer's super user credentials to authorize this installation.</p>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Super Username</label>
                                <input type="text" id="su-user" required class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="e.g. abdul rafay">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Super Password</label>
                                <input type="password" id="su-pass" required class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="••••••••">
                            </div>
                        </div>
                        
                        <div id="error-step-2" class="hidden text-red-500 text-xs font-bold text-center bg-red-50 py-3 rounded-xl border border-red-100 animate-pulse"></div>

                        <button type="submit" class="w-full bg-primary hover:bg-indigo-700 text-white font-black py-5 rounded-2xl shadow-xl shadow-primary/20 transition-all flex items-center justify-center gap-3 mt-8 cursor-pointer active:scale-[0.98]">
                            Verify Developer <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <!-- Step 3: Licensing -->
                <div id="step-3" class="step-content hidden animate-fade">
                    <form id="form-step-3" onsubmit="activateLicense(event)" class="space-y-6">
                        <div class="text-center space-y-4 mb-8">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto border border-slate-100">
                                <i class="fas fa-microchip text-slate-400"></i>
                            </div>
                            <p class="text-slate-600 text-sm">Binding software to this machine's network adapter.</p>
                        </div>
                        
                        <div class="space-y-4">
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Target MAC Address</label>
                            <input type="text" id="mac-address" required readonly class="w-full px-5 py-4 bg-slate-100 border-none rounded-2xl text-slate-500 font-mono font-bold uppercase cursor-not-allowed" value="FETCHING...">
                        </div>

                        <button type="submit" class="w-full bg-slate-900 hover:bg-black text-white font-black py-5 rounded-2xl shadow-xl transition-all flex items-center justify-center gap-3 cursor-pointer active:scale-[0.98]">
                            Bind & Activate <i class="fas fa-key"></i>
                        </button>
                    </form>
                </div>

                <!-- Step 4: System Setup & Admin -->
                <div id="step-4" class="step-content hidden animate-fade">
                    <form id="form-step-4" onsubmit="finalizeSetup(event)" class="space-y-6">
                        
                        <!-- Section: School Info -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-6 h-6 bg-primary/10 rounded-lg flex items-center justify-center text-primary text-[10px]">
                                    <i class="fas fa-school"></i>
                                </div>
                                <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">School Settings</h4>
                            </div>

                            <!-- Logo Upload -->
                            <div class="flex flex-col items-center p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                                <div class="relative group mb-4">
                                    <input type="file" id="sch-logo" name="school_logo" accept="image/*" class="hidden" onchange="previewLogo(this)">
                                    <div onclick="document.getElementById('sch-logo').click()" class="w-32 h-32 rounded-[2rem] border-4 border-white bg-white flex items-center justify-center cursor-pointer shadow-xl shadow-slate-200/50 hover:scale-105 transition-all overflow-hidden relative group">
                                        <img id="logo-preview" src="" class="hidden w-full h-full object-cover">
                                        <div id="logo-placeholder" class="text-center">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-primary mb-2"></i>
                                            <span class="block text-[10px] text-slate-400 font-bold uppercase">Logo</span>
                                        </div>
                                        <div class="absolute inset-0 bg-primary/80 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-black uppercase">
                                            Update Logo
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <button type="button" onclick="document.getElementById('sch-logo').click()" class="text-[10px] font-black text-primary uppercase tracking-widest bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm hover:bg-primary hover:text-white hover:border-primary transition-all">
                                        Select School Logo
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-1">School Name</label>
                                    <input type="text" id="sch-name" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="e.g. GBPS Ali Bux Jarwar">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-1">SEMIS Code</label>
                                    <input type="text" id="sch-semis" class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="Optional">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-1">PRINCIPAL Name</label>
                                    <input type="text" id="sch-hm" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="PRINCIPAL Name">
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-1">School Address</label>
                                    <input type="text" id="sch-addr" class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="City / District">
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100">

                        <!-- Section: Admin User -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-6 h-6 bg-secondary/10 rounded-lg flex items-center justify-center text-secondary text-[10px]">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Admin Account Creation</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-1">Admin Username</label>
                                    <input type="text" id="adm-user" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="e.g. admin">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-1">Admin Password</label>
                                    <input type="password" id="adm-pass" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="Min 4 chars">
                                </div>
                            </div>
                        </div>

                        <div id="error-step-4" class="hidden text-red-500 text-[10px] font-black text-center bg-red-50 py-3 rounded-2xl border border-red-100 uppercase tracking-widest"></div>

                        <button type="submit" class="w-full bg-secondary hover:bg-emerald-600 text-white font-black py-5 rounded-[2rem] shadow-xl shadow-secondary/20 transition-all flex items-center justify-center gap-3 mt-4 cursor-pointer active:scale-[0.98]">
                            Complete Setup <i class="fas fa-check-double rotate-3 group-hover:rotate-0 transition-transform"></i>
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <!-- Developer Info Footer -->
        <div class="bg-slate-50 border-t border-slate-100 py-4 px-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                    <i class="fas fa-code text-xs"></i>
                </div>
                <div>
                    <span class="block text-[10px] font-black text-slate-400 uppercase tracking-tighter leading-none">Developed by</span>
                    <span class="text-xs font-bold text-slate-700">Abdul Rafay</span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-slate-400">
                <a href="#" class="hover:text-primary transition-colors"><i class="fab fa-whatsapp"></i></a>
                <a href="#" class="hover:text-primary transition-colors"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
    </div>

    <!-- Hidden Restore Form -->
    <form id="restore-form" style="display:none">
        <input type="file" name="backup_file" id="real-restore-input">
    </form>

    <script>
        let currentStep = 1;
        const totalSteps = 4;
        const titles = [
            "Welcome & Restore",
            "Developer Verification",
            "Machine Licensing",
            "System Setup & Admin"
        ];

        function nextStep() {
            if (currentStep < totalSteps) {
                document.getElementById(`step-${currentStep}`).classList.add('hidden');
                currentStep++;
                document.getElementById(`step-${currentStep}`).classList.remove('hidden');
                updateProgress();
                
                // Special handling for Step 3: Fetch MAC
                if (currentStep === 3) fetchMacAddress();
            }
        }

        function updateProgress() {
            const bar = document.getElementById('progress-bar');
            const title = document.getElementById('step-title');
            const indicator = document.getElementById('step-indicator');
            bar.style.width = (currentStep / totalSteps * 100) + '%';
            title.innerText = titles[currentStep - 1];
            indicator.innerText = currentStep.toString().padStart(2, '0');
        }

        // Logic for Step 1: Restore
        let selectedRestoreFile = null;

        function handleRestoreSelect(input) {
            if (input.files && input.files[0]) {
                selectedRestoreFile = input.files[0];
                document.getElementById('restore-text').innerText = "Selected File";
                document.getElementById('restore-subtext').innerText = selectedRestoreFile.name;
                document.getElementById('btn-confirm-restore').classList.remove('hidden');
            }
        }

        async function executeRestore() {
            if (!selectedRestoreFile) return;

            const btn = document.getElementById('btn-confirm-restore');
            const text = document.getElementById('restore-text');
            const subtext = document.getElementById('restore-subtext');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            text.innerText = "Restoring Data...";
            subtext.innerText = "Please wait, do not close browser.";

            const fd = new FormData();
            fd.append('backup_file', selectedRestoreFile);
            fd.append('username', 'abdul rafay');
            fd.append('password', 'khuljasimsim'); // Super user pass as fallback for installer restore
            
            try {
                const res = await fetch('api/restore_data.php', { method: 'POST', body: fd });
                const textResponse = await res.text();
                let data;
                try {
                    data = JSON.parse(textResponse);
                } catch(e) {
                    console.error("Server returned non-JSON:", textResponse);
                    throw new Error("Invalid server response format");
                }
                
                if (data.success) {
                    text.className = "font-bold text-secondary";
                    text.innerText = "Restore Success!";
                    subtext.innerText = "Redirecting to Dashboard...";
                    setTimeout(() => window.location.href = 'index.php', 1500);
                } else {
                    text.className = "font-bold text-red-500";
                    text.innerText = "Restore Failed";
                    subtext.innerText = data.message;
                    btn.disabled = false;
                    btn.innerHTML = 'Try Again';
                }
            } catch(e) {
                console.error("Restore Error:", e);
                text.className = "font-bold text-red-500";
                text.innerText = "System Error";
                subtext.innerText = "The server failed to process the request. Check PHP logs.";
                btn.disabled = false;
                btn.innerHTML = 'Confirm & Upload';
            }
        }

        // Logic for Step 2: Super User Auth
        async function verifySuperUser(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const err = document.getElementById('error-step-2');
            
            err.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';

            const fd = new FormData();
            fd.append('username', document.getElementById('su-user').value);
            fd.append('password', document.getElementById('su-pass').value);

            try {
                const res = await fetch('api/installer_actions.php?action=verify_superuser', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) nextStep();
                else {
                    err.innerText = data.message;
                    err.classList.remove('hidden');
                }
            } catch(e) { err.innerText = "Connection lost."; err.classList.remove('hidden'); }
            
            btn.disabled = false;
            btn.innerHTML = 'Verify Developer <i class="fas fa-arrow-right"></i>';
        }

        // Logic for Step 3: Licensing
        function fetchMacAddress() {
            // Usually we'd have an API to get current MAC
            // For now, let's call a minimal API to get machine info
            fetch('api/get_machine_info.php')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('mac-address').value = data.mac || 'UNKNOWN-MAC';
                });
        }

        async function activateLicense(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalHtml = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Activating...';
            
            const mac = document.getElementById('mac-address').value;
            const fd = new FormData();
            fd.append('mac_address', mac);
            
            try {
                const res = await fetch('api/installer_actions.php?action=activate_license', { method: 'POST', body: fd });
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch(e) {
                    throw new Error("Invalid server response: " + text.substring(0, 100));
                }
                
                if (data.success) {
                    nextStep();
                } else {
                    alert("Activation Error: " + (data.message || "Unknown error"));
                }
            } catch(e) {
                console.error("Activation Error:", e);
                alert("Network or System Error: " + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

        // Preview Logo
        function previewLogo(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('logo-preview');
                    const placeholder = document.getElementById('logo-placeholder');
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Logic for Final Step: School Settings & Admin
        async function finalizeSetup(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalHtml = btn.innerHTML;
            const errorDiv = document.getElementById('error-step-4');
            
            errorDiv.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizing Settings...';

            // 1. Save Settings (including Logo)
            const fd = new FormData();
            fd.append('school_name', document.getElementById('sch-name').value);
            fd.append('semis_code', document.getElementById('sch-semis').value);
            fd.append('headmaster_name', document.getElementById('sch-hm').value);
            fd.append('address_tagline', document.getElementById('sch-addr').value);
            
            const logoFile = document.getElementById('sch-logo').files[0];
            if (logoFile) {
                fd.append('school_logo', logoFile);
            }

            try {
                // First call: Save Settings
                const res1 = await fetch('api/installer_actions.php?action=save_settings', { method: 'POST', body: fd });
                const data1 = await res1.json();
                
                if (!data1.success) {
                    throw new Error(data1.message || "Failed to save school settings.");
                }

                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Admin Account...';

                // Second call: Create Admin
                const adFd = new FormData();
                adFd.append('username', document.getElementById('adm-user').value);
                adFd.append('password', document.getElementById('adm-pass').value);

                const res2 = await fetch('api/installer_actions.php?action=create_admin', { method: 'POST', body: adFd });
                const data2 = await res2.json();

                if (!data2.success) {
                    throw new Error(data2.message || "Failed to create admin account.");
                }

                // Success!
                window.location.href = 'index.php?setup_complete=true';

            } catch(e) {
                errorDiv.innerText = e.message;
                errorDiv.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    </script>
</body>
</html>
