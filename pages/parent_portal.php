<?php
require_once '../includes/parent_auth_session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$parent_cnic = getLoggedInParentCnic();
$children = $db->getParentChildrenByCnic($parent_cnic);
$settings = $db->getSchoolSettings();

// Get parent name from the first child's data
$parent_name = !empty($children) ? $children[0]['father_name'] : 'Parent';

// Fetch Notices & Announcements
$notices = $db->getParentNotices($parent_cnic);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal - <?php echo htmlspecialchars($settings['school_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: {
                        primary: '#059669', // Emerald 600
                        'primary-dark': '#047857',
                    }
                }
            }
        }
    </script>
    <style>
        .glass { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen font-sans text-slate-900">

    <!-- Header -->
    <header class="sticky top-0 z-50 glass border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white p-2 rounded-xl shadow-sm border border-slate-100 flex items-center justify-center">
                        <img src="../assets/branding/logo.png" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h1 class="text-lg font-bold tracking-tight text-slate-800 leading-tight">Parent Portal</h1>
                        <p class="text-[10px] font-bold text-primary uppercase tracking-widest"><?php echo htmlspecialchars($settings['school_name']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="hidden md:block text-right">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Logged in as</p>
                        <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($parent_name); ?></p>
                    </div>
                    <a href="../parent_logout.php" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all font-bold text-xs uppercase tracking-widest">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        
        <!-- Welcome Section -->
        <div class="mb-12 animate-fade-in">
            <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight mb-2">Welcome Back!</h2>
            <p class="text-slate-500 font-medium">You have <span class="text-primary font-bold"><?php echo count($children); ?></span> children enrolled. Track their activities below.</p>
        </div>

        <!-- Notices & Announcements -->
        <?php if (!empty($notices)): ?>
        <div class="mb-12 animate-fade-in" style="animation-delay: 100ms">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <h3 class="text-xl font-black text-slate-800">Notices & Announcements</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($notices as $notice): 
                    $isGeneral = ($notice['target_cnic'] === 'ALL');
                    $bgClass = $isGeneral ? 'bg-amber-50 border-amber-100' : 'bg-white border-slate-200';
                    $iconClass = $isGeneral ? 'fa-globe-americas text-amber-500' : 'fa-user-shield text-emerald-500';
                    $badgeClass = $isGeneral ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700';
                ?>
                <div class="p-6 rounded-3xl border <?php echo $bgClass; ?> shadow-sm hover:shadow-md transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <i class="fas <?php echo $iconClass; ?> text-sm"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest <?php echo $badgeClass; ?> px-2 py-0.5 rounded-full">
                                <?php echo $isGeneral ? 'School Announcement' : 'Private Note'; ?>
                            </span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase"><?php echo date('M d, Y', strtotime($notice['created_at'])); ?></span>
                    </div>
                    <h4 class="text-lg font-black text-slate-900 mb-2 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($notice['title']); ?></h4>
                    <p class="text-sm font-medium text-slate-500 leading-relaxed"><?php echo nl2br(htmlspecialchars($notice['message'])); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Children Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($children as $index => $child): ?>
                <div class="group bg-white rounded-3xl shadow-sm hover:shadow-xl border border-slate-200 overflow-hidden transition-all duration-300 animate-fade-in" style="animation-delay: <?php echo $index * 100; ?>ms">
                    <!-- Child Header/Photo -->
                    <div class="h-32 bg-gradient-to-br from-emerald-500 to-teal-600 relative">
                        <div class="absolute -bottom-12 left-8 w-24 h-24 rounded-2xl border-4 border-white shadow-lg overflow-hidden bg-white">
                            <?php if (!empty($child['profile_image']) && file_exists('../' . $child['profile_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($child['profile_image']); ?>" alt="Profile" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-400 text-3xl">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="absolute top-4 right-4 bg-white/20 backdrop-blur-md rounded-full px-3 py-1 text-[10px] font-bold text-white uppercase tracking-widest">
                            GR# <?php echo htmlspecialchars($child['gr_no']); ?>
                        </div>
                    </div>

                    <div class="pt-16 p-8">
                        <h3 class="text-xl font-black text-slate-900 mb-1 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($child['student_name']); ?></h3>
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-6">Class <?php echo htmlspecialchars($child['current_class']); ?></p>

                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</p>
                                <p class="text-xs font-black text-emerald-600"><?php echo htmlspecialchars($child['student_status']); ?></p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Session</p>
                                <p class="text-xs font-black text-slate-700"><?php echo date('Y'); ?></p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="space-y-3">
                            <button onclick="viewChildDetail('<?php echo $child['id']; ?>', 'attendance')" class="w-full flex items-center justify-between p-4 rounded-2xl bg-white border-2 border-slate-100 hover:border-primary hover:bg-emerald-50 transition-all group/btn">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700">Attendance Records</span>
                                </div>
                                <i class="fas fa-chevron-right text-slate-300 group-hover/btn:text-primary transition-colors"></i>
                            </button>

                            <button onclick="viewChildDetail('<?php echo $child['id']; ?>', 'results')" class="w-full flex items-center justify-between p-4 rounded-2xl bg-white border-2 border-slate-100 hover:border-primary hover:bg-emerald-50 transition-all group/btn">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700">Detailed Marksheet</span>
                                </div>
                                <i class="fas fa-chevron-right text-slate-300 group-hover/btn:text-primary transition-colors"></i>
                            </button>

                            <button onclick="viewChildDetail('<?php echo $child['id']; ?>', 'certificates')" class="w-full flex items-center justify-between p-4 rounded-2xl bg-white border-2 border-slate-100 hover:border-primary hover:bg-emerald-50 transition-all group/btn">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                                        <i class="fas fa-certificate"></i>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700">Academic Certificates</span>
                                </div>
                                <i class="fas fa-chevron-right text-slate-300 group-hover/btn:text-primary transition-colors"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Detailed View Modal (placeholder for eventually opening details) -->
    <div id="detailModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 md:p-8">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="relative bg-white w-full max-w-5xl h-full max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-fade-in">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <div id="modalChildPhoto" class="w-12 h-12 rounded-xl border border-slate-100 overflow-hidden"></div>
                    <div>
                        <h3 id="modalTitle" class="text-xl font-bold text-slate-900">Child Name</h3>
                        <p id="modalSubtitle" class="text-xs font-bold text-slate-400 uppercase tracking-widest">Detail View</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 hover:text-red-600 hover:bg-red-50 transition-all flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-8" id="modalContent">
                <!-- Content loaded via AJAX -->
                <div class="flex flex-col items-center justify-center h-full text-slate-400">
                    <i class="fas fa-circle-notch fa-spin text-4xl mb-4"></i>
                    <p class="font-bold uppercase tracking-widest text-xs">Loading Information...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewChildDetail(childId, tab) {
            const modal = document.getElementById('detailModal');
            const content = document.getElementById('modalContent');
            const modalTitle = document.getElementById('modalTitle');
            const modalSubtitle = document.getElementById('modalSubtitle');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            // Reset content to loading
            content.innerHTML = `
                <div class="flex flex-col items-center justify-center py-20 text-slate-400">
                    <i class="fas fa-circle-notch fa-spin text-4xl mb-4 text-primary"></i>
                    <p class="font-bold uppercase tracking-widest text-xs">Fetching Records...</p>
                </div>
            `;

            fetch(`../api/get_child_activity.php?child_id=${childId}&type=${tab}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        content.innerHTML = `<div class="p-8 text-center text-red-500 font-bold">${data.message}</div>`;
                        return;
                    }

                    modalTitle.textContent = data.child_name;
                    modalSubtitle.textContent = tab.toUpperCase();

                    if (tab === 'attendance') {
                        renderAttendance(data.data, content);
                    } else if (tab === 'results') {
                        renderResults(data.data, content, childId);
                    } else if (tab === 'certificates') {
                        renderCertificates(data.data, content);
                    } else {
                        content.innerHTML = `<div class="p-12 text-center text-slate-400">Section details coming soon.</div>`;
                    }
                })
                .catch(err => {
                    content.innerHTML = `<div class="p-8 text-center text-red-500 font-bold">Error loading data.</div>`;
                });
        }

        function renderCertificates(records, container) {
            if (!records || records.length === 0) {
                container.innerHTML = `<div class="p-20 text-center text-slate-400 font-bold">No certificates available for this student.</div>`;
                return;
            }

            let html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-6">`;
            records.forEach(r => {
                html += `
                    <a href="${r.link}" target="_blank" class="flex items-center gap-6 p-6 bg-white border-2 border-slate-100 rounded-3xl hover:border-primary hover:bg-emerald-50 transition-all group/cert">
                        <div class="w-16 h-16 rounded-2xl ${r.color} flex items-center justify-center text-2xl group-hover/cert:scale-110 transition-transform">
                            <i class="${r.icon}"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-black text-slate-900 mb-1">${r.name}</h4>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Click to View & Print</p>
                        </div>
                        <i class="fas fa-external-link-alt text-slate-300 group-hover/cert:text-primary transition-colors"></i>
                    </a>
                `;
            });
            html += `</div>`;
            container.innerHTML = html;
        }

        function renderAttendance(records, container) {
            if (!records || records.length === 0) {
                container.innerHTML = `<div class="p-20 text-center text-slate-400 font-bold">No attendance records found.</div>`;
                return;
            }

            let html = `
                <div class="overflow-hidden border border-slate-100 rounded-2xl shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-100 text-[10px] font-black uppercase tracking-widest text-slate-400">
                            <tr>
                                <th class="p-4">Date</th>
                                <th class="p-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
            `;

            records.forEach(r => {
                const statusColor = r.status === 'Present' ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50';
                html += `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-4 text-sm font-bold text-slate-700">${r.date}</td>
                        <td class="p-4 text-center text-xs">
                            <span class="inline-block px-3 py-1 rounded-full font-black uppercase tracking-tighter ${statusColor}">${r.status}</span>
                        </td>
                    </tr>
                `;
            });

            html += `</tbody></table></div>`;
            container.innerHTML = html;
        }

        function renderResults(records, container, childId) {
            if (!records || records.length === 0) {
                container.innerHTML = `<div class="p-20 text-center text-slate-400 font-bold">No examination results found yet.</div>`;
                return;
            }

            let html = `<div class="space-y-6">`;
            records.forEach(r => {
                html += `
                    <div class="p-6 bg-slate-50 border border-slate-200 rounded-3xl">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                            <div>
                                <h4 class="text-lg font-black text-slate-900">${r.exam_type} (${r.year})</h4>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Marked on ${r.created_at}</p>
                            </div>
                            <div class="px-4 py-2 bg-white shadow-sm border border-slate-200 rounded-xl text-center min-w-[100px]">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Percentage</p>
                                <p class="text-lg font-black text-emerald-600">${r.percentage}%</p>
                            </div>
                            <a href="../pages/print_result.php?id=${childId}&exam_type=${encodeURIComponent(r.exam_type)}&year=${r.year}" target="_blank" class="flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200">
                                <i class="fas fa-download"></i>
                                Download Marksheet
                            </a>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-3 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Obtained</p>
                                <p class="text-sm font-black text-slate-800">${r.total_obtained}</p>
                            </div>
                            <div class="p-3 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Grade</p>
                                <p class="text-sm font-black text-blue-600">${r.grade}</p>
                            </div>
                            <div class="p-3 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Remarks</p>
                                <p class="text-sm font-black text-slate-700">${r.remarks}</p>
                            </div>
                            <div class="p-3 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Max Marks</p>
                                <p class="text-sm font-black text-slate-500">${r.total_max}</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
            container.innerHTML = html;
        }

        function closeModal() {
            const modal = document.getElementById('detailModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    </script>
</body>
</html>
