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
    <title>School Leaving Certificate</title>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @page {
            size: A4 portrait;
            margin: 10mm;
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
                margin: 0 auto !important;
                padding: 0 !important;
                page-break-inside: avoid;
                page-break-after: always;
            }
            .cert-page:last-child {
                page-break-after: auto;
            }
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: #f0f0f0;
            padding: 10px;
        }
        
        .cert-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            background: white;
            margin: 0 auto 20px;
            padding: 6mm;
            position: relative;
            overflow: hidden;
        }
        
        .border-outer {
            width: 100%;
            height: 100%;
            border: 2px solid #0c0784;
            padding: 2.5mm;
        }
        
        .border-inner {
            width: 100%;
            height: 100%;
            border: 3px double #0c0784;
            padding: 5mm 6mm;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50%;
            opacity: 0.05;
            z-index: 0;
        }
        
        .content {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4mm;
        }
        
        .logo { width: 28mm; height: 28mm; object-fit: contain; }
        
        .school-info {
            flex: 1;
            text-align: left;
            padding: 0 5mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .school-name-wrapper {
            display: flex;
            align-items: baseline;
            gap: 2.5mm;
            flex-wrap: nowrap;
            white-space: nowrap;
        }
        
        .school-name-large {
            font-family: 'Anton', sans-serif;
            font-size: 48pt;
            color: #0c0784;
            text-transform: uppercase;
            line-height: 0.8;
            letter-spacing: 1.5px;
            font-weight: 400;
        }
        
        .school-name-small {
            font-family: 'Anton', sans-serif;
            font-size: 18pt;
            color: #0c0784;
            text-transform: uppercase;
            line-height: 1;
            letter-spacing: 0.8px;
            font-weight: 400;
            white-space: nowrap;
        }
        
        .school-location {
            font-size: 13pt;
            font-weight: 700;
            color: #0c0784;
            margin-top: 2mm;
            text-align: center;
        }
        
        .cert-title {
            text-align: center;
            font-size: 16pt;
            font-weight: 700;
            color: #0c0784;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 5mm;
        }
        
        .fields {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .fields-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 4mm;
            padding: 1.5mm 0 3.5mm;
        }
        
        .field-row {
            display: flex;
            align-items: flex-end;
            gap: 2.5mm;
            min-height: 7.5mm;
        }
        
        .field-label {
            font-size: 12pt;
            font-style: italic;
            color: #0c0784;
            white-space: nowrap;
        }
        
        .field-value {
            flex: 1;
            border-bottom: 1px solid #0c0784;
            font-size: 12pt;
            font-weight: 600;
            color: #000;
            padding: 0 2mm 1.2mm 2mm;
            line-height: 1.45;
            min-height: 7mm;
        }
        
        .field-split {
            display: flex;
            gap: 8mm;
        }
        
        .field-split > div {
            flex: 1;
        }
        
        .footer-text {
            font-size: 8.5pt;
            color: #0c0784;
            font-style: italic;
            margin-top: 3mm;
            flex-shrink: 0;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 6mm;
            flex-shrink: 0;
        }
        
        .signature-box {
            text-align: center;
            padding-top: 8mm;
        }
        
        .signature-line {
            width: 45mm;
            border-top: 1px solid #0c0784;
            margin-bottom: 1mm;
        }
        
        .signature-label {
            font-size: 10pt;
            font-weight: 700;
            color: #0c0784;
        }

    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px;">
        <button id="downloadPdfBtn" style="background: #1e40af; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
            <i class="fas fa-download"></i> Download PDF
        </button>
        <button onclick="window.close()" style="background: #6b7280; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
            Close
        </button>
    </div>

    <script>
        function trimExtraBlankPages(worker, expectedPages) {
            return worker.toPdf().get('pdf').then(function(pdf) {
                let total = pdf.internal.getNumberOfPages();
                while (total > expectedPages) {
                    pdf.deletePage(total);
                    total--;
                }
            });
        }

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
                const passingYear = firstCert.getAttribute('data-passing-year') || '';
                filename = `Name: ${studentName} , GR: ${grNo} , Year: ${passingYear} , SLC.pdf`;
            } else {
                const year = new URLSearchParams(window.location.search).get('year') || 'Bulk';
                filename = `School_Leaving_Certificates_${year}.pdf`;
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

            window.scrollTo(0, 0);
            certificates.forEach(cert => {
                cert.style.margin = '0';
                cert.style.marginBottom = '0';
                cert.classList.remove('page-break');
            });

            const finish = () => {
                certificates.forEach((cert, idx) => {
                    cert.style.margin = '0 auto 20px';
                    if (idx < totalCerts - 1) {
                        cert.classList.add('page-break');
                    }
                });
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download"></i> Download PDF';
            };

            const onError = (err) => {
                console.error('PDF Generation Error:', err);
                alert('Failed to generate PDF. Please try again.');
                finish();
            };

            if (totalCerts === 1) {
                trimExtraBlankPages(html2pdf().set(opt).from(certificates[0]), 1)
                    .save()
                    .then(finish)
                    .catch(onError);
                return;
            }

            let worker = html2pdf().set(opt).from(certificates[0]).toPdf();
            for (let i = 1; i < totalCerts; i++) {
                worker = worker.from(certificates[i]).toContainer().toCanvas().toPdf();
            }
            trimExtraBlankPages(worker, totalCerts)
                .save()
                .then(finish)
                .catch(onError);
        });
    </script>

    <div id="certificates-wrapper">
        <?php foreach ($studentsToPrint as $index => $student): 
            $gradYear = $student['graduation_year'] ?? (isset($student['updated_at']) ? date('Y', strtotime($student['updated_at'])) : '');
            $logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
                ? '../' . $settings['school_logo'] 
                : '../assets/branding/logo.png';
        ?>
        <div class="cert-page <?php echo ($index < count($studentsToPrint) - 1) ? 'page-break' : ''; ?>" 
             data-student-name="<?php echo htmlspecialchars($student['student_name']); ?>"
             data-gr-no="<?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $student['gr_no'])); ?>"
             data-passing-year="<?php echo htmlspecialchars($gradYear); ?>">
            <div class="border-outer">
                <div class="border-inner">
                    <img src="<?= $logoPath ?>?v=<?= time() ?>" class="watermark" alt="">
                    
                    <div class="content">
                        <div class="header">
                            <img src="<?= $logoPath ?>?v=<?= time() ?>" class="logo" alt="Logo">
                            <div class="school-info">
                                <?php 
                                    $schoolName = strtoupper(htmlspecialchars($settings['school_name'] ?? 'AQSA PUBLIC HIGHER SECONDARY SCHOOL'));
                                    $nameParts = explode(' ', $schoolName, 2);
                                    $firstWord = $nameParts[0];
                                    $restOfName = isset($nameParts[1]) ? $nameParts[1] : '';
                                ?>
                                <div class="school-name-wrapper">
                                    <div class="school-name-large"><?php echo $firstWord; ?></div>
                                    <?php if ($restOfName): ?>
                                        <div class="school-name-small"><?php echo $restOfName; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="school-location"><?php echo strtoupper(htmlspecialchars($settings['address'] ?? 'TANDO ALLAHYAR')); ?></div>
                            </div>
                        </div>
                        
                        <div class="cert-title">SCHOOL LEAVING CERTIFICATE</div>
                        
                        <div class="fields">
                            <div class="fields-body">
                            <div class="field-row">
                                <span class="field-label">G.R.No.</span>
                                <div class="field-value"><?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $student['gr_no'])); ?></div>
                            </div>
                            
                            <div class="field-row">
                                <span class="field-label">Name of Pupil</span>
                                <div class="field-value"><?php echo htmlspecialchars($student['student_name']); ?></div>
                            </div>
                            
                            <div class="field-row">
                                <span class="field-label">Father's Name</span>
                                <div class="field-value"><?php echo htmlspecialchars($student['father_name']); ?></div>
                            </div>
                            
                            <div class="field-split">
                                <div class="field-row">
                                    <span class="field-label">Religion</span>
                                    <div class="field-value"><?php echo htmlspecialchars($student['religion'] ?? 'Islam'); ?></div>
                                </div>
                                <div class="field-row">
                                    <span class="field-label">Race & Caste</span>
                                    <div class="field-value"><?php echo htmlspecialchars($student['caste'] ?? ''); ?></div>
                                </div>
                            </div>
                            
                            <div class="field-row">
                                <span class="field-label">Date of Birth</span>
                                <div class="field-value"><?php echo !empty($student['date_of_birth']) ? date('d-m-Y', strtotime($student['date_of_birth'])) : ''; ?></div>
                            </div>
                            
                            <div class="field-row">
                                <span class="field-label">Place of Birth</span>
                                <div class="field-value"><?php echo htmlspecialchars($student['place_of_birth'] ?? 'Tando Allahyar'); ?></div>
                            </div>
                            
                            <div class="field-row">
                                <span class="field-label">Last School Attended</span>
                                <div class="field-value"><?php echo htmlspecialchars(!empty(trim($student['previous_school'] ?? '')) ? $student['previous_school'] : 'New'); ?></div>
                            </div>
                            
                            <div class="field-split">
                                <div class="field-row">
                                    <span class="field-label">Date of Admission</span>
                                    <div class="field-value"><?php echo !empty($student['admission_date']) ? date('d-m-Y', strtotime($student['admission_date'])) : ''; ?></div>
                                </div>
                                <div class="field-row">
                                    <span class="field-label">Class at Admission</span>
                                    <div class="field-value"><?php echo htmlspecialchars($student['admission_class'] ?? 'KG'); ?></div>
                                </div>
                            </div>
                            
                            <div class="field-split">
                                <div class="field-row">
                                    <span class="field-label">Progress</span>
                                    <div class="field-value">Satisfactory</div>
                                </div>
                                <div class="field-row">
                                    <span class="field-label">Conduct</span>
                                    <div class="field-value">Good</div>
                                </div>
                            </div>
                            
                            <div class="field-row">
                                 <span class="field-label">Date of Leaving the School</span>
                                 <div class="field-value"><?php echo !empty($_GET['leaving_date']) ? date('d-m-Y', strtotime($_GET['leaving_date'])) : (!empty($student['updated_at']) ? date('d-m-Y', strtotime($student['updated_at'])) : date('d-m-Y')); ?></div>
                            </div>
                            
                            <?php 
                                $leavingClass = !empty($_GET['leaving_class']) ? $_GET['leaving_class'] : ($student['last_class'] ?? $student['current_class']);
                            ?>
                            <div class="field-row">
                                <span class="field-label">Class at the time of Leaving</span>
                                <div class="field-value"><?php echo htmlspecialchars($leavingClass); ?></div>
                            </div>
                            
                            <div class="field-row">
                                <span class="field-label">Reason</span>
                                <div class="field-value">Passed / Parents Request</div>
                            </div>
                            
                            <div class="field-row">
                                <span class="field-label">Remarks</span>
                                <div class="field-value">Standing at <?php echo htmlspecialchars($leavingClass); ?></div>
                            </div>
                            </div>
                            
                            <div class="footer-text">
                                Certified that the above information is in accordance with the school General Register.
                            </div>
                        </div>
                        
                        <div class="signatures">
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">G.R Incharge</div>
                            </div>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">PRINCIPAL</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
