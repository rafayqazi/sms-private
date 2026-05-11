$db = new Database();
$settings = $db->getSchoolSettings();
include '../includes/header.php'; 
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Page Header (Same style as license.php) -->
    <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 text-white p-8 rounded-2xl shadow-xl mb-10 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="text-center md:text-left">
                <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight mb-2">Developer Bio</h1>
                <p class="text-indigo-100 text-lg opacity-90 max-w-xl">Meet the architect behind the School Management System.</p>
            </div>
            <div class="bg-white/10 p-4 rounded-xl border border-white/20 backdrop-blur-sm">
                <i class="fas fa-user-tie text-5xl text-yellow-400 animate-pulse"></i>
            </div>
        </div>
        <!-- Decorative blobs -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-indigo-500/20 rounded-full blur-3xl"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar: System Info & Warning -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-lg border border-indigo-50 overflow-hidden">
                <div class="bg-indigo-600 p-4 text-white flex items-center gap-3">
                    <i class="fas fa-shield-alt text-xl"></i>
                    <h2 class="font-bold uppercase tracking-wider">System License</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex flex-col">
                            <span class="text-[10px] text-gray-400 uppercase font-black tracking-widest">License Key</span>
                            <?php 
                            $actualLicenseData = include '../data/license.php'; 
                            $displayKey = (is_array($actualLicenseData) && isset($actualLicenseData['license_key'])) ? $actualLicenseData['license_key'] : 'N/A';
                            $activationDate = (is_array($actualLicenseData) && isset($actualLicenseData['activation_date'])) ? explode(' ', $actualLicenseData['activation_date'])[0] : 'N/A';
                            ?>
                            <span class="text-xs font-mono text-gray-600 truncate bg-gray-50 p-2 rounded border border-gray-100 mt-1"><?php echo $displayKey; ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-emerald-500"></i>
                                <span class="text-xs font-bold text-emerald-700">System Activated</span>
                            </div>
                            <span class="text-[10px] text-emerald-600 font-bold"><?php echo $activationDate; ?></span>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <p class="text-[10px] text-gray-400 text-center uppercase tracking-widest italic">Authorized Build &bull; v2.0</p>
                    </div>
                </div>
            </div>

            <!-- Skills Progress -->
            <div class="bg-indigo-900 rounded-2xl p-6 text-white shadow-lg shadow-indigo-200">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-rocket text-yellow-400"></i> Core Skills
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-indigo-200 text-sm">Full Stack PHP</span>
                        <div class="w-32 h-2 bg-indigo-700 rounded-full overflow-hidden">
                            <div class="bg-yellow-400 h-full w-[95%]"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-indigo-200 text-sm">MySQL/Database</span>
                        <div class="w-32 h-2 bg-indigo-700 rounded-full overflow-hidden">
                            <div class="bg-green-400 h-full w-[90%]"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-indigo-200 text-sm">UX/UI Design</span>
                        <div class="w-32 h-2 bg-indigo-700 rounded-full overflow-hidden">
                            <div class="bg-blue-400 h-full w-[88%]"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Contact -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-headset text-indigo-500"></i> Direct Contact
                </h3>
                <div class="space-y-3">
                    <a href="mailto:abdulrafehqazi@gmail.com" class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-indigo-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-indigo-500 shadow-sm transition-transform group-hover:scale-110">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <span class="text-xs font-semibold text-gray-600">abdulrafehqazi@gmail.com</span>
                    </a>
                    <a href="https://wa.me/923000358189" target="_blank" class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-emerald-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-emerald-500 shadow-sm transition-transform group-hover:scale-110">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <span class="text-xs font-semibold text-gray-600">+92 300 0358189</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- About Me Card -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                    <div class="relative">
                        <div class="w-32 h-32 md:w-40 md:h-40 rounded-2xl bg-indigo-100 border-4 border-white shadow-xl overflow-hidden flex items-center justify-center">
                              <img src="../assets/img/developer.jpg?v=<?php echo time(); ?>" alt="Abdul Rafay Qazi" class="w-full h-full object-cover object-top">
                        </div>
                        <div class="absolute -bottom-3 -right-3 bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-bold border-4 border-white shadow-lg">
                            Designer
                        </div>
                    </div>
                    <div class="flex-1 text-center md:text-left">
                        <h2 class="text-3xl font-bold text-gray-900 mb-1">Abdul Rafay Qazi</h2>
                        <p class="text-indigo-600 font-semibold text-lg mb-4 italic">Inspiring growth through hard work and innovation</p>
                        
                        <p class="text-gray-600 leading-relaxed mb-6">
                            Highly motivated and ambitious individual with a strong passion for technology. 
                            I have a solid educational foundation and a track record of success in both academic and professional settings. 
                            Proactive approach to work with a commitment to delivering high-quality digital solutions.
                        </p>

                        <div class="flex flex-wrap justify-center md:justify-start gap-3">
                            <a href="https://github.com/rafayqazi" target="_blank" class="p-3 bg-gray-900 text-white rounded-xl hover:scale-110 transition-transform shadow-sm">
                                <i class="fab fa-github"></i>
                            </a>
                            <a href="https://www.linkedin.com/in/abdulrafayqazi" target="_blank" class="p-3 bg-blue-600 text-white rounded-xl hover:scale-110 transition-transform shadow-sm">
                                <i class="fab fa-linkedin"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Professional Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Education -->
                <div class="bg-white rounded-2xl shadow-md p-6 border-t-4 border-indigo-600">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-graduation-cap text-indigo-600"></i> Education
                    </h3>
                    <div class="space-y-4">
                        <div class="relative pl-6 border-l-2 border-indigo-100">
                            <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-indigo-600"></div>
                            <div class="font-bold text-sm">Graduation (BSIT Hons)</div>
                            <div class="text-xs text-indigo-600 mb-1">2018 - 2022</div>
                            <div class="text-xs text-gray-500">Sindh Agriculture University Tandojam</div>
                        </div>
                        <div class="relative pl-6 border-l-2 border-indigo-100">
                            <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-indigo-200"></div>
                            <div class="font-bold text-sm">Intermediate</div>
                            <div class="text-xs text-gray-400">BISE Hyderabad</div>
                        </div>
                    </div>
                </div>

                <!-- Experience -->
                <div class="bg-white rounded-2xl shadow-md p-6 border-t-4 border-emerald-600">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-certificate text-emerald-600"></i> Expertise
                    </h3>
                    <div class="space-y-3">
                        <div class="bg-gray-50 p-3 rounded-lg flex justify-between items-center group hover:bg-emerald-50 transition-colors">
                            <span class="text-sm font-medium">PHP Development</span>
                            <span class="text-xs bg-emerald-200 text-emerald-800 px-2 py-0.5 rounded-full font-bold">7+ Years</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg flex justify-between items-center group hover:bg-emerald-50 transition-colors">
                            <span class="text-sm font-medium">WordPress Dev</span>
                            <span class="text-xs bg-emerald-200 text-emerald-800 px-2 py-0.5 rounded-full font-bold">7+ Years</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skills Box -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <i class="fas fa-code text-indigo-600"></i> Tech Stack
                </h3>
                <div class="flex flex-wrap gap-2">
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100">PHP 8.x</span>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100">MySQL</span>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100">Tailwind CSS</span>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100">JavaScript</span>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100">AJAX</span>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100">GitHub</span>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100">Python</span>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100">Chart.js</span>
                </div>
            </div>

            <!-- Featured Highlight -->
            <div class="bg-indigo-600 rounded-2xl shadow-xl p-8 text-white relative overflow-hidden group">
                <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-1/4 translate-y-1/4 group-hover:scale-110 transition-transform duration-700">
                    <i class="fas fa-graduation-cap text-9xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-4">The School Management Project</h3>
                <p class="text-indigo-100 text-sm leading-relaxed mb-6">
                    A premium, fully-integrated school hub designed for performance and scale. 
                    Features a sleek AJAX-powered dashboard, real-time inventory tracking, and complex automated result generation logic.
                </p>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-lg text-xs font-bold">
                        <i class="fas fa-shield-alt text-yellow-400"></i> Secure
                    </div>
                    <div class="flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-lg text-xs font-bold">
                        <i class="fas fa-bolt text-yellow-400"></i> Fast
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
