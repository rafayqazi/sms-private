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
    <title>Transfer Certificate (PAD)</title>
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
            .cert-page:last-child { page-break-after: auto; }
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f0f0f0;
            padding: 10px;
            color: #000;
        }

        /* A4 page wrapper — no branding, spacing via spacers */
        .cert-page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            background: white;
            margin: 0 auto 20px;
            padding: 0;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* 43.5mm blank space above content (matches SLC PAD) */
        .top-spacer {
            height: 43.5mm;
            flex-shrink: 0;
        }

        /* 28.1mm blank space below signatures (matches SLC PAD) */
        .bottom-spacer {
            height: 28.1mm;
            flex-shrink: 0;
        }

        /* Watermark centered in page */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60%;
            opacity: 0.04;
            z-index: 0;
            pointer-events: none;
        }

        /* Main content area */
        .cert-container {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 5mm 20mm 5mm 20mm;
        }

        .city-name {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 3mm;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .cert-title {
            text-align: center;
            font-size: 20pt;
            font-weight: bold;
            color: #000;
            text-decoration: underline;
            margin-bottom: 6mm;
            letter-spacing: 2px;
        }

        .enrolment {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 4mm;
        }

        .enrolment-val {
            border-bottom: 2px solid #0c0784;
            display: inline-block;
            padding: 0 4mm;
            font-size: 13pt;
        }

        .intro-text {
            font-size: 12pt;
            line-height: 2;
            margin-bottom: 6mm;
        }

        .underline-input {
            border-bottom: 1px solid #0c0784;
            display: inline-block;
            font-weight: bold;
            padding: 0 4mm;
            text-align: center;
            font-size: 12pt;
        }

        .sections {
            display: flex;
            flex-direction: column;
            gap: 4mm;
            font-size: 11pt;
        }

        .section-row {
            display: flex;
            align-items: baseline;
            gap: 2mm;
            line-height: 1.5;
        }

        .section-label { font-weight: bold; }

        .date-input {
            min-width: 10mm;
            border-bottom: 1px solid #0c0784;
            text-align: center;
            display: inline-block;
            font-weight: bold;
            padding: 0 2mm;
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
            padding: 5mm 0 0;
        }

        .principal-box {
            text-align: center;
            min-width: 60mm;
            border-top: 2px solid #0c0784;
            padding-top: 2mm;
        }

        .principal-label {
            font-size: 14pt;
            font-weight: bold;
            color: #0c0784;
        }
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px;">
        <button id="downloadPdfBtn"
            style="background: #1e40af; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
            <i class="fas fa-download"></i> Download PDF
        </button>
        <button onclick="window.print()"
            style="background: #059669; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
            <i class="fas fa-print"></i> Print Now
        </button>
        <button onclick="window.close()"
            style="background: #6b7280; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
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
                filename = `TC_PAD_${studentName}_GR_${grNo}.pdf`;
            } else {
                const year = new URLSearchParams(window.location.search).get('year') || 'Bulk';
                filename = `Transfer_Certificates_PAD_${year}.pdf`;
            }

            const opt = {
                margin: 0,
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, letterRendering: true, scrollX: 0, scrollY: 0 },
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
                    if (idx < totalCerts - 1) cert.classList.add('page-break');
                });
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download"></i> Download PDF';
            };

            const onError = (err) => {
                console.error('PDF Error:', err);
                alert('Failed to generate PDF. Please try again.');
                finish();
            };

            if (totalCerts === 1) {
                trimExtraBlankPages(html2pdf().set(opt).from(certificates[0]), 1)
                    .save().then(finish).catch(onError);
                return;
            }

            let worker = html2pdf().set(opt).from(certificates[0]).toPdf();
            for (let i = 1; i < totalCerts; i++) {
                worker = worker.from(certificates[i]).toContainer().toCanvas().toPdf();
            }
            trimExtraBlankPages(worker, totalCerts).save().then(finish).catch(onError);
        });
    </script>

    <div id="certificates-wrapper">
        <?php foreach ($studentsToPrint as $index => $student):
            $studentName = strtoupper($student['student_name']);
            $fatherName  = strtoupper($student['father_name']);
            $grNo        = preg_replace('/[^0-9]/', '', $student['gr_no']);

            // Gender detection
            $isFemale = (isset($student['gender']) && stripos($student['gender'], 'f') === 0) ||
                        stripos($studentName, 'MISS') !== false ||
                        stripos($studentName, 'KUMARI') !== false;

            $prefix              = $isFemale ? 'Miss' : 'Mr.';
            $parentagePrefix     = $isFemale ? 'D/O' : 'S/O';
            $pronoun             = $isFemale ? 'She' : 'He';
            $possessivePronoun   = $isFemale ? 'Her' : 'His';
            $objectivePronoun    = $isFemale ? 'her' : 'him';

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

            <!-- Watermark only — no branding header -->
            <img src="<?= $logoPath ?>?v=<?= time() ?>" class="watermark" alt="">

            <!-- 43.5mm blank space above (for pre-printed PAD header) -->
            <div class="top-spacer"></div>

            <div class="cert-container">

                <div class="cert-title">TRANSFER CERTIFICATE</div>

                <div class="enrolment">
                    Enrolment No. <span class="enrolment-val"><?php echo $grNo; ?></span>
                </div>

                <div class="intro-text">
                    This is to Certify that <?= $prefix ?> <span class="underline-input"><?php echo $studentName; ?> <?= $parentagePrefix ?> <?php echo $fatherName; ?></span><br>
                    <?= $pronoun ?> has been a student of this school.
                </div>

                <div class="sections">
                    <div class="section-row">
                        <span class="section-label">A) Since passing the S.S.C II Examination, He/She kept term in this school as under</span>
                    </div>
                    <div class="sub-sections">
                        <div class="section-row" style="margin-bottom: 2mm;">
                            <span>August 20</span><span class="date-input" style="margin-right: 4mm;"><?php echo htmlspecialchars($_GET['ssc_aug'] ?? ''); ?></span> <span>to December 20</span><span class="date-input"><?php echo htmlspecialchars($_GET['ssc_dec'] ?? ''); ?></span>
                        </div>
                        <div class="section-row">
                            <span>January 20</span><span class="date-input" style="margin-right: 4mm;"><?php echo htmlspecialchars($_GET['ssc_jan'] ?? ''); ?></span> <span>to May 20</span><span class="date-input"><?php echo htmlspecialchars($_GET['ssc_may'] ?? ''); ?></span>
                        </div>
                    </div>

                    <div class="section-row">
                        <span class="section-label">B) <?= $possessivePronoun ?> work the school Examination was as following:</span>
                    </div>

                    <div class="section-row" style="display: flex; gap: 4mm;">
                        <span class="section-label" style="white-space: nowrap;">C) Passing the H.S.C II Examination in the Year Annual <span style="border-bottom: 1px solid #0c0784; padding: 0 4mm; display: inline-block; text-align: center;"><?php echo htmlspecialchars($_GET['hsc_year'] ?? '_______'); ?></span></span>
                        <span class="section-label" style="white-space: nowrap;">under Seat No : <span style="border-bottom: 1px solid #0c0784; padding: 0 4mm; display: inline-block; text-align: center;"><?php echo htmlspecialchars($_GET['hsc_seat'] ?? '_______'); ?></span></span>
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
                        <span style="border-bottom: 2px solid #0c0784; font-weight: bold; padding: 0 4mm 0 0; display: inline-block;"><?php echo $subjectText; ?></span>
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

                <div class="footer">
                    <div class="principal-box">
                        <span class="principal-label">Principal</span>
                    </div>
                </div>

            </div>

            <!-- 28.1mm blank space below (for pre-printed PAD footer) -->
            <div class="bottom-spacer"></div>

        </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
