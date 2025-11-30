            <footer class="mt-8 py-6 text-center text-sm text-gray-500 border-t border-gray-200">
                <p>Application created by <span class="font-semibold text-indigo-600">Abdul Rafay Qazi</span> and all rights reserved 2025 &copy;</p>
            </footer>
        </main>
    </div>

    <!-- Chat Widget -->
    <div id="chat-widget-btn" class="chat-widget-btn">
        <i class="fas fa-robot fa-lg"></i>
    </div>

    <div id="chat-widget-window" class="chat-widget-window">
        <div class="chat-header">
            <h3><i class="fas fa-robot mr-2"></i> Ali Bux Jarwar AI</h3>
            <div id="chat-close" class="chat-close"><i class="fas fa-times"></i></div>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div class="message ai">
                Hello! I am the school's AI assistant. I have access to all student, teacher, and attendance records. How can I help you today?
            </div>
            <div class="chat-suggestions">
                <p class="text-xs text-gray-400 mb-2 px-2">Suggested Questions:</p>
                <div class="flex flex-wrap gap-2 px-2">
                    <button class="suggestion-chip" onclick="sendSuggestion(this)">Check today's attendance</button>
                    <button class="suggestion-chip" onclick="sendSuggestion(this)">Total students count</button>
                    <button class="suggestion-chip" onclick="sendSuggestion(this)">List absent students</button>
                    <button class="suggestion-chip" onclick="sendSuggestion(this)">Recent admissions</button>
                    <button class="suggestion-chip" onclick="sendSuggestion(this)">Teacher list</button>
                </div>
            </div>
        </div>
        <div class="chat-input-area">
            <input type="text" id="chat-input" placeholder="Ask about students, attendance...">
            <button id="chat-send-btn" class="chat-send-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <link rel="stylesheet" href="assets/css/chat.css?v=<?php echo time(); ?>">
    <script src="assets/js/chat.js?v=<?php echo time(); ?>"></script>
    <!-- Global Notification Modal -->
    <div id="globalModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
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
    </script>
</body>
</html>
