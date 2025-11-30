<?php
require_once 'includes/auth_session.php';
require_once 'includes/db.php';
$db = new Database();

$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';
$students = [];

if ($selectedClass) {
    $students = $db->getStudentsByClass($selectedClass);
}
?>

<?php include 'includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold">Promote Students</h1>
        <p class="text-green-100 mt-1">Manage student promotions for the academic year</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Class</label>
        <select id="classSelect" class="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Choose a class...</option>
            <?php
            $classes = ['Kachi', 'One', 'Two', 'Three', 'Four', 'Five'];
            foreach ($classes as $c) {
                $selected = ($selectedClass == $c) ? 'selected' : '';
                echo "<option value=\"$c\" $selected>$c</option>";
            }
            ?>
        </select>
    </div>

    <?php if ($selectedClass && !empty($students)): ?>
    <div id="promotionSection">
        <div class="mb-4 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Students in Class <?php echo htmlspecialchars($selectedClass); ?></h2>
            <button onclick="applyPromotions()" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-accent transition-colors font-semibold">
                <i class="fas fa-check-circle mr-2"></i> Apply Promotions
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="studentsGrid">
            <?php foreach ($students as $student): ?>
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow" data-student-id="<?php echo $student['id']; ?>">
                <div class="flex items-center gap-3 mb-3">
                    <?php if (!empty($student['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-lg font-bold">
                            <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($student['student_name']); ?></h3>
                        <p class="text-sm text-gray-500">GR: <?php echo htmlspecialchars($student['gr_no']); ?></p>
                    </div>
                </div>
                
                <div class="text-sm text-gray-600 mb-3">
                    <p><strong>Father:</strong> <?php echo htmlspecialchars($student['father_name']); ?></p>
                    <p><strong>Current Class:</strong> <?php echo htmlspecialchars($student['current_class']); ?></p>
                </div>

                <div class="border-t pt-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Promotion Decision:</label>
                    <div class="space-y-2">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="promotion_<?php echo $student['id']; ?>" value="pass" class="mr-2" checked>
                            <span class="text-sm">✓ Pass (Promote to Next Class)</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="promotion_<?php echo $student['id']; ?>" value="fail" class="mr-2">
                            <span class="text-sm">✗ Fail (Mark as Repeater)</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="promotion_<?php echo $student['id']; ?>" value="stay" class="mr-2">
                            <span class="text-sm">― Stay Same (No Change)</span>
                        </label>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ($selectedClass): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    No students found in Class <?php echo htmlspecialchars($selectedClass); ?>.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('classSelect').addEventListener('change', function() {
    if (this.value) {
        window.location.href = 'promote_students.php?class=' + this.value;
    }
});

function applyPromotions() {
    const cards = document.querySelectorAll('[data-student-id]');
    const promotions = [];
    
    cards.forEach(card => {
        const studentId = card.dataset.studentId;
        const selectedRadio = card.querySelector(`input[name="promotion_${studentId}"]:checked`);
        if (selectedRadio) {
            promotions.push({
                id: studentId,
                action: selectedRadio.value
            });
        }
    });

    if (promotions.length === 0) {
        showModal('warning', 'No Changes', 'Please select promotion decisions for students.');
        return;
    }

    if (!confirm(`Are you sure you want to apply these promotions to ${promotions.length} student(s)? This action cannot be undone.`)) {
        return;
    }

    // Disable button and show loading
    event.target.disabled = true;
    event.target.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';

    // Process each promotion
    let processed = 0;
    let errors = 0;

    promotions.forEach(promotion => {
        fetch('api/promote_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(promotion)
        })
        .then(response => response.json())
        .then(data => {
            processed++;
            if (data.error) errors++;
            
            if (processed === promotions.length) {
                if (errors === 0) {
                    showModal('success', 'Success', `Successfully promoted ${promotions.length} student(s)!`);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showModal('error', 'Partial Success', `Processed ${processed} students with ${errors} error(s).`);
                    event.target.disabled = false;
                    event.target.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Apply Promotions';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            processed++;
            errors++;
            
            if (processed === promotions.length) {
                showModal('error', 'Error', `Failed to process promotions. Please try again.`);
                event.target.disabled = false;
                event.target.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Apply Promotions';
            }
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>
