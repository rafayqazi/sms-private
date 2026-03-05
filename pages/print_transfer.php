<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$db = new Database();
$settings = $db->getSchoolSettings();
$allStudents = $db->readData();

$studentsToPrint = [];

// Bulk Print by Year
if (isset($_GET['year']) && !empty($_GET['year'])) {
    $year = $_GET['year'];
    $studentsToPrint = array_filter($allStudents, function($s) use ($year) {
        $gradYear = $s['graduation_year'] ?? (isset($s['updated_at']) ? date('Y', strtotime($s['updated_at'])) : '');
        return ($s['student_status'] ?? '') === 'Alumni' && $gradYear == $year;
    });
}
// Single Print by ID
elseif (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $id = $_GET['student_id'];
    $studentsToPrint = array_filter($allStudents, function($s) use ($id) {
        return $s['id'] == $id && ($s['student_status'] ?? '') === 'Alumni';
    });
}

if (empty($studentsToPrint)) {
    die('<div style="text-align:center; padding:50px; font-family:sans-serif;"><h2>No students found.</h2></div>');
}

// Sort if bulk
if (count($studentsToPrint) > 1) {
    usort($studentsToPrint, function($a, $b) {
        return (int)$a['gr_no'] - (int)$b['gr_no'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Certificate</title>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @page {
            size: A4 portrait;
            margin: 0;
        }
        
        @media print {
            html, body {
                width: 210mm;
                height: 297mm;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            .cert-page { 
                width: 210mm !important;
                height: 297mm !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-inside: avoid;
                page-break-after: always;
            }
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f0f0f0;
            padding: 10px;
            color: #000;
        }
        
        .cert-page {
            width: 210mm;
            height: 297mm;
            background: white;
            margin: 0 auto 20px;
            padding: 0; /* Removing padding to allow header-bar to bleed */
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Corner logo at intersection */
        /* Corner logo at intersection */
        .corner-logo {
            position: absolute;
            left: 10mm;
            top: 10mm;
            width: 25mm;
            height: 25mm;
            background: white;
            border-radius: 50%;
            border: 3px solid #0c4a96;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2mm;
            z-index: 10;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .logo-img { width: 100%; height: 100%; object-fit: contain; }

        /* Left vertical blue stripe */
        .left-stripe {
            position: absolute;
            left: 10mm;
            top: 35mm; /* Starts after logo (10mm + 25mm) */
            bottom: 15mm;
            width: 14mm;
            background: #0c4a96;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }

        .vertical-text {
            color: white;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 13pt; /* Reduced to fit long name */
            font-weight: bold;
            letter-spacing: 8px; /* Reduced to fit long name */
            white-space: nowrap;
            text-transform: uppercase;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Times New Roman', serif;
        }
        
        .cert-container {
            position: relative;
            z-index: 2;
            padding: 10mm 20mm 15mm 32mm;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header-bar {
            background: #0c4a96;
            color: white;
            padding: 4mm 10mm;
            display: flex;
            align-items: center;
            margin-bottom: 4mm;
            margin-left: 5mm; /* Shift right to start after logo area */
            height: 25mm; /* Match logo height */
            box-shadow: 2px 2px 10px rgba(0,0,0,0.1);
            border-radius: 0 4px 4px 0;
        }
        
        .logo-box {
            width: 20mm;
            height: 20mm;
            background: white;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1mm;
        }
        
        .logo-img { width: 100%; height: 100%; object-fit: contain; }
        
        .school-name {
            font-size: 17pt; /* Reduced to fit on one line */
            font-weight: bold;
            letter-spacing: 0.2px; /* Reduced to fit */
            flex: 1;
            text-align: center;
            white-space: nowrap;
            overflow: visible;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        
        .city-name {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            color: #0c4a96;
            margin-bottom: 8mm;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        
        .cert-title {
            text-align: center;
            font-size: 24pt;
            font-weight: bold;
            color: #0c4a96;
            text-decoration: underline;
            margin-bottom: 12mm;
            letter-spacing: 2px;
        }
        
        .enrolment {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 8mm;
        }

        .enrolment-val {
            border-bottom: 2px solid #000;
            display: inline-block;
            min-width: 50mm;
            padding-left: 4mm;
            font-size: 13pt;
        }

        .intro-text {
            font-size: 12pt;
            line-height: 2;
            margin-bottom: 12mm;
        }

        .underline-input {
            border-bottom: 1px solid #000;
            display: inline-block;
            font-weight: bold;
            padding: 0 2mm;
            min-width: 80mm;
            text-align: center;
            font-size: 12pt;
        }

        .sections {
            display: flex;
            flex-direction: column;
            gap: 6mm;
            font-size: 11pt;
        }

        .section-row {
            display: flex;
            align-items: baseline;
            gap: 2mm;
            line-height: 1.5;
        }

        .section-label {
            font-weight: bold;
        }

        .dotted-line {
            flex: 1;
            border-bottom: 1px dotted #000;
            min-height: 6mm;
            padding: 0 4mm;
            font-weight: bold;
            color: #333;
        }

        .date-input {
            width: 30mm;
            border-bottom: 1px solid #000;
            text-align: center;
            display: inline-block;
            font-weight: bold;
        }

        .sub-sections {
            display: flex;
            flex-direction: column;
            gap: 4mm;
            margin-top: 2mm;
        }

        .footer {
            margin-top: auto;
            display: flex;
            justify-content: flex-end;
            padding: 20mm 10mm 10mm;
        }

        .principal-box {
            text-align: center;
            min-width: 60mm;
            border-top: 2px solid #000;
            padding-top: 2mm;
        }

        .principal-label {
            font-size: 14pt;
            font-weight: bold;
            color: #000;
        }

        .watermark {
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            opacity: 0.04;
            z-index: 0;
            pointer-events: none;
        }

        /* Outer decorative border */
        .outer-border {
            position: absolute;
            top: 5mm;
            left: 5mm;
            right: 5mm;
            bottom: 5mm;
            border: 1px solid #0c4a96;
            pointer-events: none;
            z-index: 5;
        }

        .inner-border {
            position: absolute;
            top: 6.5mm;
            left: 6.5mm;
            right: 6.5mm;
            bottom: 6.5mm;
            border: 2px solid #0c4a96;
            pointer-events: none;
            z-index: 5;
        }
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px;">
        <button id="downloadPdfBtn" style="background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
            <i class="fas fa-download"></i> Download PDF
        </button>
        <button onclick="window.close()" style="background: #6b7280; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
            Close
        </button>
    </div>

    <script>
        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
            
            const certificates = document.querySelectorAll('.cert-page');
            const totalCerts = certificates.length;
            
            let filename = '';
            if (totalCerts === 1) {
                const firstCert = certificates[0];
                const studentName = firstCert.getAttribute('data-student-name') || 'Student';
                const grNo = firstCert.getAttribute('data-gr-no') || '000';
                filename = `TC_${studentName}_GR_${grNo}.pdf`;
            } else {
                const year = new URLSearchParams(window.location.search).get('year') || 'Bulk';
                filename = `Transfer_Certificates_${year}.pdf`;
            }

            const opt = {
                margin: 0,
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2, 
                    useCORS: true, 
                    letterRendering: true,
                    scrollX: 0,
                    scrollY: 0
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // Temporarily remove margins for clean capture
            if (totalCerts > 1) {
                certificates.forEach(cert => {
                    cert.style.margin = '0';
                    cert.style.marginBottom = '0';
                    cert.classList.remove('page-break');
                });
            }

            let worker = html2pdf().set(opt).from(certificates[0]).toPdf();
            
            for (let i = 1; i < totalCerts; i++) {
                (function(index) {
                    worker = worker.from(certificates[index]).toContainer().toCanvas().toPdf();
                })(i);
            }
            
            worker.save().then(() => {
                if (totalCerts > 1) {
                    certificates.forEach((cert, idx) => {
                        cert.style.margin = '0 auto 20px';
                        if (idx < totalCerts - 1) {
                            cert.classList.add('page-break');
                        }
                    });
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download"></i> Download PDF';
            }).catch(err => {
                console.error('PDF Error:', err);
                alert('Failed to generate PDF.');
                btn.disabled = false;
            });
        });
    </script>

    <div id="certificates-wrapper">
        <?php foreach ($studentsToPrint as $index => $student): 
            $studentName = strtoupper($student['student_name']);
            $fatherName = strtoupper($student['father_name']);
            $grNo = preg_replace('/[^0-9]/', '', $student['gr_no']);
            $gradYear = $student['graduation_year'] ?? '';
            
            // Gender detection
            $isFemale = (isset($student['gender']) && stripos($student['gender'], 'f') === 0) || 
                        stripos($studentName, 'MISS') !== false || 
                        stripos($studentName, 'KUMARI') !== false;
            
            $prefix = $isFemale ? 'Miss' : 'Mr.';
            $parentagePrefix = $isFemale ? 'D/O' : 'S/O';
            $pronoun = $isFemale ? 'She' : 'He';
            $possessivePronoun = $isFemale ? 'Her' : 'His';
            $objectivePronoun = $isFemale ? 'her' : 'him';

            // Subject detection based on GR suffix
            $subjectText = "PHYSICS, CHEMISTRY AND BIOLOGY / MATHS"; 
            if (!empty($student['gr_no'])) {
                if (stripos($student['gr_no'], 'P.M') !== false) {
                    $subjectText = "PHYSICS, CHEMISTRY AND BIOLOGY";
                } elseif (stripos($student['gr_no'], 'P.E') !== false) {
                    $subjectText = "PHYSICS, CHEMISTRY AND MATHS";
                }
            }
            
            $logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
                ? '../' . $settings['school_logo'] 
                : '../assets/branding/logo.png';
        ?>
        <div class="cert-page <?php echo ($index < count($studentsToPrint) - 1) ? 'page-break' : ''; ?>" 
             data-student-name="<?php echo htmlspecialchars($student['student_name']); ?>"
             data-gr-no="<?php echo htmlspecialchars($grNo); ?>">
            
            <div class="outer-border"></div>
            <div class="inner-border"></div>

            <div class="corner-logo">
                <img src="<?= $logoPath ?>?v=<?= time() ?>" class="logo-img" alt="Logo">
            </div>

            <div class="left-stripe">
                <div class="vertical-text"><?php echo strtoupper(htmlspecialchars($settings['school_name'])); ?></div>
            </div>
            <img src="<?= $logoPath ?>?v=<?= time() ?>" class="watermark" alt="">

            <div class="cert-container">
                <div class="header-bar">
                    <div class="school-name"><?php echo strtoupper(htmlspecialchars($settings['school_name'])); ?></div>
                </div>

                <div class="city-name"><?php echo strtoupper(htmlspecialchars($settings['address_tagline'])); ?></div>

                <div class="cert-title">TRANSFER CERTIFICATE</div>

                <div class="enrolment">
                    Enrolment No. <span class="enrolment-val"><?php echo $grNo; ?></span>
                </div>

                <div class="intro-text">
                    This is to Certify that <?= $prefix ?> <span class="underline-input"><?php echo $studentName; ?> <?= $parentagePrefix ?> <?php echo $fatherName; ?></span><br>
                    <?= $pronoun ?> been a student of this school.
                </div>

                <div class="sections">
                    <div class="section-row">
                        <span class="section-label">A) Since passing the S.S.C II Examination, He/She kept term in this school as under</span>
                    </div>
                    <div class="sub-sections">
                        <div class="section-row">
                            August 20<span class="date-input"></span> to December 20<span class="date-input"></span>
                        </div>
                        <div class="section-row">
                            January 20<span class="date-input"></span> to May 20<span class="date-input"></span>
                        </div>
                    </div>

                    <div class="section-row">
                        <span class="section-label">B) <?= $possessivePronoun ?> work the school Examination was as following:</span>
                    </div>

                    <div class="section-row" style="white-space: nowrap;">
                        <span class="section-label">C) Passing the H.S.C II Examination in the Year Annual __________</span>
                        <span class="section-label">under Seat No :________</span>
                    </div>

                    <div class="section-row">
                        <span class="section-label">D) <?= $pronoun ?> has no books belonging to this school in <?= $possessivePronoun ?> possessn.</span>
                    </div>

                    <div class="section-row">
                        <span class="section-label">E) Nothing is owed by <?= $objectivePronoun ?> on account of school.</span>
                    </div>

                    <div class="section-row">
                        <span class="section-label">F) <?= $possessivePronoun ?> conduct and character are good.</span>
                    </div>

                    <div class="section-row">
                        <span class="section-label">G) <?= $pronoun ?> has attended course of instruction in the optional subject of</span>
                    </div>
                    <div class="section-row" style="padding-left: 10mm; display: block; text-align: left;">
                        <span style="border-bottom: 2px solid #000; font-weight: bold; padding: 0 4mm 0 0; display: inline-block;"><?php echo $subjectText; ?></span>
                    </div>

                    <div class="section-row">
                        <span class="section-label">H) <?= $possessivePronoun ?> Principal subjects were English, Sindhi, Urdu Salees / Urdu Comp:</span>
                    </div>
                    <div class="section-row" style="padding-left: 10mm;">
                        <span class="section-label">P.S, Isl. Education.</span>
                    </div>

                    <div class="section-row">
                        <span class="section-label">I) <?= $pronoun ?> has satisfactorily carried out the Practical works in science subjects by</span>
                    </div>
                    <div class="section-row" style="padding-left: 10mm;">
                        <span class="section-label">Performing the necessary experiments.</span>
                    </div>
                </div>

                <div class="footer" style="margin-top: auto; display: flex; justify-content: flex-end; padding: 10mm 10mm 5mm; position: relative; z-index: 100;">
                    <div class="principal-box" style="text-align: center; min-width: 60mm; border-top: 2px solid #000; padding-top: 2mm;">
                        <span style="font-size: 14pt; font-weight: bold; color: #000; display: block; text-transform: none;">Principal</span>
                    </div>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
