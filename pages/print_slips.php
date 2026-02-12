<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Verify Admin/Teacher permissions
if (!canAccessPage('students.php')) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['class']) || !isset($_GET['exam_name'])) {
    die("Invalid request. Class and Exam Name are required.");
}

$class = $_GET['class'];
$examName = $_GET['exam_name'];

$db = new Database();
$settings = $db->getSchoolSettings(); // Fetch settings for dynamic values
$allStudents = $db->readData();

// Filter students by class
$students = array_filter($allStudents, function($student) use ($class) {
    // Only active students
    $isAlumni = isset($student['student_status']) && $student['student_status'] === 'Alumni';
    return !$isAlumni && isset($student['current_class']) && $student['current_class'] === $class;
});

// Sort by GR No
usort($students, function($a, $b) {
    return (int)$a['gr_no'] - (int)$b['gr_no'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examination Slips - Class <?php echo htmlspecialchars($class); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            body {
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .slip-container {
                height: 100vh;
                margin: 0 !important;
                border: none !important;
                page-break-after: always;
            }
        }
        
        .slip-container {
            width: 210mm;
            height: 296.5mm; /* Reduced slightly to avoid sub-pixel bleed */
            padding: 10mm; /* Reduced padding */
            background: white;
            margin: 0 auto 10px;
            display: flex;
            flex-direction: column;
            position: relative;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            overflow: hidden; /* Prevent bleed */
        }

        .border-slips {
            border: 3px double #4f46e5;
            padding: 8mm; /* Reduced padding */
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            width: 300px; /* Reduced width */
            pointer-events: none;
            z-index: 0;
        }

        .school-color-text { color: #4f46e5; }
        .school-color-bg { background-color: #4f46e5; }
        .school-color-border { border-color: #4f46e5; }
    </style>
</head>
<body class="bg-slate-50 p-6 min-h-screen">

    <div class="max-w-[210mm] mx-auto mb-6 no-print flex justify-between items-center bg-white p-5 rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100">
        <div>
            <h1 class="text-xl font-black text-slate-800 uppercase tracking-tight">Examination Slips</h1>
            <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mt-0.5">Preview & Export Class <?php echo htmlspecialchars($class); ?></p>
        </div>
        <div class="flex gap-3">
            <button id="downloadPdfBtn" class="bg-emerald-600 text-white px-6 py-2.5 rounded-xl shadow-lg shadow-emerald-200/50 hover:bg-emerald-700 font-black flex items-center gap-2 transition-all active:scale-95 text-[10px] uppercase tracking-widest">
                <i class="fas fa-file-pdf"></i>
                Download PDF
            </button>
            <button onclick="window.print()" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl shadow-lg shadow-slate-200/50 hover:bg-black font-black flex items-center gap-2 transition-all active:scale-95 text-[10px] uppercase tracking-widest">
                <i class="fas fa-print"></i>
                Print Slips
            </button>
        </div>
    </div>

    <div id="slips-wrapper">
    <?php 
    foreach ($students as $student): 
    ?>
    
    <div class="slip-container">
        <div class="border-slips">
            <!-- Watermark -->
            <img src="../assets/branding/logo.png?v=<?php echo time(); ?>" class="watermark">

            <!-- Header -->
            <div class="flex items-center gap-6 mb-6 border-b-2 school-color-border pb-4 relative z-10 text-center">
                <img src="../assets/branding/logo.png?v=<?php echo time(); ?>" alt="Logo" class="w-24 h-24 object-contain">
                <div class="flex-1">
                    <h1 class="text-4xl font-black uppercase tracking-tight school-color-text mb-1"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                    <p class="text-base font-bold uppercase tracking-[0.2em] text-slate-600"><?php echo htmlspecialchars($settings['address_tagline']); ?></p>
                </div>
            </div>

            <div class="text-center mb-6 relative z-10">
                <div class="inline-block school-color-bg text-white px-8 py-2 font-black text-xl uppercase tracking-[0.1em] rounded-xl shadow-lg shadow-indigo-200">
                    Examination Slip
                </div>
                <div class="mt-4">
                    <span class="text-base font-black text-slate-800 uppercase border-b-2 border-dotted school-color-border px-6 py-0.5">
                        <?php echo htmlspecialchars($examName); ?>
                    </span>
                </div>
            </div>

            <!-- Student Info -->
            <div class="grid grid-cols-[1fr_auto] gap-8 relative z-10 mb-6">
                <div class="space-y-4">
                    <div class="flex items-end gap-3">
                        <span class="font-black text-base school-color-text uppercase tracking-widest shrink-0">Student Name:</span>
                        <div class="flex-1 border-b-2 border-slate-300 font-bold text-xl capitalize px-3 pb-0.5 text-slate-800">
                            <?php echo htmlspecialchars($student['student_name']); ?>
                        </div>
                    </div>
                    
                    <div class="flex items-end gap-3">
                        <span class="font-black text-base school-color-text uppercase tracking-widest shrink-0">Father Name:</span>
                        <div class="flex-1 border-b-2 border-slate-300 font-bold text-xl capitalize px-3 pb-0.5 text-slate-800">
                            <?php echo htmlspecialchars($student['father_name']); ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8">
                        <div class="flex items-end gap-3">
                            <span class="font-black text-base school-color-text uppercase tracking-widest shrink-0">Class:</span>
                            <div class="flex-1 border-b-2 border-slate-300 font-black text-xl px-3 pb-0.5 text-slate-800">
                                <?php echo htmlspecialchars($student['current_class']); ?>
                            </div>
                        </div>
                        <div class="flex items-end gap-3">
                            <span class="font-black text-base school-color-text uppercase tracking-widest shrink-0">GR No:</span>
                            <div class="flex-1 border-b-2 border-slate-300 font-black text-xl px-3 pb-0.5 text-indigo-600 tracking-tighter">
                                #<?php echo htmlspecialchars($student['gr_no']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Photo Area -->
                <div class="w-36 h-40 border-4 school-color-border rounded-2xl overflow-hidden shadow-xl shadow-slate-200 bg-slate-50 flex items-center justify-center relative">
                    <?php 
                    $imagePath = '';
                    if (!empty($student['profile_image'])) {
                        $possiblePaths = ['../' . $student['profile_image'], $student['profile_image']];
                        foreach ($possiblePaths as $path) {
                            if (file_exists($path)) { $imagePath = $path; break; }
                        }
                    }
                    if ($imagePath): ?>
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" class="w-full h-full object-cover object-top">
                    <?php else: ?>
                        <div class="text-center">
                            <i class="fas fa-camera text-2xl text-slate-200 mb-1"></i>
                            <span class="block text-[8px] text-slate-300 font-black uppercase tracking-widest">Photo</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Subjects Table -->
            <div class="mt-2 relative z-10 flex-1">
                <table class="w-full border-collapse rounded-2xl overflow-hidden shadow-lg shadow-slate-100 border border-slate-200">
                    <thead>
                        <tr class="school-color-bg text-white uppercase text-xs font-black tracking-widest">
                            <th class="py-4 px-4 text-left border-r border-white/20">Examination Subjects</th>
                            <th class="py-4 px-4 text-center border-r border-white/20 w-28">Date</th>
                            <th class="py-4 px-4 text-center border-r border-white/20 w-28">Day</th>
                            <th class="py-4 px-4 text-center border-r border-white/20 w-28">Timing</th>
                            <th class="py-4 px-4 text-left w-36">Invigilator</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-700">
                        <?php 
                        $subjects = $_GET['subjects'] ?? [];
                        $dates = $_GET['dates'] ?? [];
                        $days = $_GET['days'] ?? [];
                        $times = $_GET['times'] ?? [];
                        
                        if (empty($subjects)) {
                            for($i=0; $i<6; $i++) {
                                echo '<tr class="border-b border-slate-100 h-10 bg-white"><td class="border-r border-slate-100"></td><td class="border-r border-slate-100"></td><td class="border-r border-slate-100"></td><td class="border-r border-slate-100"></td><td></td></tr>';
                            }
                        } else {
                            for($i=0; $i < count($subjects); $i++) {
                                $sub = htmlspecialchars($subjects[$i] ?? '');
                                $date = isset($dates[$i]) ? date('d-m-Y', strtotime($dates[$i])) : '';
                                $day = htmlspecialchars($days[$i] ?? '');
                                $time = htmlspecialchars($times[$i] ?? '');
                                $bgColor = ($i % 2 == 0) ? 'bg-white' : 'bg-slate-50/50';
                                
                                echo "<tr class='border-b border-slate-100 h-10 $bgColor font-bold text-xs'>";
                                echo '<td class="border-r border-slate-100 px-4 py-2">' . $sub . '</td>';
                                echo '<td class="border-r border-slate-100 px-4 py-2 text-center">' . $date . '</td>';
                                echo '<td class="border-r border-slate-100 px-4 py-2 text-center">' . $day . '</td>';
                                echo '<td class="border-r border-slate-100 px-4 py-2 text-center">' . $time . '</td>';
                                echo '<td class="px-4 py-2"></td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Instructions & Signature -->
            <div class="mt-6 flex justify-between items-end relative z-10 bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                <div class="text-[10px] space-y-1">
                    <div class="flex items-center gap-2 text-slate-800 font-black uppercase tracking-tight">
                        <i class="fas fa-exclamation-triangle school-color-text"></i>
                        Security Instructions
                    </div>
                    <p class="text-slate-500 font-bold ml-5">1. Bring this original slip daily for verification.</p>
                    <p class="text-slate-500 font-bold ml-5">2. Electronic devices & mobiles are strictly prohibited.</p>
                    <p class="text-slate-500 font-bold ml-5">3. Be present in hall 15 mins before exam starts.</p>
                </div>
                <div class="text-center group pr-6">
                    <div class="w-36 border-b-2 school-color-border mb-2 group-hover:scale-105 transition-transform"></div>
                    <span class="font-black text-[10px] uppercase tracking-widest school-color-text">School Principal</span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <?php if (empty($students)): ?>
        <div class="bg-white rounded-3xl shadow-xl p-20 text-center max-w-2xl mx-auto border border-slate-100">
            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8 text-slate-200">
                <i class="fas fa-users-slash text-4xl"></i>
            </div>
            <h2 class="text-3xl font-black text-slate-800 mb-2">No Students Found</h2>
            <p class="text-slate-500 font-medium">There are no active students in Class <?php echo htmlspecialchars($class); ?> for this exam.</p>
        </div>
    <?php endif; ?>

    <script>
        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            const btn = this;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin text-lg"></i>';

            const element = document.getElementById('slips-wrapper');
            const opt = {
                margin: [0, 0, 0, 0],
                filename: 'Exam_Slips_Class_<?php echo addslashes($class); ?>.pdf',
                image: { type: 'jpeg', quality: 1 },
                html2canvas: { 
                    scale: 2, 
                    useCORS: true, 
                    letterRendering: true,
                    backgroundColor: '#ffffff'
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'] }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }).catch(err => {
                console.error('PDF Generation Error:', err);
                btn.disabled = false;
                btn.innerHTML = originalContent;
                alert('An error occurred during PDF generation. Please use the Print option.');
            });
        });
    </script>
</body>
</html>
