<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$db = new Database();

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        if (isEditor()) {
            // Editor sends to admin (system admin ID is 'admin')
            $db->sendMessage($_SESSION['teacher_id'], 'teacher', 'admin', 'admin', $message);
        } elseif (isAdmin() || isSuperAdmin()) {
            // Admin replies to specific teacher or parent
            $recipientId = $_POST['recipient_id'];
            $recipientType = $_POST['recipient_type'] ?? 'teacher';
            $db->sendMessage('admin', 'admin', $recipientId, $recipientType, $message);
        }
    }
    header("Location: messages.php" . (isset($recipientId) ? "?teacher_id=$recipientId" : ""));
    exit;
}

// Handle message deletion
if (isset($_GET['delete']) && (isAdmin() || isSuperAdmin())) {
    $db->deleteMessage($_GET['delete']);
    header("Location: messages.php" . (isset($_GET['teacher_id']) ? "?teacher_id=" . $_GET['teacher_id'] : ""));
    exit;
}

// Handle conversation deletion
if (isset($_GET['delete_conversation']) && (isAdmin() || isSuperAdmin())) {
    $teacherId = $_GET['delete_conversation'];
    $db->deleteConversation('admin', $teacherId);
    header("Location: messages.php");
    exit;
}

// Get conversation data
$conversations = [];
$currentConversation = [];
$currentTeacherId = null;
$currentTeacherName = '';
$currentUserType = 'teacher';

if (isEditor()) {
    // Editor only sees their conversation with admin
    $currentTeacherId = 'admin';
    $currentTeacherName = 'Admin';
    $currentConversation = $db->getConversation($_SESSION['teacher_id'], 'admin');
    $db->markMessagesAsRead($_SESSION['teacher_id'], 'admin');
} elseif (isAdmin() || isSuperAdmin()) {
    // Admin sees all conversations
    $conversations = $db->getAllConversations();
    
    // If a specific teacher/parent is selected
    if (isset($_GET['teacher_id'])) {
        $currentTeacherId = $_GET['teacher_id'];
        
        // Try to get as teacher first, then parent
        $teacher = $db->getTeacher($currentTeacherId);
        if ($teacher) {
            $currentTeacherName = $teacher['name'];
            $currentUserType = 'teacher';
        } else {
            $currentTeacherName = $db->getParentNameByCnic($currentTeacherId) ?? 'Unknown';
            $currentUserType = 'parent';
        }
        
        $currentConversation = $db->getConversation('admin', $currentTeacherId);
        $db->markMessagesAsRead('admin', $currentTeacherId);
    }

    // Get all teachers with roles for "New Message" feature
    $allUserRoles = $db->getAllUserRoles();
    $eligibleTeachers = [];
    foreach ($allUserRoles as $role) {
        // Skip if it's the admin/editor themselves (though currently admin doesn't have a teacher_id in user_roles usually, but good to be safe)
        // Actually user_roles links teacher_id to a role. 
        // We want to get the teacher details for each role.
        $teacherDetails = $db->getTeacher($role['teacher_id']);
        if ($teacherDetails) {
            $eligibleTeachers[] = [
                'id' => $role['teacher_id'],
                'name' => $teacherDetails['name'],
                'role' => $role['role']
            ];
        }
    }
}

require_once '../includes/header.php';
?>

<div class="flex flex-col md:flex-row h-[calc(100vh-4rem)] gap-4">
    <!-- Conversations List (Admin Only) -->
    <?php if (isAdmin() || isSuperAdmin()): ?>
    <div class="w-full md:w-80 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col <?php echo $currentTeacherId ? 'hidden md:flex' : 'flex'; ?>">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-4 flex justify-between items-center">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <i class="fas fa-comments"></i> Conversations
            </h2>
            <button onclick="document.getElementById('newMsgModal').classList.remove('hidden')" class="bg-white/20 hover:bg-white/30 p-2 rounded-full transition-colors" title="New Message">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto">
            <?php if (empty($conversations)): ?>
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p class="text-sm">No messages yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <a href="?teacher_id=<?php echo $conv['teacher_id']; ?>" 
                   class="block p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors <?php echo $currentTeacherId == $conv['teacher_id'] ? 'bg-indigo-50' : ''; ?>">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                            <?php echo strtoupper(substr($conv['teacher_name'], 0, 1)); ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($conv['teacher_name']); ?></h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-tighter px-2 py-0.5 rounded <?php echo $conv['user_type'] === 'parent' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo $conv['user_type']; ?>
                                    </span>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full font-bold"><?php echo $conv['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars(substr($conv['latest_message'], 0, 40)) . (strlen($conv['latest_message']) > 40 ? '...' : ''); ?></p>
                            <p class="text-xs text-gray-400 mt-1"><?php echo date('M j, g:i A', strtotime($conv['latest_time'])); ?></p>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Chat Area -->
    <div class="flex-1 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col <?php echo $currentTeacherId ? 'flex' : 'hidden md:flex'; ?>">
        <?php if ($currentTeacherId): ?>
            <!-- Chat Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="messages.php" class="md:hidden text-white hover:text-indigo-200 mr-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold">
                        <?php echo strtoupper(substr($currentTeacherName, 0, 1)); ?>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold"><?php echo htmlspecialchars($currentTeacherName); ?></h2>
                        <p class="text-sm text-indigo-100">
                            <?php 
                            if ($currentUserType === 'parent') {
                                echo 'Parent';
                            } else {
                                echo isEditor() ? 'System Administrator' : 'Teacher'; 
                            }
                            ?>
                        </p>
                    </div>
                </div>
                <?php if ((isAdmin() || isSuperAdmin()) && !isEditor()): ?>
                <button onclick="openResolveModal('<?php echo $currentTeacherId; ?>', '<?php echo $currentUserType; ?>', '<?php echo htmlspecialchars(addslashes($currentTeacherName)); ?>')" 
                   class="px-4 py-2 bg-white/20 border border-white/30 text-white rounded-lg hover:bg-white/30 transition-colors flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> Resolve Ticket
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Messages Area -->
            <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
                <?php if (empty($currentConversation)): ?>
                    <div class="text-center text-gray-400 py-8">
                        <i class="fas fa-comment-slash text-4xl mb-2"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($currentConversation as $msg): ?>
                        <?php 
                        $isSentByMe = (isEditor() && $msg['sender_type'] === 'teacher') || 
                                      ((isAdmin() || isSuperAdmin()) && $msg['sender_type'] === 'admin');
                        ?>
                        <div class="flex <?php echo $isSentByMe ? 'justify-end' : 'justify-start'; ?>">
                            <div class="max-w-md">
                                <div class="<?php echo $isSentByMe ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200'; ?> rounded-lg p-3 shadow-sm">
                                    <p class="text-sm"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                    <div class="flex items-center justify-between mt-2 gap-3">
                                        <p class="text-xs <?php echo $isSentByMe ? 'text-indigo-100' : 'text-gray-400'; ?>">
                                            <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                                        </p>
                                        <?php if ((isAdmin() || isSuperAdmin())): ?>
                                        <a href="?delete=<?php echo $msg['id']; ?>&teacher_id=<?php echo $currentTeacherId; ?>" 
                                           onclick="return confirm('Delete this message?')"
                                           class="text-xs <?php echo $isSentByMe ? 'text-indigo-200 hover:text-white' : 'text-red-500 hover:text-red-700'; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Message Input -->
            <form method="POST" class="p-4 bg-white border-t border-gray-200">
                <?php if (isAdmin() || isSuperAdmin()): ?>
                <input type="hidden" name="recipient_id" value="<?php echo $currentTeacherId; ?>">
                <input type="hidden" name="recipient_type" value="<?php echo $currentUserType; ?>">
                <?php endif; ?>
                <div class="flex gap-2">
                    <textarea name="message" rows="2" required
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600 focus:border-transparent resize-none"
                        placeholder="Type your message..."></textarea>
                    <button type="submit" 
                        class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </div>
            </form>
        <?php else: ?>
            <!-- No conversation selected -->
            <div class="flex-1 flex items-center justify-center text-gray-400">
                <div class="text-center">
                    <i class="fas fa-comments text-6xl mb-4"></i>
                    <p class="text-lg">Select a conversation to start messaging</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto scroll to bottom of messages
const messagesContainer = document.getElementById('messagesContainer');
if (messagesContainer) {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Resolve Ticket Modal Logic
function openResolveModal(recipientId, recipientType, recipientName) {
    document.getElementById('resolveRecipientId').value = recipientId;
    document.getElementById('resolveRecipientType').value = recipientType;
    document.getElementById('resolveRecipientName').textContent = recipientName;
    document.getElementById('resolveModal').classList.remove('hidden');
    document.getElementById('resolveModal').classList.add('flex');
    document.getElementById('customMsgContainer').classList.add('hidden');
}

function closeResolveModal() {
    document.getElementById('resolveModal').classList.add('hidden');
    document.getElementById('resolveModal').classList.remove('flex');
}

function toggleCustomMsg(val) {
    const container = document.getElementById('customMsgContainer');
    if (val === 'Custom') {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
}

document.getElementById('resolveForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
    
    fetch('../api/resolve_ticket.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'messages.php';
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Confirm Resolution';
        }
    })
    .catch(() => {
        alert('Error processing request.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Confirm Resolution';
    });
});
</script>

<!-- Resolve Ticket Modal -->
<?php if (isAdmin() || isSuperAdmin()): ?>
<div id="resolveModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white p-5">
            <h3 class="font-bold text-lg flex items-center gap-2"><i class="fas fa-clipboard-check"></i> Resolve Ticket</h3>
            <p class="text-emerald-100 text-sm mt-1">Close conversation with <strong id="resolveRecipientName"></strong></p>
        </div>
        <form id="resolveForm" class="p-6 space-y-5">
            <input type="hidden" name="recipient_id" id="resolveRecipientId">
            <input type="hidden" name="recipient_type" id="resolveRecipientType">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Resolution Status</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 border-2 border-gray-100 rounded-xl hover:border-emerald-300 transition-all cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
                        <input type="radio" name="status" value="Resolved" checked onchange="toggleCustomMsg(this.value)" class="accent-emerald-600">
                        <div class="flex-1">
                            <span class="font-bold text-gray-800">✅ Resolved</span>
                            <p class="text-xs text-gray-400">Ticket has been addressed successfully</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border-2 border-gray-100 rounded-xl hover:border-amber-300 transition-all cursor-pointer has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
                        <input type="radio" name="status" value="Pending" onchange="toggleCustomMsg(this.value)" class="accent-amber-600">
                        <div class="flex-1">
                            <span class="font-bold text-gray-800">⏳ Pending</span>
                            <p class="text-xs text-gray-400">Under review, will follow up</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border-2 border-gray-100 rounded-xl hover:border-red-300 transition-all cursor-pointer has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                        <input type="radio" name="status" value="Rejected" onchange="toggleCustomMsg(this.value)" class="accent-red-600">
                        <div class="flex-1">
                            <span class="font-bold text-gray-800">❌ Rejected</span>
                            <p class="text-xs text-gray-400">Ticket closed without resolution</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border-2 border-gray-100 rounded-xl hover:border-indigo-300 transition-all cursor-pointer has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                        <input type="radio" name="status" value="Custom" onchange="toggleCustomMsg(this.value)" class="accent-indigo-600">
                        <div class="flex-1">
                            <span class="font-bold text-gray-800">📋 Custom Message</span>
                            <p class="text-xs text-gray-400">Send a personalized response</p>
                        </div>
                    </label>
                </div>
            </div>
            
            <div id="customMsgContainer" class="hidden">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Custom Message</label>
                <textarea name="custom_message" rows="3" placeholder="Type your custom resolution message..." 
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none text-sm"></textarea>
            </div>
            
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeResolveModal()" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-all text-sm">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition-all text-sm shadow-lg shadow-emerald-200">
                    <i class="fas fa-check-circle mr-2"></i> Confirm Resolution
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

<!-- New Message Modal (Admin Only) -->
<?php if (isAdmin() || isSuperAdmin()): ?>
<div id="newMsgModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-indigo-600 text-white p-4 flex justify-between items-center">
            <h3 class="font-bold text-lg">New Message</h3>
            <button onclick="document.getElementById('newMsgModal').classList.add('hidden')" class="text-white/80 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-4 max-h-[60vh] overflow-y-auto">
            <p class="text-sm text-gray-500 mb-3">Select a teacher to message:</p>
            <div class="space-y-2">
                <?php if (empty($eligibleTeachers)): ?>
                    <p class="text-center text-gray-400 py-4">No teachers found with assigned roles.</p>
                <?php else: ?>
                    <?php foreach ($eligibleTeachers as $et): ?>
                    <a href="?teacher_id=<?php echo $et['id']; ?>" class="flex items-center gap-3 p-3 hover:bg-indigo-50 rounded-lg transition-colors border border-gray-100">
                        <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold">
                            <?php echo strtoupper(substr($et['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($et['name']); ?></h4>
                            <span class="text-xs text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100">
                                <?php echo htmlspecialchars($et['role']); ?>
                            </span>
                        </div>
                        <i class="fas fa-chevron-right ml-auto text-gray-300"></i>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
