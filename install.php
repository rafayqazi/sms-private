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

    <div class="w-full max-w-2xl glass rounded-[2.5rem] shadow-2xl border border-white/20 overflow-hidden relative z-10">
        <!-- Progress Bar -->
        <div class="h-1.5 w-full bg-slate-100 flex">
            <div id="progress-bar" class="h-full bg-primary step-transition" style="width: 20%"></div>
        </div>

        <div class="p-8 md:p-12">
            <!-- Header -->
            <div class="text-center mb-10">
                <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mx-auto mb-4 shadow-inner">
                    <i class="fas fa-magic text-2xl"></i>
                </div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight">Installation Wizard</h1>
                <p class="text-slate-500 font-medium text-sm mt-1 uppercase tracking-widest" id="step-title">Welcome & Restore</p>
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
                                <input type="file" id="backup_zip" class="hidden" accept=".zip" onchange="handleRestoreSelect(this)">
                                <div onclick="document.getElementById('backup_zip').click()" class="p-6 border-2 border-dashed border-slate-200 rounded-3xl group-hover:border-primary group-hover:bg-primary/5 transition-all h-full flex flex-col items-center justify-center gap-3 cursor-pointer">
                                    <i class="fas fa-file-zipper text-3xl text-slate-400 group-hover:text-primary transition-colors"></i>
                                    <span class="font-bold text-slate-700 block" id="restore-text">Restore Backup</span>
                                    <span class="text-[10px] text-slate-400 uppercase font-black" id="restore-subtext">Upload ZIP File</span>
                                </div>
                                
                                <!-- Confirm Restore Button (Hidden initially) -->
                                <button id="btn-confirm-restore" onclick="executeRestore()" class="hidden absolute -bottom-4 left-1/2 -translate-x-1/2 bg-secondary text-white text-[10px] font-black px-4 py-2 rounded-full shadow-lg hover:bg-emerald-600 transition-all uppercase tracking-widest whitespace-nowrap">
                                    Confirm & Upload
                                </button>
                            </div>
                            
                            <button onclick="nextStep()" class="p-6 border-2 border-slate-200 rounded-3xl hover:border-primary hover:bg-primary/5 transition-all text-center flex flex-col items-center justify-center gap-3 group">
                                <i class="fas fa-sparkles text-3xl text-slate-400 group-hover:text-primary transition-colors"></i>
                                <span class="font-bold text-slate-700">Fresh Install</span>
                                <span class="text-[10px] text-slate-400 uppercase font-black">recommended</span>
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

                        <button type="submit" class="w-full bg-primary hover:bg-indigo-700 text-white font-black py-5 rounded-2xl shadow-xl shadow-primary/20 transition-all flex items-center justify-center gap-3 mt-8">
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

                        <button type="submit" class="w-full bg-slate-900 hover:bg-black text-white font-black py-5 rounded-2xl shadow-xl transition-all flex items-center justify-center gap-3">
                            Bind & Activate <i class="fas fa-key"></i>
                        </button>
                    </form>
                </div>

                <!-- Step 4: School Config -->
                <div id="step-4" class="step-content hidden animate-fade">
                    <form id="form-step-4" onsubmit="saveSettings(event)" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">School Name</label>
                                <input type="text" id="sch-name" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="e.g. GBPS Ali Bux Jarwar">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">SEMIS Code</label>
                                <input type="text" id="sch-semis" class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="e.g. 424010147">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">HM Name</label>
                                <input type="text" id="sch-hm" required class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="Signature name">
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">School Address</label>
                                <input type="text" id="sch-addr" class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="District & Taluka">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-indigo-700 text-white font-black py-5 rounded-2xl shadow-xl transition-all flex items-center justify-center gap-3 mt-6">
                            Next Stage <i class="fas fa-chevron-right"></i>
                        </button>
                    </form>
                </div>

                <!-- Step 5: Admin User -->
                <div id="step-5" class="step-content hidden animate-fade">
                    <form id="form-step-5" onsubmit="createAdmin(event)" class="space-y-6">
                        <p class="text-sm text-slate-500 text-center">Finally, create your primary administrator account.</p>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Admin Username</label>
                                <input type="text" id="adm-user" required class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="Login name">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2 pl-1">Admin Password</label>
                                <input type="password" id="adm-pass" required class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary text-slate-800 font-bold" placeholder="Min 4 characters">
                            </div>
                        </div>
                        
                        <div id="error-step-5" class="hidden text-red-500 text-xs font-bold text-center bg-red-50 py-3 rounded-xl border border-red-100"></div>

                        <button type="submit" class="w-full bg-secondary hover:bg-emerald-600 text-white font-black py-5 rounded-2xl shadow-xl shadow-secondary/20 transition-all flex items-center justify-center gap-3 mt-8">
                            Complete Setup <i class="fas fa-check-double"></i>
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
                    <span class="text-xs font-bold text-slate-700">Abdul Rafay Jarwar</span>
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
        const totalSteps = 5;
        const titles = [
            "Welcome & Restore",
            "Developer Verification",
            "Machine Licensing",
            "School Configuration",
            "Admin Management"
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
            bar.style.width = (currentStep / totalSteps * 100) + '%';
            title.innerText = titles[currentStep - 1];
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
            const mac = document.getElementById('mac-address').value;
            const fd = new FormData();
            fd.append('mac_address', mac);
            
            const res = await fetch('api/installer_actions.php?action=activate_license', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) nextStep();
            else alert(data.message);
        }

        // Logic for Step 4: Settings
        async function saveSettings(e) {
            e.preventDefault();
            const fd = new FormData();
            fd.append('school_name', document.getElementById('sch-name').value);
            fd.append('semis_code', document.getElementById('sch-semis').value);
            fd.append('headmaster_name', document.getElementById('sch-hm').value);
            fd.append('address_tagline', document.getElementById('sch-addr').value);

            const res = await fetch('api/installer_actions.php?action=save_settings', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) nextStep();
            else alert(data.message);
        }

        // Logic for Step 5: Admin Account
        async function createAdmin(e) {
            e.preventDefault();
            const err = document.getElementById('error-step-5');
            const fd = new FormData();
            fd.append('username', document.getElementById('adm-user').value);
            fd.append('password', document.getElementById('adm-pass').value);

            const res = await fetch('api/installer_actions.php?action=create_admin', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                window.location.href = 'login.php?installed=true';
            } else {
                err.innerText = data.message;
                err.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
