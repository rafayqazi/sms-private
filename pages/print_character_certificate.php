<?php
require_once '../includes/parent_or_staff_auth.php';
require_once '../includes/db.php';

$db = new Database();
$settings = $db->getSchoolSettings();

if (!isset($_GET['id'])) {
    die('Student ID missing.');
}

$student = $db->getStudent($_GET['id']);
if (!$student) {
    die('Student not found.');
}

$caste = strtoupper($_GET['caste'] ?? ($student['caste'] ?? ''));
$years = $_GET['years'] ?? '';
$admission_class = strtoupper($_GET['admission_class'] ?? ($student['admission_class'] ?? ''));
$leaving_class = strtoupper($_GET['leaving_class'] ?? ($student['student_status'] === 'Alumni' ? ($student['last_class'] ?? '') : ($student['current_class'] ?? '')));
$dated = !empty($_GET['dated']) ? date('d-m-Y', strtotime($_GET['dated'])) : date('d-m-Y');

$studentName = strtoupper($student['student_name']);
$fatherName = strtoupper($student['father_name']);
$grNo = preg_replace('/[^0-9]/', '', $student['gr_no']);

// Gender detection
$isFemale = (isset($student['gender']) && stripos($student['gender'], 'f') === 0) || 
            stripos($studentName, 'MISS') !== false || 
            stripos($studentName, 'KUMARI') !== false;

$prefix = $isFemale ? 'Miss.' : 'Mr.';
$parentagePrefix = $isFemale ? 'D/o' : 'S/o';
$pronoun = $isFemale ? 'She' : 'He';
$possessivePronoun = $isFemale ? 'Her' : 'His';
$himHer = $isFemale ? 'Her' : 'Him';

$logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
    ? '../' . $settings['school_logo'] 
    : '../assets/branding/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Certificate - <?= $studentName ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Lora:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary-blue: #0c0784;
            --gold: #c5a059;
            --soft-white: #ffffff;
        }

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
            .cert-page { 
                width: 210mm !important;
                height: 297mm !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-inside: avoid;
            }
        }
        
        body {
            font-family: 'Lora', serif;
            background: #e2e8f0;
            padding: 20px;
            color: #1a1a1a;
        }
        
        .cert-page {
            width: 210mm;
            height: 297mm;
            background: var(--soft-white);
            margin: 0 auto;
            padding: 10mm; /* Reduced from 15mm */
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* Elaborate Border */
        .outer-border {
            position: absolute;
            top: 10mm;
            left: 10mm;
            right: 10mm;
            bottom: 10mm;
            border: 5px double var(--primary-blue);
            z-index: 5;
            pointer-events: none;
        }

        .inner-border {
            position: absolute;
            top: 14mm;
            left: 14mm;
            right: 14mm;
            bottom: 14mm;
            border: 1px solid var(--gold);
            z-index: 5;
            pointer-events: none;
        }

        /* Corner Ornaments */
        .corner {
            position: absolute;
            width: 30mm;
            height: 30mm;
            border: 3px solid var(--primary-blue);
            z-index: 6;
        }
        .corner-tl { top: 12mm; left: 12mm; border-right: none; border-bottom: none; }
        .corner-tr { top: 12mm; right: 12mm; border-left: none; border-bottom: none; }
        .corner-bl { bottom: 12mm; left: 12mm; border-right: none; border-top: none; }
        .corner-br { bottom: 12mm; right: 12mm; border-left: none; border-top: none; }

        /* Content Container */
        .cert-content-wrapper {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 10mm 15mm; /* Reduced top padding from 20mm to 10mm */
        }

        /* Header */
        .school-info {
            margin-bottom: 5mm;
        }

        .logo-main {
            width: 50mm; /* Increased from 40mm */
            height: 50mm; /* Increased from 40mm */
            object-fit: contain;
            margin-bottom: 5mm;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .school-title {
            font-family: 'Cinzel', serif;
            font-size: 28pt;
            font-weight: 700;
            color: var(--primary-blue);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 2mm;
            line-height: 1.1;
        }

        .school-location {
            font-family: 'Cinzel', serif;
            font-size: 16pt;
            font-weight: 400;
            color: var(--primary-blue); /* Changed from var(--gold) to be more visible */
            letter-spacing: 5px;
            text-transform: uppercase;
            margin-bottom: 5mm; /* Reduced from 10mm to pull title up */
        }

        /* Title Area */
        .certificate-label {
            width: 100%;
            margin-bottom: 12mm;
            text-align: center;
        }

        .cert-main-title {
            font-family: 'Playfair Display', serif;
            font-size: 36pt;
            font-weight: 700;
            color: var(--primary-blue);
            letter-spacing: 1px;
            display: inline-block;
            padding: 0 10mm;
            border-bottom: 2px solid var(--gold); /* Added a clean gold underline instead */
        }

        /* Body Text */
        .cert-body {
            font-size: 14pt; /* Reduced to ensure line fit */
            line-height: 2.8; /* Increased from 2.5 for better spacing */
            color: #333;
            width: 100%;
            max-width: 190mm; /* Increased to maximize line length */
            margin: 0 auto 15mm auto;
            text-align: left;
            padding-left: 0; /* Minimized padding */
        }

        .field-label {
            font-style: italic;
            color: #666;
            margin-right: 5px;
        }

        .value-underline {
            display: inline-block;
            border-bottom: 1px solid var(--primary-blue);
            padding: 0 4mm;
            font-weight: 700;
            color: #000;
            min-width: 40mm; /* Reduced from 60mm */
            font-family: 'Playfair Display', serif;
            font-style: italic;
        }

        .closing-statement {
            font-family: 'Playfair Display', serif;
            font-size: 16pt;
            font-style: italic;
            color: var(--primary-blue);
            font-weight: 400;
            margin-top: 2mm; /* Reduced from 5mm */
            text-align: center; /* Center aligned as requested */
            width: 100%;
        }

        /* Footer Section */
        .cert-footer {
            margin-top: auto;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 10mm 10mm 5mm; /* Reduced left padding from 10mm to 5mm */
        }

        .footer-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 50mm;
        }

        .dated-text {
            border-bottom: 1px solid #ccc;
            width: 100%;
            margin-bottom: 4mm;
            font-weight: 700;
            color: #333;
            padding-bottom: 2mm;
        }

        .dated-label {
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #888;
        }

        .signature-line {
            border-top: 2px solid var(--primary-blue);
            width: 100%;
            margin-bottom: 2mm;
        }

        .principal-title {
            font-family: 'Cinzel', serif;
            font-size: 14pt;
            font-weight: 700;
            color: var(--primary-blue);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .principal-name {
            font-family: 'Lora', serif;
            font-size: 11pt;
            color: #333;
            margin-top: 1mm;
            letter-spacing: 0.5px;
        }

        /* Background Elements */
        .watermark-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120mm;
            height: 120mm;
            opacity: 0.03;
            z-index: 1;
            pointer-events: none;
        }

        .top-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8mm;
            background: linear-gradient(90deg, var(--primary-blue), #1e3a8a);
            z-index: 10;
        }

        .bottom-pattern {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 8mm;
            background: linear-gradient(90deg, #1e3a8a, var(--primary-blue));
            z-index: 10;
        }
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px;">
        <button id="downloadPdfBtn" style="background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <i class="fas fa-file-pdf"></i> Save Premium PDF
        </button>
        <button onclick="window.close()" style="background: #6b7280; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            Exit
        </button>
    </div>

    <div class="cert-page" id="cert-content">
        <div class="top-pattern"></div>
        <div class="bottom-pattern"></div>
        
        <div class="outer-border"></div>
        <div class="inner-border"></div>
        
        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>

        <img src="<?= $logoPath ?>" class="watermark-overlay">

        <div class="cert-content-wrapper">
            <div class="school-info">
                <img src="<?= $logoPath ?>?v=<?= time() ?>" class="logo-main" alt="Logo">
                <h1 class="school-title"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                <div class="school-location"><?php echo htmlspecialchars($settings['address_tagline']); ?></div>
            </div>

            <div class="certificate-label">
                <h2 class="cert-main-title">Character Certificate</h2>
            </div>

            <div class="cert-body">
                This is to Certify that <span class="field-label"><?= $prefix ?></span> 
                <span class="value-underline"><?= $studentName ?></span> 
                <span class="field-label"><?= $parentagePrefix ?></span> 
                <span class="value-underline"><?= $fatherName ?></span> 
                <span class="field-label">by Caste</span> 
                <span class="value-underline" style="min-width: 30mm;"><?= $caste ?></span><br>
                Remained a Bonafide Student of this School for <span class="value-underline" style="min-width: 30mm; text-align: center;"><?= $years ?></span> Years.<br><br>
                To the best of my knowledge, <?= $pronoun ?> bears <span style="font-weight: 700; color: var(--primary-blue);">Good Moral Character</span>.<br><br>
                <div class="closing-statement">
                    I wish <?= $himHer ?> best of Luck in <?= $possessivePronoun ?> Life.
                </div>
            </div>

            <div class="cert-footer">
                <div class="footer-item">
                    <div class="dated-text"><?= $dated ?></div>
                    <div class="dated-label">Dated</div>
                </div>
                <div class="footer-item">
                    <div class="signature-line"></div>
                    <div class="principal-title">Principal</div>
                    <div class="principal-name"><?php echo strtoupper($settings['headmaster_name'] ?? ''); ?></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Designing PDF...';
            
            const element = document.getElementById('cert-content');
            const studentName = "<?= $studentName ?>";
            const grNo = "<?= $grNo ?>";
            const filename = `Premium_Character_Certificate_${studentName}_${grNo}.pdf`;

            const opt = {
                margin: 0,
                filename: filename,
                image: { type: 'jpeg', quality: 1.0 },
                html2canvas: { 
                    scale: 3, 
                    useCORS: true,
                    letterRendering: true,
                    scrollX: 0,
                    scrollY: 0,
                    windowWidth: document.documentElement.offsetWidth,
                    windowHeight: document.documentElement.offsetHeight
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-pdf"></i> Save Premium PDF';
            }).catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-pdf"></i> Save Premium PDF';
                alert('An error occurred while generating the PDF. Please try again.');
            });
        });
    </script>
</body>
</html>
