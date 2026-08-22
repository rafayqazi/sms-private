<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$class = isset($_GET['class']) ? $_GET['class'] : '';
$examName = isset($_GET['exam_name']) ? $_GET['exam_name'] : '';
$subjects = isset($_GET['subjects']) ? $_GET['subjects'] : [];
$dates = isset($_GET['dates']) ? $_GET['dates'] : [];
$days = isset($_GET['days']) ? $_GET['days'] : [];
$times = isset($_GET['times']) ? $_GET['times'] : [];

if (!$class) {
    die("Class is required.");
}

// Clean up empty subjects
$cleanSubjects = [];
for($i=0; $i<count($subjects); $i++) {
    if(!empty($subjects[$i])) {
        $cleanSubjects[] = [
            'name' => $subjects[$i],
            'date' => isset($dates[$i]) ? $dates[$i] : '',
            'time' => isset($times[$i]) ? $times[$i] : ''
        ];
    }
}

$db = new Database();
$settings = $db->getSchoolSettings();
$allStudents = $db->readData();
$students = [];
foreach ($allStudents as $student) {
    if (isset($student['current_class']) && $student['current_class'] == $class) {
        if (isset($student['student_status']) && $student['student_status'] === 'Alumni') continue;
        $students[] = $student;
    }
}

usort($students, function($a, $b) {
    return intval($a['gr_no']) - intval($b['gr_no']);
});

$logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
    ? '../' . $settings['school_logo'] 
    : '../assets/branding/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo htmlspecialchars($class); ?> - <?php echo htmlspecialchars($examName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }
            body { background: white; }
            .no-print { display: none !important; }
            .sheet-container {
                width: 100vw;
                height: 100vh;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
        }
        
        .sheet-container {
            width: 297mm; /* A4 Landscape width */
            min-height: 210mm;
            padding: 10mm;
            background: white;
            margin: 20px auto;
            position: relative;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            border-radius: 1rem;
        }

        .border-sheet {
            border: 2px solid #4f46e5;
            padding: 8mm;
            height: 100%;
            display: flex;
            flex-direction: column;
            border-radius: 0.75rem;
        }

        .school-color-text { color: #4f46e5; }
        .school-color-bg { background-color: #4f46e5; }
        .school-color-border { border-color: #4f46e5; }

        table th { background-color: #4f46e5; color: white; border-color: #4338ca; }
        table td { border-color: #e2e8f0; }
        
        /* Attendance grid optimization */
        .attendance-cell {
            min-width: 65px;
            max-width: 85px;
            font-size: 9px;
        }
    </style>
</head>
<body class="bg-slate-50 p-8 min-h-screen font-sans">

    <!-- Control Bar -->
    <div class="max-w-[297mm] mx-auto mb-8 no-print flex justify-between items-center bg-white p-6 rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Examination Attendance</h1>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Consolidated Sheet | Class <?php echo htmlspecialchars($class); ?></p>
        </div>
        <div class="flex gap-4">
            <button id="downloadPdfBtn" class="bg-emerald-600 text-white px-8 py-3.5 rounded-2xl shadow-xl shadow-emerald-200/50 hover:bg-emerald-700 font-black flex items-center gap-3 transition-all active:scale-95 text-xs uppercase tracking-[0.2em]">
                <i class="fas fa-file-pdf text-lg"></i>
                Download PDF
            </button>
            <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3.5 rounded-2xl shadow-xl shadow-slate-200/50 hover:bg-black font-black flex items-center gap-3 transition-all active:scale-95 text-xs uppercase tracking-[0.2em]">
                <i class="fas fa-print text-lg"></i>
                Universal Print
            </button>
        </div>
    </div>

    <!-- Attendance Sheet Container -->
    <div id="attendance-sheet" class="sheet-container">
        <div class="border-sheet relative overflow-hidden">
            <!-- Header -->
            <div class="flex items-center gap-8 mb-6 border-b-2 school-color-border pb-6 relative z-10">
                <img src="<?= $logoPath ?>?v=<?php echo time(); ?>" alt="Logo" class="w-20 h-20 object-contain">
                <div class="flex-1 text-center">
                    <h1 class="text-4xl font-black uppercase tracking-tight school-color-text mb-1"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                    <p class="text-xs font-bold uppercase tracking-[0.4em] text-slate-500"><?php echo htmlspecialchars($settings['address_tagline'] ?? 'Education is Life'); ?></p>
                    <div class="mt-4 inline-block school-color-bg text-white px-10 py-2 rounded-xl text-lg font-black uppercase tracking-widest">
                        Consolidated Attendance Sheet
                    </div>
                </div>
                <div class="w-20 text-right">
                    <p class="text-[10px] font-black uppercase text-slate-400">Class</p>
                    <p class="text-2xl font-black school-color-text"><?php echo htmlspecialchars($class); ?></p>
                </div>
            </div>

            <!-- Meta Info -->
            <div class="flex justify-between items-center mb-6 px-1 text-xs font-black uppercase tracking-widest text-slate-600">
                <div>EXAM: <span class="school-color-text ml-2"><?php echo htmlspecialchars($examName); ?></span></div>
                <div>SESSION: <span class="school-color-text ml-2"><?php echo date('Y'); ?>-<?php echo date('y', strtotime('+1 year')); ?></span></div>
                <div>TOTAL STUDENTS: <span class="school-color-text ml-2"><?php echo count($students); ?></span></div>
            </div>

            <!-- Attendance Table -->
            <div class="flex-1 overflow-visible">
                <table class="w-full border-collapse border-2 school-color-border text-[10px]">
                    <thead>
                        <tr>
                            <th class="border-2 school-color-border p-3 w-10 text-center uppercase tracking-tighter">S#</th>
                            <th class="border-2 school-color-border p-3 w-20 text-center uppercase tracking-wider">GR No</th>
                            <th class="border-2 school-color-border p-3 text-left w-56 uppercase tracking-widest">Student Information</th>
                            
                            <?php 
                            $fetchAttendance = isset($_GET['fetch_attendance']) && $_GET['fetch_attendance'] == '1';
                            $attendanceData = [];
                            if ($fetchAttendance) {
                                foreach ($cleanSubjects as $sub) {
                                    $attendanceData[$sub['name']] = $db->getExamAttendance($examName, $class, $sub['name'], $sub['date']);
                                }
                            }
                            
                            foreach ($cleanSubjects as $sub): ?>
                            <th class="border-2 school-color-border p-2 text-center attendance-cell">
                                <div class="font-black truncate"><?php echo htmlspecialchars($sub['name']); ?></div>
                                <div class="text-[8px] font-bold opacity-80 mt-1">
                                    <?php echo date('d-m-Y', strtotime($sub['date'])); ?>
                                </div>
                            </th>
                            <?php endforeach; ?>
                            
                            <th class="border-2 school-color-border p-3 w-16 text-center uppercase">Remarks</th>
                            <th class="border-2 school-color-border p-3 w-28 text-center uppercase leading-tight">Invigilator<br>Sign</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-800">
                        <?php 
                        $count = 1;
                        foreach ($students as $student): 
                            $rowBg = ($count % 2 == 0) ? 'bg-slate-50/50' : 'bg-white';
                        ?>
                        <tr class="<?php echo $rowBg; ?> hover:bg-indigo-50/30 transition-colors">
                            <td class="border-2 border-slate-200 p-2 text-center font-bold text-slate-400 bg-slate-50/80"><?php echo $count++; ?></td>
                            <td class="border-2 border-slate-200 p-2 text-center font-black school-color-text"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                            <td class="border-2 border-slate-200 p-2">
                                <div class="font-black uppercase text-[11px]"><?php echo htmlspecialchars($student['student_name']); ?></div>
                                <div class="text-[9px] font-bold text-slate-400 uppercase"><?php echo htmlspecialchars($student['father_name']); ?></div>
                            </td>
                            
                            <?php foreach ($cleanSubjects as $sub): ?>
                            <td class="border-2 border-slate-200 p-2 text-center align-middle relative">
                                <?php 
                                if ($fetchAttendance && isset($attendanceData[$sub['name']][$student['id']])) {
                                    $status = $attendanceData[$sub['name']][$student['id']];
                                    $statusColor = ($status == 'P') ? 'bg-emerald-500' : 'bg-rose-500';
                                    echo "<span class='inline-block w-6 h-6 leading-6 rounded-md $statusColor text-white font-black'>$status</span>";
                                } else {
                                    echo '<div class="h-6 w-full border-b border-dotted border-slate-200"></div>';
                                }
                                ?>
                            </td>
                            <?php endforeach; ?>
                            
                            <td class="border-2 border-slate-200 p-2"></td>
                            <td class="border-2 border-slate-200 p-2 border-r school-color-border"></td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- Empty rows for manual entry -->
                        <?php for($k=0; $k<2; $k++): ?>
                        <tr class="bg-white/50">
                            <td class="border-2 border-slate-200 p-3 bg-slate-50/80"></td>
                            <td class="border-2 border-slate-200"></td>
                            <td class="border-2 border-slate-200"></td>
                            <?php foreach ($cleanSubjects as $sub): ?>
                            <td class="border-2 border-slate-200"></td>
                            <?php endforeach; ?>
                            <td class="border-2 border-slate-200"></td>
                            <td class="border-2 border-slate-200"></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="mt-8 flex justify-between items-end bg-slate-50 p-6 rounded-2xl border border-slate-100 relative z-10">
                <div class="space-y-4">
                    <!-- Space for manual notes -->
                </div>
                <div class="flex gap-20">
                    <div class="text-center group">
                        <div class="w-48 border-b-2 border-slate-300 mb-2 transition-transform group-hover:scale-110"></div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Class Incharge</p>
                    </div>
                    <div class="text-center group pr-4">
                        <div class="w-48 border-b-2 school-color-border mb-2 transition-transform group-hover:scale-110 school-color-border"></div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] school-color-text">Principal Signature</p>
                        <p class="text-[10px] font-black text-slate-900 mt-1 uppercase"><?php echo htmlspecialchars($settings['headmaster_name'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            const btn = this;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin text-lg"></i>';

            const element = document.getElementById('attendance-sheet');
            const opt = {
                margin: [5, 5, 5, 5],
                filename: 'Exam_Attendance_Class_<?php echo addslashes($class); ?>.pdf',
                image: { type: 'jpeg', quality: 1 },
                html2canvas: { 
                    scale: 2, 
                    useCORS: true, 
                    letterRendering: true,
                    backgroundColor: '#ffffff',
                    logging: false
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
                pagebreak: { mode: ['css', 'legacy'] }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }).catch(err => {
                console.error('PDF Generation Error:', err);
                btn.disabled = false;
                btn.innerHTML = originalContent;
                alert('An error occurred during PDF generation.');
            });
        });
    </script>
</body>
</html>
