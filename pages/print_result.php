<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

if (!isset($_GET['id']) || !isset($_GET['exam_type'])) {
    die("Invalid Request");
}

$database = new Database();
$settings = $database->getSchoolSettings();
$studentId = $_GET['id'];
$examType = $_GET['exam_type'];
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$student = $database->getStudent($studentId);
$result = $database->getStudentResult($studentId, $examType, $year);

if (!$student) {
    die("Student not found.");
}

// If result not found, initialize empty structure
if (!$result) {
    $result = [
        'english' => 0, 'math' => 0, 'social_studies' => 0, 'general_science' => 0, 
        'mt' => 0, 'islamiyat' => 0, 'nmt' => 0,
        'total_max' => 700, 'total_obtained' => 0, 'percentage' => 0, 'grade' => '-',
        'other_subjects' => '{}' // Empty JSON
    ];
    // Add extra subjects logic if needed, but for now basic is fine
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Card - <?= htmlspecialchars($student['student_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { 
                -webkit-print-color-adjust: exact;
                font-family: 'Times New Roman', Times, serif;
            }
        }
        body { font-family: 'Times New Roman', Times, serif; }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1;
            width: 400px;
            pointer-events: none;
            z-index: 0;
        }
        .content-relative {
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-white p-8">
    <div class="max-w-2xl mx-auto border-4 border-double border-gray-800 p-8 relative overflow-hidden">
        <!-- Watermark -->
        <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>" class="watermark">
        
        <div class="content-relative">
        <!-- Header -->
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div class="w-24 h-24 flex-shrink-0">
                <img src="../GBPS_LOGO.png?v=<?php echo time(); ?>" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="flex-1 text-center px-4">
                <h1 class="text-2xl font-bold text-gray-900 uppercase leading-tight"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                <h2 class="text-lg font-bold text-gray-800 uppercase mt-1 whitespace-nowrap"><?php echo htmlspecialchars($settings['address_tagline']); ?></h2>
                <p class="text-sm font-bold uppercase tracking-widest mt-1">SEMIS CODE: <?php echo htmlspecialchars($settings['semis_code']); ?></p>
                <h3 class="text-lg font-semibold text-gray-600 mt-2 uppercase">Result Card - <?= $examType ?> Examination</h3>
                <p class="text-sm text-gray-500 mt-1">Session <?= $year ?>-<?= $year + 1 ?></p>
            </div>
            <!-- Student Profile Image -->
            <div class="w-24 h-24 flex-shrink-0 border border-gray-300 flex items-center justify-center bg-gray-50 overflow-hidden">
                <?php if (!empty($student['profile_image']) && file_exists($student['profile_image'])): ?>
                    <img src="<?= htmlspecialchars($student['profile_image']) ?>" alt="Student Photo" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-xs text-gray-400 text-center">Photo</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Info -->
        <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
            <div class="flex">
                <span class="font-bold w-24">Name:</span>
                <span class="border-b border-gray-400 flex-1"><?= htmlspecialchars($student['student_name']) ?></span>
            </div>
            <div class="flex">
                <span class="font-bold w-24">Father Name:</span>
                <span class="border-b border-gray-400 flex-1"><?= htmlspecialchars($student['father_name']) ?></span>
            </div>
            <div class="flex">
                <span class="font-bold w-24">Class:</span>
                <span class="border-b border-gray-400 flex-1"><?= htmlspecialchars($student['current_class']) ?></span>
            </div>
            <div class="flex">
                <span class="font-bold w-24">GR No:</span>
                <span class="border-b border-gray-400 flex-1"><?= htmlspecialchars($student['gr_no']) ?></span>
            </div>
        </div>

        <!-- Marks Table -->
        <table class="w-full border-collapse border border-gray-800 mb-8 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-800 px-4 py-2 text-left">Subject</th>
                    <th class="border border-gray-800 px-4 py-2 text-center w-24">Max Marks</th>
                    <th class="border border-gray-800 px-4 py-2 text-center w-24">Obtained</th>
                    <th class="border border-gray-800 px-4 py-2 text-left">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $subjects = [
                    'English' => 'english',
                    'Mathematics' => 'math',
                    'Social Studies' => 'social_studies',
                    'General Science' => 'general_science',
                    'Mother Tongue (MT)' => 'mt',
                    'Islamiyat' => 'islamiyat',
                    'N.M.T' => 'nmt'
                ];
                foreach ($subjects as $label => $key):
                    $marks = $result[$key];
                    $max = 100; // Assuming 100 for now
                ?>
                <tr>
                    <td class="border border-gray-800 px-4 py-2"><?= $label ?></td>
                    <td class="border border-gray-800 px-4 py-2 text-center"><?= $max ?></td>
                    <td class="border border-gray-800 px-4 py-2 text-center font-bold"><?= $marks ?></td>
                    <td class="border border-gray-800 px-4 py-2 text-sm italic">
                        <?= $marks >= 33 ? 'Pass' : 'Fail' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="bg-gray-100 font-bold">
                    <td class="border border-gray-800 px-4 py-2 text-right">Total</td>
                    <td class="border border-gray-800 px-4 py-2 text-center"><?= $result['total_max'] ?></td>
                    <td class="border border-gray-800 px-4 py-2 text-center"><?= $result['total_obtained'] ?></td>
                    <td class="border border-gray-800 px-4 py-2">
                        <?= $result['percentage'] ?>% (<?= $result['grade'] ?>)
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Footer Signatures -->
        <div class="flex justify-between mt-16 pt-8">
            <div class="text-center">
                <div class="w-40 border-t border-gray-800 mb-2"></div>
                <p class="font-bold text-sm">Class Teacher</p>
            </div>
            <div class="text-center">
                <div class="w-40 border-t border-gray-800 mb-2"></div>
                <p class="font-bold text-sm"><?php echo htmlspecialchars($settings['headmaster_name']); ?></p>
            </div>
        </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
