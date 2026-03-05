<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Access control
if (!isAdmin() && !isSuperAdmin()) {
    header("Location: ../index.php");
    exit;
}

$db = new Database();
$schoolSettings = $db->getSchoolSettings();
?>

<?php include '../includes/header.php'; ?>

<div class="max-w-6xl mx-auto px-4 py-8 h-full">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 p-8 mb-8 flex flex-col md:flex-row justify-between items-center gap-6">
        <div>
            <h1 class="text-3xl font-black text-gray-800 dark:text-gray-100 tracking-tight">System Maintenance</h1>
            <div class="flex items-center gap-2 mt-2">
                <span class="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
                <p class="text-gray-500 dark:text-gray-400 font-bold text-xs uppercase tracking-widest">Database Management</p>
            </div>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-900/30 px-6 py-4 rounded-2xl border border-emerald-100 dark:border-emerald-800 flex items-center gap-4">
            <div class="text-right">
                <div class="text-sm font-bold text-emerald-800 dark:text-emerald-400 capitalize"><?php echo date('l, d M'); ?></div>
                <div class="text-xs text-emerald-600 dark:text-emerald-500 font-medium"><?php echo date('Y'); ?></div>
            </div>
            <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Backup Section -->
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-800 p-10 flex flex-col items-center text-center group">
            <div class="w-20 h-20 bg-emerald-100 dark:bg-emerald-900/50 rounded-2xl flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-6 group-hover:scale-110 transition-transform shadow-inner">
                <i class="fas fa-cloud-download text-3xl"></i>
            </div>
            <h2 class="text-2xl font-black text-gray-800 dark:text-gray-100 mb-3">Backup Data</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-8 leading-relaxed">
                Securely export all your system data into a compressed ZIP file for safekeeping.
            </p>
            <button id="backupBtn" onclick="runBackup()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-5 rounded-2xl font-black shadow-lg hover:shadow-emerald-500/30 transition-all flex items-center justify-center gap-3">
                <i class="fas fa-download"></i> Download Backup
            </button>
        </div>

        <!-- Restore Section -->
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-800 p-10 flex flex-col items-center text-center group">
            <div class="w-20 h-20 bg-orange-100 dark:bg-orange-900/50 rounded-2xl flex items-center justify-center text-orange-600 dark:text-orange-400 mb-6 group-hover:scale-110 transition-transform shadow-inner">
                <i class="fas fa-upload text-3xl"></i>
            </div>
            <h2 class="text-2xl font-black text-gray-800 dark:text-gray-100 mb-1">Restore Data</h2>
            <div class="text-red-500 text-[10px] font-black uppercase tracking-widest mb-4">Warning: Overwrites All Data</div>
            
            <form id="restore-drop-zone" class="w-full mb-6 relative">
                <input type="file" id="zip_upload" class="hidden" accept=".zip">
                <label for="zip_upload" class="block w-full border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl p-6 cursor-pointer hover:border-orange-500 hover:bg-orange-50/10 transition-all">
                    <div id="file-status-icon" class="text-orange-500 mb-2">
                        <i class="fas fa-file-zipper text-2xl"></i>
                    </div>
                    <div id="file-name-text" class="text-gray-700 dark:text-gray-300 font-bold text-sm">Drag or Browse ZIP</div>
                </label>
            </form>

            <button id="restore-trigger-btn" disabled onclick="openRestoreAuth()" class="w-full bg-orange-600 hover:bg-orange-700 text-white py-5 rounded-2xl font-black shadow-lg transition-all flex items-center justify-center gap-3 disabled:opacity-30 disabled:cursor-not-allowed">
                <i class="fas fa-sync-alt"></i> Upload & Restore
            </button>
        </div>

    </div>

    <!-- Factory Reset -->
    <div class="mt-12 bg-gray-50 dark:bg-gray-800/50 rounded-[2rem] p-10 flex flex-col md:flex-row items-center justify-between border border-gray-100 dark:border-gray-800 gap-6">
        <div class="flex items-center gap-6">
            <div class="w-14 h-14 bg-red-100 dark:bg-red-900/40 rounded-xl flex items-center justify-center text-red-600">
                <i class="fas fa-trash-can text-xl"></i>
            </div>
            <div>
                <h3 class="font-black text-gray-800 dark:text-gray-100">Factory System Reset</h3>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Irreversible Action • Auto-Backup Included</p>
            </div>
        </div>
        <button onclick="openModal('resetModal')" class="px-8 py-4 bg-red-600 hover:bg-red-700 text-white font-black rounded-xl shadow-lg transition-all">
            Wipe All Data
        </button>
    </div>
</div>

<!-- Modal Template Helper -->
<?php 
function renderAuthModal($id, $title, $description, $colorClass, $btnText, $actionFn) {
    echo "
    <div id='$id' class='modal-overlay fixed inset-0 bg-black/60 backdrop-blur-md z-[200] hidden items-center justify-center p-4'>
        <div class='bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl max-w-md w-full overflow-hidden transition-all duration-300 transform scale-95 opacity-0 modal-content'>
            <div class='bg-$colorClass p-10 text-white text-center rounded-b-[2.5rem] relative z-10'>
                <button onclick=\"closeModal('$id')\" class='absolute top-6 right-6 text-white/50 hover:text-white transition-colors'>
                    <i class='fas fa-times text-xl'></i>
                </button>
                <h3 class='text-2xl font-black'>$title</h3>
                <p class='text-white/80 text-sm mt-2 font-bold uppercase tracking-tighter'>$description</p>
            </div>
            <div class='p-10 pt-12 -mt-8 bg-white dark:bg-gray-900'>
                <div class='mb-6'>
                    <label class='block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2'>Admin Password Verification</label>
                    <input type='password' id='{$id}_pass' class='w-full px-5 py-4 bg-gray-50 dark:bg-gray-800 border-none rounded-2xl focus:ring-2 focus:ring-$colorClass text-lg font-black' placeholder='••••••••'>
                </div>
                <div id='{$id}_error' class='hidden bg-red-50 text-red-600 p-4 rounded-xl mb-6 text-sm font-bold border border-red-100 flex items-center gap-2'>
                    <i class='fas fa-exclamation-circle'></i> <span id='{$id}_error_msg'></span>
                </div>
                <div class='flex gap-4'>
                    <button onclick=\"closeModal('$id')\" class='flex-1 py-4 bg-gray-100 dark:bg-gray-800 text-gray-500 font-black rounded-2xl'>Cancel</button>
                    <button id='{$id}_btn' onclick=\"$actionFn\" class='flex-1 py-4 bg-$colorClass text-white font-black rounded-2xl shadow-lg shadow-$colorClass/20'>$btnText</button>
                </div>
            </div>
        </div>
    </div>";
}
?>

<?php 
// renderAuthModal('backupModal', 'Database Backup', 'System will prepare ZIP file', 'emerald-600', 'Start Backup', 'runBackup()');
renderAuthModal('restoreModal', 'Restore System', 'Overwriting with backup data', 'orange-600', 'Confirm Restore', 'runRestore()');
renderAuthModal('resetModal', 'Factory Reset', 'Deleting every file on system', 'red-600', 'Yes, Format Everything', 'runReset()');
?>

<!-- Hidden elements for automation -->
<iframe id="dl_frame" class="hidden"></iframe>
<form id="backup_form" action="../api/backup_data.php" method="POST" target="dl_frame" class="hidden">
    <input type="password" name="password" id="backup_form_pass">
</form>

<script>
let activeFile = null;

// UI State Management
function updateRestoreUI(file) {
    const text = document.getElementById('file-name-text');
    const icon = document.getElementById('file-status-icon');
    const btn = document.getElementById('restore-trigger-btn');
    
    if (file && file.name.endsWith('.zip')) {
        activeFile = file;
        text.innerText = file.name;
        text.classList.add('text-orange-600');
        icon.innerHTML = '<i class="fas fa-file-circle-check text-2xl animate-bounce"></i>';
        btn.disabled = false;
        btn.classList.remove('opacity-30');
    } else {
        activeFile = null;
        text.innerText = "Invalid File (ZIP Required)";
        text.classList.add('text-red-500');
        btn.disabled = true;
        btn.classList.add('opacity-30');
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('zip_upload');
    const zone = document.getElementById('restore-drop-zone');

    // Manual Select
    input.addEventListener('change', (e) => {
        if (e.target.files.length) updateRestoreUI(e.target.files[0]);
    });

    // Drag & Drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eName => {
        zone.addEventListener(eName, (e) => { e.preventDefault(); e.stopPropagation(); });
    });

    zone.addEventListener('dragover', () => zone.classList.add('bg-orange-50/20'));
    ['dragleave', 'drop'].forEach(eName => zone.addEventListener(eName, () => zone.classList.remove('bg-orange-50/20')));

    zone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length) updateRestoreUI(files[0]);
    });
});

// Modal Logic
function openModal(id) {
    const modal = document.getElementById(id);
    const content = modal.querySelector('.modal-content');
    const passInput = document.getElementById(id + '_pass');
    const errorDiv = document.getElementById(id + '_error');
    
    if (passInput) passInput.value = '';
    if (errorDiv) errorDiv.classList.add('hidden');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
        if (passInput) passInput.focus();
    }, 10);
}

function closeModal(id) {
    const modal = document.getElementById(id);
    const content = modal.querySelector('.modal-content');
    const passInput = document.getElementById(id + '_pass');
    const errorDiv = document.getElementById(id + '_error');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (passInput) passInput.value = '';
        if (errorDiv) errorDiv.classList.add('hidden');
    }, 300);
}

function openRestoreAuth() {
    if (activeFile) openModal('restoreModal');
}

// Action Execution
async function verifyPass(pass, btnId, errorId) {
    const btn = document.getElementById(btnId);
    const errorMsg = document.getElementById(errorId + '_msg');
    const errorDiv = document.getElementById(errorId);
    
    errorDiv.classList.add('hidden');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

    try {
        const formData = new FormData();
        formData.append('password', pass);
        const res = await fetch('../api/verify_admin.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) return true;
        
        errorMsg.innerText = data.message || "Invalid Password";
        errorDiv.classList.remove('hidden');
    } catch(e) {
        errorMsg.innerText = "Network Error";
        errorDiv.classList.remove('hidden');
    }
    
    btn.disabled = false;
    btn.innerHTML = originalText;
    return false;
}

async function runBackup() {
    const btn = document.getElementById('backupBtn');
    const originalContent = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating ZIP...';
    
    try {
        const response = await fetch('../api/backup_data.php', {
            method: 'POST'
        });
        
        // Check content type - if it's JSON, it's an error message
        const contentType = response.headers.get('Content-Type');
        if (contentType && contentType.includes('application/json')) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Unknown server error');
        }
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error('Server Error: ' + errorText);
        }
        
        const blob = await response.blob();
        if (blob.size === 0) {
            throw new Error('Downloaded file is empty (0 bytes)');
        }
        
        const disposition = response.headers.get('Content-Disposition');
        let filename = 'school_backup.zip';
        if (disposition && disposition.includes('filename=')) {
            filename = disposition.split('filename=')[1].replace(/"/g, '');
        }
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        btn.innerHTML = '<i class="fas fa-check"></i> Downloaded!';
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }, 2000);
        
    } catch (error) {
        alert('Backup Failed: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
}

async function runRestore() {
    const pass = document.getElementById('restoreModal_pass').value;
    const btn = document.getElementById('restoreModal_btn');
    const errDiv = document.getElementById('restoreModal_error');
    const errMsg = document.getElementById('restoreModal_error_msg');

    if (!activeFile) return;
    
    // Manual verification not needed because restore_data.php does it, 
    // but we use verifyPass for consistent UI feedback before upload starts
    if (await verifyPass(pass, 'restoreModal_btn', 'restoreModal_error')) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Restoring...';
        
        const fd = new FormData();
        fd.append('password', pass);
        fd.append('backup_file', activeFile);

        try {
            const res = await fetch('../api/restore_data.php', { method: 'POST', body: fd });
            const data = await res.json();
            
            if (data.success) {
                btn.innerHTML = '<i class="fas fa-check"></i> Success!';
                setTimeout(() => window.location.href = '../index.php?restored=true', 1500);
            } else {
                errMsg.innerText = data.message;
                errDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = "Retry Restore";
            }
        } catch(e) {
            errMsg.innerText = "Restore Failed";
            errDiv.classList.remove('hidden');
            btn.disabled = false;
        }
    }
}

async function runReset() {
    const pass = document.getElementById('resetModal_pass').value;
    const btn = document.getElementById('resetModal_btn');
    
    if (await verifyPass(pass, 'resetModal_btn', 'resetModal_error')) {
        btn.innerHTML = '<i class="fas fa-cloud-arrow-down fa-spin"></i> Pre-Reset Backup...';
        
        // 1. Trigger Auto Backup via a hidden link for maximum compatibility
        const dlLink = document.createElement('a');
        dlLink.href = '../api/backup_data_auto.php';
        dlLink.target = '_blank';
        document.body.appendChild(dlLink);
        dlLink.click();
        document.body.removeChild(dlLink);
        
        // 2. Change UI to inform user to wait for download
        btn.className = "flex-1 py-4 bg-amber-600 text-white font-black rounded-2xl shadow-lg animate-pulse";
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Wait for Download...';
        
        // 3. Wipe after a sufficient delay to allow the browser/IDM to start the download
        setTimeout(async () => {
            btn.className = "flex-1 py-4 bg-red-700 text-white font-black rounded-2xl shadow-lg";
            btn.innerHTML = '<i class="fas fa-skull-crossbones animate-bounce"></i> Deleting Data...';
            
            const fd = new FormData();
            fd.append('password', pass);
            const res = await fetch('../api/reset_data.php', { method: 'POST', body: fd });
            const data = await res.json();
            
            if (data.success) {
                window.location.href = '../login.php?reset=success';
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.className = "flex-1 py-4 bg-red-600 text-white font-black rounded-2xl";
                btn.innerHTML = "Wipe All Data";
            }
        }, 5000); // 5 second delay to ensure download starts
    }
}
</script>

<?php include '../includes/footer.php'; ?>
 