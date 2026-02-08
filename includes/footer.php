            </div> <!-- End Page Content Wrapper -->
            <footer class="mt-8 py-6 text-center text-sm text-gray-500 border-t border-gray-200">
                <p>Application created by <span class="font-semibold text-indigo-600">Abdul Rafay Qazi</span> and all rights reserved 2025 &copy;</p>
                <div class="mt-2 flex justify-center gap-4">
                    <a href="<?php echo $base_path; ?>pages/license.php" class="text-indigo-500 hover:text-indigo-700 hover:underline flex items-center gap-1">
                        <i class="fas fa-file-contract"></i> Software License
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="mailto:abdulrafehqazi@gmail.com" class="text-indigo-500 hover:text-indigo-700 hover:underline flex items-center gap-1">
                        <i class="fas fa-envelope"></i> Contact Developer
                    </a>
                </div>
            </footer>
        </main>
    </div>

    <!-- Chat Widget -->
    <div id="chat-widget-btn" class="fixed bottom-5 right-5 w-14 h-14 bg-gradient-to-br from-green-700 to-green-800 text-white rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:scale-110 transition-transform z-50 text-2xl">
        <i class="fas fa-robot"></i>
    </div>

    <div id="chat-widget-window" class="fixed bottom-24 right-5 w-[350px] h-[500px] bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden z-50 transition-all duration-300 transform translate-y-5 opacity-0 pointer-events-none [&.open]:translate-y-0 [&.open]:opacity-100 [&.open]:pointer-events-auto">
        <div class="bg-green-700 text-white p-4 flex items-center justify-between">
            <h3 class="m-0 text-base font-semibold flex items-center"><i class="fas fa-robot mr-2"></i> Ali Bux Jarwar AI</h3>
            <div id="chat-close" class="cursor-pointer opacity-80 hover:opacity-100"><i class="fas fa-times"></i></div>
        </div>
        <div id="chat-messages" class="flex-1 p-4 overflow-y-auto bg-gray-50 flex flex-col gap-3">
            <div class="max-w-[80%] p-3 rounded-xl text-sm leading-relaxed relative break-words self-start bg-white border border-gray-200 text-gray-800 rounded-bl-sm">
                Hello! I am the school's AI assistant. I have access to all student, teacher, and attendance records. How can I help you today?
            </div>
            <div class="mt-2 mb-2">
                <p class="text-xs text-gray-400 mb-2 px-2">Suggested Questions:</p>
                <div class="flex flex-wrap gap-2 px-2">
                    <button class="bg-white border border-gray-200 rounded-full px-3 py-1.5 text-xs text-green-700 cursor-pointer hover:bg-green-700 hover:text-white hover:border-green-700 transition-colors" onclick="sendSuggestion(this)">Check today's attendance</button>
                    <button class="bg-white border border-gray-200 rounded-full px-3 py-1.5 text-xs text-green-700 cursor-pointer hover:bg-green-700 hover:text-white hover:border-green-700 transition-colors" onclick="sendSuggestion(this)">Total students count</button>
                    <button class="bg-white border border-gray-200 rounded-full px-3 py-1.5 text-xs text-green-700 cursor-pointer hover:bg-green-700 hover:text-white hover:border-green-700 transition-colors" onclick="sendSuggestion(this)">List absent students</button>
                    <button class="bg-white border border-gray-200 rounded-full px-3 py-1.5 text-xs text-green-700 cursor-pointer hover:bg-green-700 hover:text-white hover:border-green-700 transition-colors" onclick="sendSuggestion(this)">Recent admissions</button>
                    <button class="bg-white border border-gray-200 rounded-full px-3 py-1.5 text-xs text-green-700 cursor-pointer hover:bg-green-700 hover:text-white hover:border-green-700 transition-colors" onclick="sendSuggestion(this)">Teacher list</button>
                </div>
            </div>
        </div>
        <div class="p-4 border-t border-gray-200 bg-white flex gap-2">
            <input type="text" id="chat-input" placeholder="Ask about students, attendance..." class="flex-1 p-2 border border-gray-300 rounded-full focus:outline-none focus:border-green-700 text-sm">
            <button id="chat-send-btn" class="bg-green-700 text-white w-9 h-9 rounded-full flex items-center justify-center hover:bg-green-800 transition-colors">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script src="<?php echo $base_path; ?>assets/js/chat.js?v=<?php echo time(); ?>"></script>
    <!-- Global Confirmation Modal -->
    <div id="confirmationModal" class="fixed inset-0 z-[200] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeConfirmationModal()"></div>

            <!-- Modal panel -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="confirmModalTitle">Confirm Action</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="confirmModalMessage">Are you sure you want to proceed?</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="button" id="confirmModalYesBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirm
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeConfirmationModal()">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Notification Modal -->
    <div id="globalModal" class="fixed inset-0 z-[200] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>

            <!-- Modal panel -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div id="modalIconBg" class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i id="modalIcon" class="fas fa-check text-green-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Success</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="modalMessage">Operation completed successfully.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal()">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showModal(type, title, message) {
            const modal = document.getElementById('globalModal');
            const iconBg = document.getElementById('modalIconBg');
            const icon = document.getElementById('modalIcon');
            const titleEl = document.getElementById('modalTitle');
            const messageEl = document.getElementById('modalMessage');

            // Reset classes
            iconBg.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10';
            icon.className = 'fas';

            if (type === 'success') {
                iconBg.classList.add('bg-green-100');
                icon.classList.add('fa-check', 'text-green-600');
            } else if (type === 'error') {
                iconBg.classList.add('bg-red-100');
                icon.classList.add('fa-times', 'text-red-600');
            } else if (type === 'warning') {
                iconBg.classList.add('bg-yellow-100');
                icon.classList.add('fa-exclamation-triangle', 'text-yellow-600');
            }

            titleEl.textContent = title;
            messageEl.textContent = message;

            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('globalModal').classList.add('hidden');
        }

        let confirmCallback = null;

        function showConfirmationModal(title, message, onConfirm) {
            const modal = document.getElementById('confirmationModal');
            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalMessage').textContent = message;
            
            confirmCallback = onConfirm;
            
            modal.classList.remove('hidden');
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').classList.add('hidden');
            confirmCallback = null;
        }

        document.getElementById('confirmModalYesBtn').addEventListener('click', function() {
            if (confirmCallback) {
                confirmCallback();
            }
            closeConfirmationModal();
        });

        // Global Admission Modal
        function openAdmissionModal() {
            const modal = document.getElementById('admissionModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeAdmissionModal() {
            const modal = document.getElementById('admissionModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }
        }

        // Close on outside click for admission modal
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('admissionModal');
            if (event.target == modal) {
                closeAdmissionModal();
            }
        });
    </script>

    <!-- Admission Selection Modal -->
    <div id="admissionModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4 text-left">
        <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden animate-[scaleIn_0.3s_ease-out]">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <i class="fas fa-user-plus text-indigo-500"></i> Select Admission Type
                </h3>
                <button onclick="closeAdmissionModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-8 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Single Admission -->
                <a href="<?php echo $base_path; ?>pages/student_form.php" class="group flex flex-col items-center p-6 bg-slate-50 dark:bg-gray-800 rounded-2xl border-2 border-transparent hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all">
                    <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-100 mb-1">Single Admission</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center">Add students one by one manually.</p>
                </a>

                <!-- Bulk Admission -->
                <a href="<?php echo $base_path; ?>pages/bulk_admission.php" class="group flex flex-col items-center p-6 bg-slate-50 dark:bg-gray-800 rounded-2xl border-2 border-transparent hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all">
                    <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-csv"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-100 mb-1">Bulk Admission</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center">Import multiple students via CSV file.</p>
                </a>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/50 text-center">
                <button onclick="closeAdmissionModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-sm font-medium">Cancel</button>
            </div>
        </div>
    </div>

    <style>
        @keyframes scaleIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</body>
</html>
