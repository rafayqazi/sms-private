<?php
require_once '../includes/parent_or_staff_auth.php';
require_once '../includes/functions.php';
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

$resultClass = !empty($result['class']) ? $result['class'] : $student['current_class'];
$activeSubjects = $database->getActiveSubjects($resultClass, $examType, $year);
$subjectMaxMarks = $database->getSubjectMaxMarks($resultClass, $examType, $year);
if (isEditor()) {
    $assigned = getAssignedClasses();
    if (!in_array($resultClass, $assigned)) {
        die("Unauthorized access to print this student's result.");
    }
}

if (!$result) {
    $result = [
        'english' => 0, 'math' => 0, 'social_studies' => 0, 'general_science' => 0, 
        'mt' => 0, 'islamiyat' => 0, 'nmt' => 0,
        'total_max' => array_sum($subjectMaxMarks), 'total_obtained' => 0, 'percentage' => 0, 'grade' => '-',
        'other_subjects' => '{}'
    ];
}

$brandColor = "#0c0784";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Card - <?= htmlspecialchars($student['student_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');
        
        body { 
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }

        .brand-text { color: <?= $brandColor ?>; }
        .brand-bg { background-color: <?= $brandColor ?>; }
        .brand-border { border-color: <?= $brandColor ?>; }

        /* A4 Page Container */
        .a4-container {
            width: 210mm;
            height: 297mm;
            margin: 10px auto;
            background: white;
            box-sizing: border-box;
            position: relative;
            padding: 10mm;
            overflow: hidden;
        }

        .inner-border {
            border: 3px solid <?= $brandColor ?>;
            height: 100%;
            padding: 8mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            opacity: 0.04;
            width: 140mm;
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        table { border-collapse: collapse; width: 100%; }
        th { 
            background-color: <?= $brandColor ?>; 
            color: white;
            text-transform: uppercase;
            font-size: 11px;
            padding: 10px;
        }
        th, td { border: 1.2px solid <?= $brandColor ?>; padding: 8px 12px; }

        .field-label { font-weight: 700; color: #000; font-size: 13px; text-transform: uppercase; }
        .field-value { border-bottom: 2px solid #e2e8f0; font-weight: 600; font-size: 15px; margin-left: 8px; flex: 1; color: #000; }

        @media print {
            body { background: transparent; }
            .a4-container { margin: 0; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <!-- Controls -->
    <div class="fixed top-4 right-4 flex gap-2 no-print z-50">
        <button id="downloadBtn" onclick="generatePDF()" class="brand-bg hover:opacity-90 text-white font-bold py-2.5 px-6 rounded-lg shadow-xl flex items-center gap-2">
            <i class="fas fa-download"></i> Save as PDF
        </button>
        <button onclick="window.print()" class="bg-gray-800 text-white font-bold py-2.5 px-6 rounded-lg">
            <i class="fas fa-print"></i> Print
        </button>
    </div>

    <div id="capture">
    <div class="a4-container">
        <div class="inner-border">
            <?php 
            $logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
                        ? '../' . $settings['school_logo'] 
                        : '../assets/branding/logo.png';
            ?>
            <img src="<?= $logoPath ?>?v=<?= time() ?>" class="watermark">
            
            <div class="content">
                <!-- Header with Logo and Photo -->
                <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-slate-100">
                    <!-- Left: School Logo -->
                    <div class="w-24 h-24 flex-shrink-0">
                        <img src="<?= $logoPath ?>?v=<?= time() ?>" alt="Logo" class="w-full h-full object-contain">
                    </div>

                    <!-- Center: School Details -->
                    <div class="flex-1 text-center px-4">
                        <!-- 1. School Name (One Line) -->
                        <h1 class="text-2xl font-black brand-text uppercase leading-tight"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                        
                        <!-- 2. Address (One Line) -->
                        <p class="text-[13px] font-bold text-slate-600 uppercase mt-1">
                            <?php echo htmlspecialchars($settings['address_tagline']); ?>
                        </p>
                        
                        <!-- 3. Examination Name (One Line) -->
                        <div class="mt-4">
                            <h2 class="text-lg font-extrabold text-black underline decoration-2 decoration-indigo-300 uppercase tracking-widest">
                                <?= $examType ?> EXAMINATION - SESSION <?= $year ?>
                            </h2>
                        </div>
                    </div>

                    <!-- Right: Student Photo -->
                    <?php 
                        $imagePath = $student['profile_image'];
                        if (!$imagePath || !file_exists('../' . $imagePath)) {
                            // High-quality UI-Avatar as fallback
                            $imagePath = 'https://ui-avatars.com/api/?name=' . urlencode($student['student_name']) . '&background=0c0784&color=fff&size=200&bold=true';
                        } else {
                            $imagePath = '../' . $imagePath;
                        }
                    ?>
                    <div class="w-24 h-28 border-2 brand-border bg-white flex-shrink-0 rounded-lg overflow-hidden p-1 shadow-sm">
                        <img src="<?= htmlspecialchars($imagePath) ?>" class="w-full h-full object-cover object-top rounded-md">
                    </div>
                </div>

                <!-- Marksheet Identifiers -->
                <div class="grid grid-cols-2 gap-x-10 gap-y-4 mb-8">
                    <div class="flex items-end">
                        <span class="field-label">Student:</span>
                        <span class="field-value uppercase"><?= htmlspecialchars($student['student_name']) ?></span>
                    </div>
                    <div class="flex items-end">
                        <span class="field-label">Father Name:</span>
                        <span class="field-value uppercase"><?= htmlspecialchars($student['father_name']) ?></span>
                    </div>
                    <div class="flex items-end">
                        <span class="field-label">Class:</span>
                        <span class="field-value uppercase text-black"><?= htmlspecialchars(!empty($result['class']) ? $result['class'] : $student['current_class']) ?></span>
                    </div>
                    <div class="flex items-end">
                        <span class="field-label">G.R. No:</span>
                        <span class="field-value font-black"><?= htmlspecialchars($student['gr_no']) ?></span>
                    </div>
                </div>

                <!-- Result Table -->
                <div class="mb-8 border-2 brand-border">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left py-3">Subject Name</th>
                                <th class="text-center w-28">Max Marks</th>
                                <th class="text-center w-28">Obtained</th>
                                <th class="text-left">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php
                            $otherSubjectsData = isset($result['other_subjects']) ? json_decode($result['other_subjects'], true) : [];
                            if (!is_array($otherSubjectsData)) {
                                $otherSubjectsData = [];
                            }
                            $rowCount = 0;
                            foreach ($activeSubjects as $subject):
                                $key = $subject['key'];
                                $marks = ($subject['type'] === 'standard')
                                    ? ($result[$key] ?? 0)
                                    : ($otherSubjectsData[$key] ?? 0);
                                $subjectMax = (int)($subjectMaxMarks[$key] ?? 100);
                                $passMark = $subjectMax * 0.33;
                                $markVal = strtoupper(trim((string)$marks));
                                $passed = ($markVal !== 'A') && ((float)$marks >= $passMark);
                                $rowBg = ($rowCount++ % 2 == 0) ? 'bg-white' : 'bg-slate-50';
                            ?>
                            <tr class="<?= $rowBg ?> border-b">
                                <td class="font-bold py-3 px-4 text-black"><?= htmlspecialchars($subject['print_label']) ?></td>
                                <td class="text-center text-slate-500"><?= $subjectMax ?></td>
                                <td class="text-center font-black <?= $passed ? '' : 'text-red-500' ?>"><?= htmlspecialchars($marks) ?></td>
                                <td class="font-bold italic text-xs <?= $passed ? 'text-emerald-600' : 'text-red-500' ?>"><?= $passed ? 'PASS' : 'FAIL' ?></td>
                            </tr>
                            <?php endforeach; ?>

                            <tr class="brand-bg text-white font-bold h-14">
                                <td class="text-right px-6 uppercase tracking-widest text-xs">Total Marks Obtained</td>
                                <td class="text-center border-l border-white/20"><?= $result['total_max'] ?></td>
                                <td class="text-center text-xl border-l border-white/20"><?= $result['total_obtained'] ?></td>
                                <td class="px-4 border-l border-white/20">
                                     <div class="flex items-center justify-between">
                                        <span><?= $result['percentage'] ?>%</span>
                                        <span class="bg-white/20 px-3 py-1 rounded text-xs uppercase">Grade: <?= $result['grade'] ?></span>
                                     </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Signatures -->
                <div class="mt-auto flex justify-between items-end pb-4">
                    <div class="text-center">
                        <div class="w-56 border-b-2 border-slate-900 mb-2 h-10"></div>
                        <p class="text-xs font-bold text-black uppercase">Class Teacher Signature</p>
                    </div>
                    <div class="text-center">
                        <div class="w-56 border-b-2 border-slate-900 mb-2 h-10"></div>
                        <p class="text-xs font-bold brand-text uppercase">PRINCIPAL SIGNATURE</p>
                    </div>
                </div>

                <!-- Footer Tagline -->
                <div class="text-center border-t border-slate-100 mt-6 pt-3">
                    <p class="text-[9px] uppercase tracking-[0.4em] text-slate-300 font-bold">
                        AQSA School Management System
                    </p>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        function generatePDF() {
            const element = document.getElementById('capture');
            const btn = document.getElementById('downloadBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            btn.disabled = true;

            const opt = {
                margin: 0,
                filename: 'Marksheet_<?= addslashes($student['student_name']) ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 3, useCORS: true, letterRendering: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btn.innerHTML = '<i class="fas fa-download"></i> Save as PDF';
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
