<?php 
require_once '../includes/db.php';
include '../includes/header.php'; 
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 text-white p-8 rounded-2xl shadow-xl mb-10 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="text-center md:text-left">
                <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight mb-2">Software License</h1>
                <p class="text-indigo-100 text-lg opacity-90 max-w-xl">Legal status and ownership information for the School Management System.</p>
            </div>
            <div class="bg-white/10 p-4 rounded-xl border border-white/20 backdrop-blur-sm">
                <i class="fas fa-certificate text-5xl text-yellow-400 animate-pulse"></i>
            </div>
        </div>
        <!-- Decorative blobs -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-indigo-500/20 rounded-full blur-3xl"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar: Legal Warning -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-lg border border-red-100 overflow-hidden">
                <div class="bg-red-600 p-4 text-white flex items-center gap-3">
                    <i class="fas fa-gavel text-xl"></i>
                    <h2 class="font-bold uppercase tracking-wider">Legal Warning</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 leading-relaxed mb-4 font-medium">
                        This software is the intellectual property of <span class="text-indigo-600 font-bold">Abdul Rafay Qazi</span>.
                    </p>
                    <div class="bg-red-50 p-4 rounded-xl border-l-4 border-red-500 mb-4">
                        <p class="text-red-700 text-sm italic">
                            "Unauthorized sale, distribution, or duplication of this software without the explicit written permission of the developer is strictly prohibited."
                        </p>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                        <i class="fas fa-balance-scale text-indigo-600 text-sm"></i> 
                        Relevant Laws (Pakistan)
                    </h3>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li class="flex gap-2">
                            <span class="text-red-600 shrink-0 font-bold">•</span>
                            <span><strong>Copyright Ordinance, 1962 (Section 66):</strong> Infringement of copyright in computer programs is punishable by up to 3 years imprisonment and heavy fines.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-red-600 shrink-0 font-bold">•</span>
                            <span><strong>PECA 2016 (Section 14):</strong> Electronic fraud or unauthorized use for financial gain constitutes a serious cybercrime.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-red-600 shrink-0 font-bold">•</span>
                            <span><strong>Section 56:</strong> Specifically restricts copying, adapting, or distribution without authorization.</span>
                        </li>
                    </ul>
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <p class="text-[10px] text-gray-400 text-center uppercase tracking-widest">Legal Action will be initiated against pirated copies.</p>
                    </div>
                </div>
            </div>

            <!-- Stats/Highlights Card -->
            <div class="bg-indigo-900 rounded-2xl p-6 text-white shadow-lg shadow-indigo-200">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-star text-yellow-400"></i> Dev Portfolio
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-indigo-200 text-sm">Web Dev</span>
                        <div class="w-32 h-2 bg-indigo-700 rounded-full overflow-hidden">
                            <div class="bg-yellow-400 h-full w-[95%]"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-indigo-200 text-sm">PHP/MySQL</span>
                        <div class="w-32 h-2 bg-indigo-700 rounded-full overflow-hidden">
                            <div class="bg-green-400 h-full w-[90%]"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-indigo-200 text-sm">SEO/Content</span>
                        <div class="w-32 h-2 bg-indigo-700 rounded-full overflow-hidden">
                            <div class="bg-blue-400 h-full w-[85%]"></div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 bg-white/10 p-3 rounded-lg text-center">
                    <p class="text-xs text-indigo-100">"Focused on high-quality delivery and exceeding client expectations."</p>
                </div>
            </div>
        </div>

        <!-- Main Content: Developer Profile -->
        <div class="lg:col-span-2 space-y-8">
            <!-- About Me Card -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                    <div class="relative">
                        <div class="w-32 h-32 md:w-40 md:h-40 rounded-2xl bg-indigo-100 border-4 border-white shadow-xl overflow-hidden flex items-center justify-center">
                              <!-- Developer Profile Picture -->
                              <img src="../assets/img/developer.jpg?v=<?php echo time(); ?>" alt="Abdul Rafay Qazi" class="w-full h-full object-cover object-top">
                        </div>
                        <div class="absolute -bottom-3 -right-3 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold border-4 border-white shadow-lg">
                            Available
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
                            <a href="mailto:abdulrafehqazi@gmail.com" class="p-3 bg-indigo-50 text-indigo-700 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <a href="https://github.com/rafayqazi" target="_blank" class="p-3 bg-gray-50 text-gray-800 rounded-xl hover:bg-gray-900 hover:text-white transition-all shadow-sm">
                                <i class="fab fa-github"></i>
                            </a>
                            <a href="https://www.linkedin.com/in/abdulrafayqazi" target="_blank" class="p-3 bg-blue-50 text-blue-700 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                <i class="fab fa-linkedin"></i>
                            </a>
                            <a href="https://wa.me/923000358189" target="_blank" class="p-3 bg-green-50 text-green-700 rounded-xl hover:bg-green-600 hover:text-white transition-all shadow-sm" title="WhatsApp">
                                <i class="fab fa-whatsapp"></i>
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
                        <div class="relative pl-6 border-l-2 border-indigo-100">
                            <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-indigo-100"></div>
                            <div class="font-bold text-sm">Matriculation</div>
                            <div class="text-xs text-gray-400">BISE Hyderabad</div>
                        </div>
                    </div>
                </div>

                <!-- Experience -->
                <div class="bg-white rounded-2xl shadow-md p-6 border-t-4 border-green-600">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-briefcase text-green-600"></i> Experience
                    </h3>
                    <div class="space-y-3">
                        <div class="bg-gray-50 p-3 rounded-lg flex justify-between items-center group hover:bg-green-50 transition-colors">
                            <span class="text-sm font-medium">PHP Development</span>
                            <span class="text-xs bg-green-200 text-green-800 px-2 py-0.5 rounded-full font-bold">7+ Years</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg flex justify-between items-center group hover:bg-green-50 transition-colors">
                            <span class="text-sm font-medium">WordPress Dev</span>
                            <span class="text-xs bg-green-200 text-green-800 px-2 py-0.5 rounded-full font-bold">7+ Years</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg flex justify-between items-center group hover:bg-green-50 transition-colors">
                            <span class="text-sm font-medium">Web Content Writing</span>
                            <span class="text-xs bg-green-200 text-green-800 px-2 py-0.5 rounded-full font-bold">5+ Years</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skills & Certifications -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 underline decoration-indigo-200 underline-offset-8">
                            Programming Skills
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200 hover:scale-110 transition-transform">HTML5</span>
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200 hover:scale-110 transition-transform">CSS3</span>
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200 hover:scale-110 transition-transform">JavaScript</span>
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200 hover:scale-110 transition-transform">PHP</span>
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200 hover:scale-110 transition-transform">SQL</span>
                            <span class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-bold rounded-lg border border-orange-200 hover:scale-110 transition-transform">WordPress</span>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-lg border border-blue-200 hover:scale-110 transition-transform">GitHub</span>
                            <span class="px-3 py-1 bg-cyan-100 text-cyan-700 text-xs font-bold rounded-lg border border-cyan-200 hover:scale-110 transition-transform">ReactJS</span>
                            <span class="px-3 py-1 bg-black text-white text-xs font-bold rounded-lg border border-gray-800 hover:scale-110 transition-transform">Next.js</span>
                            <span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-lg border border-blue-700 hover:scale-110 transition-transform">React Native</span>
                            <span class="px-3 py-1 bg-sky-100 text-sky-700 text-xs font-bold rounded-lg border border-sky-200 hover:scale-110 transition-transform">Tailwind CSS</span>
                            <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-lg border border-green-200 hover:scale-110 transition-transform">Node.js</span>
                        </div>

                         <h3 class="text-lg font-bold text-gray-900 mt-8 mb-4 flex items-center gap-2 underline decoration-green-200 underline-offset-8">
                            Software Tools
                        </h3>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                            <div class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> VS Code</div>
                            <div class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> XAMPP</div>
                            <div class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> MS Word</div>
                            <div class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> MS Excel</div>
                            <div class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> MS PowerPoint</div>
                            <div class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> GitHub Dash</div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 underline decoration-yellow-200 underline-offset-8">
                            Certifications
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded bg-yellow-100 flex items-center justify-center shrink-0">
                                     <i class="fab fa-microsoft text-blue-600 text-sm"></i>
                                </div>
                                <div class="text-xs">
                                    <div class="font-bold text-gray-800">Microsoft Word Specialist (2016)</div>
                                    <div class="text-gray-500">Certified by Microsoft</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded bg-blue-100 flex items-center justify-center shrink-0">
                                     <i class="fas fa-graduation-cap text-blue-600 text-sm"></i>
                                </div>
                                <div class="text-xs">
                                    <div class="font-bold text-gray-800">Digital Literacy / SEO / Content Writing</div>
                                    <div class="text-gray-500">Certified by Digiskills.pk</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded bg-green-100 flex items-center justify-center shrink-0">
                                     <i class="fas fa-laptop-code text-green-600 text-sm"></i>
                                </div>
                                <div class="text-xs">
                                    <div class="font-bold text-gray-800">Wordpress Development / D.I.T</div>
                                    <div class="text-gray-500">Professional Diploma</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Projects -->
            <div class="space-y-6">
                <!-- Project 1: SMS -->
                <div class="bg-gradient-to-br from-indigo-900 to-indigo-800 rounded-2xl shadow-xl border border-indigo-700 p-8 text-white relative overflow-hidden group">
                    <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/4 -translate-y-1/4 group-hover:scale-110 transition-transform duration-700">
                        <i class="fas fa-university text-9xl"></i>
                    </div>
                    
                    <h3 class="text-xl font-black mb-4 flex items-center gap-3">
                        <i class="fas fa-code-branch text-2xl text-yellow-400"></i> Current Project: School Management System
                    </h3>
                    
                    <p class="text-indigo-100/80 text-sm leading-relaxed mb-6 max-w-xl">
                        A premium, fully-integrated school hub designed for performance and scale. 
                        Features a sleek **AJAX-powered dashboard**, real-time **Inventory tracking**, 
                        and complex **Automated Result Generation** logic with secure multi-role access.
                    </p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-white/10 text-center hover:bg-white/20 transition-colors">
                            <i class="fas fa-chart-line text-green-400 mb-1"></i>
                            <p class="text-[9px] font-black uppercase tracking-wider">Auto-Reports</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-white/10 text-center hover:bg-white/20 transition-colors">
                            <i class="fas fa-boxes text-blue-400 mb-1"></i>
                            <p class="text-[9px] font-black uppercase tracking-wider">Inventory+v2</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-white/10 text-center hover:bg-white/20 transition-colors">
                            <i class="fas fa-user-shield text-purple-400 mb-1"></i>
                            <p class="text-[9px] font-black uppercase tracking-wider">RBAC Security</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm p-3 rounded-xl border border-white/10 text-center hover:bg-white/20 transition-colors">
                            <i class="fas fa-database text-amber-400 mb-1"></i>
                            <p class="text-[9px] font-black uppercase tracking-wider">SQL Optimized</p>
                        </div>
                    </div>
                </div>

                <!-- Project 2 (Previous): COVID Robot -->
                <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl shadow-lg border border-gray-100 p-8 group">
                    <h3 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-3">
                        <i class="fas fa-robot text-2xl text-indigo-600 animate-bounce"></i> FYP: COVID-19 Inspection Robot
                    </h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-6">
                        Fully functional inspection robot using **IoT, AI, and Machine Learning**. 
                        Designed to prevent person-to-person spread in crowded areas. Proved as a position-winner project at SAU University.
                        <br>
                        <a href="https://github.com/rafayqazi/Covid-19-Inspection-Robot" target="_blank" class="mt-2 inline-flex items-center gap-2 text-indigo-600 font-bold hover:text-indigo-800 transition-colors">
                            <i class="fab fa-github"></i> View Project on GitHub
                        </a>
                    </p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white p-3 rounded-xl shadow-sm text-center border border-gray-100 group-hover:border-indigo-200 transition-colors">
                            <i class="fas fa-mask text-indigo-500 mb-1"></i>
                            <p class="text-[9px] font-bold text-gray-500">Mask Detection</p>
                        </div>
                        <div class="bg-white p-3 rounded-xl shadow-sm text-center border border-gray-100 group-hover:border-indigo-200 transition-colors">
                            <i class="fas fa-thermometer-half text-red-500 mb-1"></i>
                            <p class="text-[9px] font-bold text-gray-500">Temp Check</p>
                        </div>
                        <div class="bg-white p-3 rounded-xl shadow-sm text-center border border-gray-100 group-hover:border-indigo-200 transition-colors">
                            <i class="fas fa-pump-soap text-green-500 mb-1"></i>
                            <p class="text-[9px] font-bold text-gray-500">Sanitization</p>
                        </div>
                        <div class="bg-white p-3 rounded-xl shadow-sm text-center border border-gray-100 group-hover:border-indigo-200 transition-colors">
                            <i class="fas fa-vial text-blue-500 mb-1"></i>
                            <p class="text-[9px] font-bold text-gray-500">Vaccine Verify</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Footer -->
    <div class="mt-12 bg-white rounded-2xl p-8 shadow-md border border-gray-100 text-center">
        <h3 class="text-2xl font-bold text-gray-900 mb-4 tracking-tighter">Let's Build Something Great Together</h3>
        <p class="text-gray-500 max-w-2xl mx-auto mb-8">Currently available for high-quality web development, WordPress solutions, and specialized software projects.</p>
        <div class="flex flex-wrap justify-center gap-6">
            <div class="flex items-center gap-2">
                <i class="fas fa-map-marker-alt text-red-500"></i>
                <span class="text-sm font-bold text-gray-700">Sindh, Pakistan</span>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
