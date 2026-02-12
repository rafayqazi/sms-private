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
$settings = $db->getSchoolSettings();
$allStudents = $db->readData();

// Filter students by class
$students = array_filter($allStudents, function($student) use ($class) {
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
    <title>Exam Slips - <?php echo htmlspecialchars($class); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --school-indigo: #4f46e5;
        }
        
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }

        body {
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        /* Essential A4 Container */
        .page-container {
            width: 210mm;
            height: 296.5mm; /* Fixed height is critical for A4 alignment */
            background: white;
            margin: 0 auto 40px;
            padding: 10mm;
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            overflow: hidden;
            page-break-after: always;
        }

        .slip-border {
            border: 4px double var(--school-indigo);
            padding: 8mm;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            flex: 1;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.03;
            width: 450px;
            pointer-events: none;
            z-index: 0;
            user-select: none;
        }

        @media print {
            body { background: white; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            .page-container {
                width: 210mm;
                height: 296.5mm;
                margin: 0 !important;
                padding: 10mm !important;
                border: none !important;
                box-shadow: none !important;
                page-break-after: always;
                page-break-inside: avoid;
            }
        }

        .school-color-text { color: var(--school-indigo); }
        .school-color-bg { background-color: var(--school-indigo); }
        .school-color-border { border-color: var(--school-indigo); }
    </style>
</head>
<body>

    <!-- UI Header -->
    <div class="max-w-[210mm] mx-auto mb-8 no-print flex justify-between items-center bg-white p-6 rounded-3xl shadow-xl border border-slate-100">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Examination Slips</h1>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Perfect A4 Alignment Mode</p>
        </div>
        <div class="flex gap-4">
            <button id="downloadPdfBtn" class="bg-indigo-600 text-white px-8 py-3 rounded-xl shadow-lg shadow-indigo-200/50 hover:bg-indigo-700 font-black flex items-center gap-2 transition-all active:scale-95 uppercase tracking-widest text-xs">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </button>
            <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3 rounded-xl shadow-lg shadow-slate-200/50 hover:bg-black font-black flex items-center gap-2 transition-all active:scale-95 uppercase tracking-widest text-xs">
                <i class="fas fa-print"></i> Print Now
            </button>
        </div>
    </div>

    <!-- Slips Wrapper -->
    <div id="pdf-content">
        <?php foreach ($students as $student): ?>
        <div class="page-container">
            <div class="slip-border">
                <!-- Watermark -->
                <img src="../assets/branding/logo.png" class="watermark">

                <!-- Header -->
                <div class="flex items-center gap-6 mb-4 border-b-4 school-color-border pb-4 relative z-10">
                    <img src="../assets/branding/logo.png?v=<?php echo time(); ?>" alt="Logo" class="w-20 h-20 object-contain">
                    <div class="flex-1 text-center">
                        <h1 class="text-3xl font-black uppercase tracking-tighter school-color-text leading-none">
                            <?php echo htmlspecialchars($settings['school_name']); ?>
                        </h1>
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 mt-1">
                            <?php echo htmlspecialchars($settings['address_tagline'] ?? 'Education for all'); ?>
                        </p>
                        <div class="mt-2 inline-block school-color-bg text-white px-8 py-1.5 rounded-lg text-lg font-black uppercase tracking-widest shadow-md">
                            Examination Slip
                        </div>
                    </div>
                </div>

                <!-- Exam Title -->
                <div class="text-center mb-6 relative z-10">
                    <span class="text-xl font-black text-slate-800 uppercase border-b-2 border-dotted school-color-border px-6 pb-0.5">
                        <?php echo htmlspecialchars($examName); ?>
                    </span>
                </div>

                <!-- Student Info Row -->
                <div class="flex gap-8 mb-6 relative z-10">
                    <div class="flex-1 space-y-4">
                        <div class="flex items-end gap-3">
                            <span class="font-black text-sm school-color-text uppercase tracking-widest shrink-0">Name:</span>
                            <div class="flex-1 border-b-2 border-slate-100 font-black text-xl uppercase text-slate-800 px-2 pb-0.5">
                                <?php echo htmlspecialchars($student['student_name']); ?>
                            </div>
                        </div>
                        <div class="flex items-end gap-3">
                            <span class="font-black text-sm school-color-text uppercase tracking-widest shrink-0">Father:</span>
                            <div class="flex-1 border-b-2 border-slate-100 font-bold text-xl uppercase text-slate-800 px-2 pb-0.5">
                                <?php echo htmlspecialchars($student['father_name']); ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-8">
                            <div class="flex items-end gap-3">
                                <span class="font-black text-sm school-color-text uppercase tracking-widest shrink-0">Class:</span>
                                <div class="flex-1 border-b-2 border-slate-100 font-black text-xl uppercase text-slate-800 px-2 pb-0.5">
                                    <?php echo htmlspecialchars($student['current_class']); ?>
                                </div>
                            </div>
                            <div class="flex items-end gap-3">
                                <span class="font-black text-sm school-color-text uppercase tracking-widest shrink-0">GR No:</span>
                                <div class="flex-1 border-b-2 border-slate-100 font-black text-xl text-indigo-600 px-2 pb-0.5">
                                    #<?php echo htmlspecialchars($student['gr_no']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Photo -->
                    <div class="w-32 h-36 border-4 school-color-border rounded-xl overflow-hidden bg-slate-50 shadow-xl relative flex-shrink-0">
                        <?php 
                        $img = !empty($student['profile_image']) && file_exists('../' . $student['profile_image']) 
                               ? '../' . $student['profile_image'] 
                               : null;
                        if($img): ?>
                            <img src="<?php echo $img; ?>?v=<?php echo time(); ?>" class="w-full h-full object-cover object-top">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center text-slate-200">
                                <i class="fas fa-user-circle text-4xl"></i>
                                <span class="text-[8px] font-black uppercase tracking-widest mt-1">No Photo</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Subjects Table -->
                <div class="flex-1 relative z-10 mb-6">
                    <table class="w-full text-left border-collapse border-b-4 school-color-border overflow-hidden rounded-t-xl">
                        <thead class="school-color-bg text-white">
                            <tr class="text-[10px] font-black uppercase tracking-widest">
                                <th class="p-3 border-r border-white/20">Examination Subjects</th>
                                <th class="p-3 border-r border-white/20 text-center w-32">Date</th>
                                <th class="p-3 border-r border-white/20 text-center w-32">Day</th>
                                <th class="p-3 border-r border-white/20 text-center w-36">Timing</th>
                                <th class="p-3 text-center w-40">Invigilator</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-700">
                            <?php 
                            $subjects = $_GET['subjects'] ?? [];
                            $dates = $_GET['dates'] ?? [];
                            $days = $_GET['days'] ?? [];
                            $times = $_GET['times'] ?? [];
                            
                            // Ensure at least 6 rows for layout consistency
                            $rowCount = max(6, count($subjects));
                            for($i=0; $i < $rowCount; $i++): 
                                $sub = htmlspecialchars($subjects[$i] ?? '');
                                $date = isset($dates[$i]) && $dates[$i] ? date('d-m-Y', strtotime($dates[$i])) : '';
                                $day = htmlspecialchars($days[$i] ?? '');
                                $time = htmlspecialchars($times[$i] ?? '');
                                $bg = ($i % 2 == 0) ? 'bg-white' : 'bg-slate-50/50';
                            ?>
                            <tr class="<?php echo $bg; ?> border-b border-slate-100 font-bold text-xs">
                                <td class="p-2.5 border-r border-slate-100 uppercase truncate max-w-[200px]"><?php echo $sub; ?></td>
                                <td class="p-2.5 border-r border-slate-100 text-center"><?php echo $date; ?></td>
                                <td class="p-2.5 border-r border-slate-100 text-center uppercase"><?php echo $day; ?></td>
                                <td class="p-2.5 border-r border-slate-100 text-center uppercase whitespace-nowrap"><?php echo $time; ?></td>
                                <td class="p-2.5"></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Section -->
                <div class="flex justify-between items-end bg-slate-50 p-4 rounded-2xl border border-slate-100 relative z-10">
                    <div class="space-y-1">
                        <h4 class="font-black text-[10px] uppercase school-color-text flex items-center gap-2">
                            <i class="fas fa-info-circle"></i> Instructions
                        </h4>
                        <ul class="text-[9px] font-bold text-slate-500 uppercase list-none space-y-0.5">
                            <li>1. Bring Original Slip Daily for Verification</li>
                            <li>2. Reach Examination Hall 15 Mins Before</li>
                            <li>3. No Electronic Devices Allowed</li>
                        </ul>
                    </div>
                    <div class="text-center group pr-2">
                        <div class="w-40 border-b-4 school-color-border mb-2 group-hover:scale-110 transition-transform"></div>
                        <p class="text-[10px] font-black uppercase tracking-widest school-color-text">School Principal</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty State -->
    <?php if (empty($students)): ?>
        <div class="bg-white rounded-3xl shadow-xl p-20 text-center max-w-2xl mx-auto border border-slate-100 mt-20">
            <i class="fas fa-users-slash text-6xl text-slate-200 mb-6"></i>
            <h2 class="text-3xl font-black text-slate-800 uppercase">No Students Found</h2>
            <p class="text-slate-500 font-bold mt-2">Class: <?php echo htmlspecialchars($class); ?></p>
        </div>
    <?php endif; ?>

    <script>
        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerHTML;
            
            // Re-confirm because this is a heavy operation for 37+ pages
            if (!confirm('Generating ' + <?php echo count($students); ?> + ' slips might take a moment. Proceed?')) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';

            const element = document.getElementById('pdf-content');
            
            // Standard A4 settings for high compatibility
            const options = {
                margin: 0,
                filename: 'Exam_Slips_Class_<?php echo addslashes($class); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2, 
                    useCORS: true,
                    logging: false,
                    letterRendering: true,
                    backgroundColor: '#ffffff',
                    scrollY: 0,
                    windowWidth: 1200 
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                pagebreak: { mode: 'css' }
            };

            // Use html2pdf worker for better memory management
            html2pdf().set(options).from(element).toPdf().get('pdf').then(function (pdf) {
                // Done
            }).save().then(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }).catch(err => {
                console.error('PDF Error:', err);
                btn.disabled = false;
                btn.innerHTML = originalText;
                alert('PDF Generation failed due to local memory limits. Please use the "Print Now" button to save as PDF via your browser.');
            });
        });
    </script>
</body>
</html>
