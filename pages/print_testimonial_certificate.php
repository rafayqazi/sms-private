<?php
require_once '../includes/auth_session.php';
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

// Get form variables
$years = $_GET['years'] ?? '';
$examType = $_GET['exam_type'] ?? '';
$examCategory = $_GET['exam_category'] ?? '';
$examYear = $_GET['exam_year'] ?? '';
$displayYear = strlen($examYear) == 4 ? substr($examYear, -2) : $examYear;
$boardName = $_GET['board_name'] ?? '';
$seatNo = $_GET['seat_no'] ?? '';
$candidateType = $_GET['candidate_type'] ?? '';
$groupName = $_GET['group_name'] ?? '';

// Override group name based on GR prefix as requested
$rawGr = !empty($_GET['gr_no']) ? $_GET['gr_no'] : ($student['gr_no'] ?? '');
if (stripos($rawGr, 'P.M') !== false) {
    $groupName = 'Pre-Medical';
} elseif (stripos($rawGr, 'P.E') !== false) {
    $groupName = 'Pre-Engineering';
}

$grade = $_GET['grade'] ?? '';
$dobWords = $_GET['dob_words'] ?? '';
$dated = !empty($_GET['dated']) ? date('d-F-Y', strtotime($_GET['dated'])) : date('d-F-Y');

$studentName = strtoupper($student['student_name']);
$fatherName = strtoupper($student['father_name']);
$grNo = !empty($_GET['gr_no']) ? preg_replace('/[^0-9]/', '', $_GET['gr_no']) : preg_replace('/[^0-9]/', '', $student['gr_no']);

// Gender detection
$isFemale = (isset($student['gender']) && stripos($student['gender'], 'f') === 0) || 
            stripos($studentName, 'MISS') !== false || 
            stripos($studentName, 'KUMARI') !== false;

$prefix = $isFemale ? 'Miss' : 'Mr.';
$parentagePrefix = $isFemale ? 'D/o' : 'S/o';
$pronoun = $isFemale ? 'She' : 'He';
$possessivePronoun = $isFemale ? 'Her' : 'His';
$himHer = $isFemale ? 'her' : 'him';
$sonDaughter = $isFemale ? 'Daughter' : 'Son';

// DOB fetching logic requested by user
$rawDob = !empty($_GET['dob']) ? $_GET['dob'] : (!empty($student['date_of_birth']) ? $student['date_of_birth'] : '');
$dob = !empty($rawDob) ? date('d-m-Y', strtotime($rawDob)) : '';

// Auto-generate DOB in words if empty
if (empty($dobWords) && !empty($rawDob)) {
    function numberToWordLocal($num) {
        $words = array(0=>'', 1=>'One', 2=>'Two', 3=>'Three', 4=>'Four', 5=>'Five', 6=>'Six', 7=>'Seven', 8=>'Eight', 9=>'Nine', 10=>'Ten', 11=>'Eleven', 12=>'Twelve', 13=>'Thirteen', 14=>'Fourteen', 15=>'Fifteen', 16=>'Sixteen', 17=>'Seventeen', 18=>'Eighteen', 19=>'Nineteen', 20=>'Twenty', 30=>'Thirty', 40=>'Forty', 50=>'Fifty', 60=>'Sixty', 70=>'Seventy', 80=>'Eighty', 90=>'Ninety');
        if ($num < 20) return $words[$num];
        if ($num < 100) return $words[floor($num / 10) * 10] . ($num % 10 > 0 ? " " . $words[$num % 10] : "");
        if ($num < 1000) return $words[floor($num / 100)] . " Hundred" . ($num % 100 > 0 ? " and " . numberToWordLocal($num % 100) : "");
        if ($num < 1000000) return numberToWordLocal(floor($num / 1000)) . " Thousand" . ($num % 1000 > 0 ? " " . numberToWordLocal($num % 1000) : "");
        return $num;
    }
    
    $timestamp = strtotime($rawDob);
    $day = date('j', $timestamp);
    $month = date('F', $timestamp);
    $year = date('Y', $timestamp);
    
    $ordinals = [
        1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Fifth',
        6 => 'Sixth', 7 => 'Seventh', 8 => 'Eighth', 9 => 'Ninth', 10 => 'Tenth',
        11 => 'Eleventh', 12 => 'Twelfth', 13 => 'Thirteenth', 14 => 'Fourteenth', 15 => 'Fifteenth',
        16 => 'Sixteenth', 17 => 'Seventeenth', 18 => 'Eighteenth', 19 => 'Nineteenth', 20 => 'Twentieth',
        21 => 'Twenty-First', 22 => 'Twenty-Second', 23 => 'Twenty-Third', 24 => 'Twenty-Fourth', 25 => 'Twenty-Fifth',
        26 => 'Twenty-Sixth', 27 => 'Twenty-Seventh', 28 => 'Twenty-Eighth', 29 => 'Twenty-Ninth', 30 => 'Thirtieth',
        31 => 'Thirty-First'
    ];
    
    $dayStr = $ordinals[$day] ?? $day;
    $yearStr = numberToWordLocal($year);
    $dobWords = trim("$dayStr of $month, $yearStr");
}

$logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
    ? '../' . $settings['school_logo'] 
    : '../assets/branding/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonial Certificate - <?= $studentName ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;800&family=Great+Vibes&family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary: #0a2540;
            --secondary: #c5a059;
            --accent: #8b6d33;
            --text-dark: #1a1a1a;
            --text-light: #4a4a4a;
            --bg-page: #f8fafc;
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
            .cert-body-wrapper {
                 box-shadow: none !important;
            }
            .cert-page {
                box-shadow: none !important;
                border: none !important;
            }
        }
        
        body {
            font-family: 'Lora', serif;
            background: var(--bg-page);
            padding: 20px;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
        }
        
        .cert-page {
            width: 210mm;
            height: 297mm;
            background: #ffffff;
            margin: 0 auto;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
            background-image: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M50 0 L100 50 L50 100 L0 50 Z" fill="rgba(197,160,89,0.02)"/></svg>');
            background-size: 30mm;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        /* Elaborate Borders */
        .border-layer-1 {
            position: absolute;
            top: 8mm; left: 8mm; right: 8mm; bottom: 8mm;
            border: 8px solid var(--primary);
            z-index: 5;
            pointer-events: none;
        }
        
        .border-layer-2 {
            position: absolute;
            top: 10mm; left: 10mm; right: 10mm; bottom: 10mm;
            border: 2px solid var(--secondary);
            z-index: 5;
            pointer-events: none;
        }
        
        .border-layer-3 {
            position: absolute;
            top: 12mm; left: 12mm; right: 12mm; bottom: 12mm;
            border: 1px dashed rgba(10,37,64,0.3);
            z-index: 5;
            pointer-events: none;
        }

        /* Decorative Corners */
        .corner-ornament {
            position: absolute;
            width: 25mm;
            height: 25mm;
            z-index: 6;
            pointer-events: none;
        }
        
        .corner-tl {
            top: 4mm; left: 4mm;
            border-top: 16px solid var(--secondary);
            border-left: 16px solid var(--secondary);
        }
        
        .corner-tr {
            top: 4mm; right: 4mm;
            border-top: 16px solid var(--secondary);
            border-right: 16px solid var(--secondary);
        }
        
        .corner-bl {
            bottom: 4mm; left: 4mm;
            border-bottom: 16px solid var(--secondary);
            border-left: 16px solid var(--secondary);
        }
        
        .corner-br {
            bottom: 4mm; right: 4mm;
            border-bottom: 16px solid var(--secondary);
            border-right: 16px solid var(--secondary);
        }

        /* Inner Corner Accents */
        .inner-corner {
            position: absolute;
            width: 15mm;
            height: 15mm;
            z-index: 6;
            pointer-events: none;
        }
        
        .inner-tl { top: 12mm; left: 12mm; border-top: 2px solid var(--primary); border-left: 2px solid var(--primary); }
        .inner-tr { top: 12mm; right: 12mm; border-top: 2px solid var(--primary); border-right: 2px solid var(--primary); }
        .inner-bl { bottom: 12mm; left: 12mm; border-bottom: 2px solid var(--primary); border-left: 2px solid var(--primary); }
        .inner-br { bottom: 12mm; right: 12mm; border-bottom: 2px solid var(--primary); border-right: 2px solid var(--primary); }

        /* Main Content Wrappers */
        .cert-main-container {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 10mm 22mm 15mm 22mm; /* Added more bottom padding (15mm) */
        }

        /* Headers & Branding */
        .school-header {
            text-align: center;
            margin-bottom: 0mm;
            position: relative;
        }

        .logo-placeholder {
            width: 34mm;
            height: 34mm;
            margin: 0 auto 1mm auto; /* Tighter logo margin */
            position: relative;
            z-index: 10;
        }
        
        .logo-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        .school-name {
            font-family: 'Cinzel', serif;
            font-size: 22pt;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0mm; /* Tighter school name margin */
            line-height: 1.2;
            text-shadow: 1px 1px 0px rgba(255,255,255,0.8);
        }

        .school-tagline {
            font-family: 'Montserrat', sans-serif;
            font-size: 9pt;
            font-weight: 500;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 5px;
            margin-bottom: 3mm; /* Halved tagline space */
        }

        .title-container {
            text-align: center;
            margin-bottom: 3mm; /* Halved title container space */
            position: relative;
        }
        
        .title-container::before,
        .title-container::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 15%;
            height: 1px;
            background-color: var(--secondary);
        }
        
        .title-container::before { left: 5%; }
        .title-container::after { right: 5%; }

        .cert-title {
            font-family: 'Great Vibes', cursive;
            font-size: 38pt;
            color: var(--primary);
            display: inline-block;
            margin: 0 0 2mm 0; /* Reduced to 2mm bottom margin */
            line-height: 1;
            padding: 0 15px;
            position: relative;
        }

        .cert-subtitle {
            font-family: 'Montserrat', sans-serif;
            font-size: 10pt;
            font-weight: 600;
            letter-spacing: 8px;
            color: var(--secondary);
            text-transform: uppercase;
            margin-top: 0;
        }

        /* Certificate Body */
        .cert-body-wrapper {
            background-color: transparent;
            padding: 2mm 5mm; /* Drastically reduced vertical padding inside body wrapper */
            border-radius: 8px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 20;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            font-family: 'Montserrat', sans-serif;
            font-size: 9pt;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 6mm;
            padding-bottom: 2mm;
            border-bottom: 1px solid rgba(197, 160, 89, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .text-content {
            font-size: 13.5pt;
            line-height: 2;
            color: var(--text-dark);
            text-align: left;
        }

        .text-content p {
            margin-bottom: 5mm;
        }

        /* Beautiful Input Fields */
        .highlight-data {
            font-family: 'Playfair Display', serif;
            font-size: 14pt; /* Dropped by 1pt */
            font-weight: 700;
            font-style: italic;
            color: var(--primary);
            padding: 0 6px;
            border-bottom: 1.5px solid var(--secondary);
            display: inline-block;
            line-height: 1.1;
            position: relative;
            white-space: nowrap;
        }
        
        /* Specific widths for fields to look neat */
        .w-name { min-width: auto; text-align: left; }
        .w-father { min-width: auto; text-align: left; }
        .w-years { min-width: auto; text-align: center; }
        .w-exam { min-width: auto; text-align: center; }
        .w-board { min-width: auto; text-align: center; }
        .w-dob { min-width: auto; text-align: center; }

        .praise-section {
            font-family: 'Great Vibes', cursive;
            font-size: 24pt;
            color: var(--secondary);
            text-align: center;
            margin: 6mm 0;
            line-height: 1.2;
        }

        /* Footer / Signatures */
        .cert-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 5mm;
            padding-bottom: 0mm; /* Removed bottom padding space in footer */
            position: relative;
            z-index: 20;
        }

        .signature-block {
            text-align: center;
            width: 55mm;
        }

        .signature-line {
            border-bottom: 1px solid var(--primary);
            margin-bottom: 2mm;
            height: 15mm;
            position: relative;
        }
        
        .seal-circle {
            position: absolute;
            left: 50%;
            bottom: -5px;
            transform: translateX(-50%);
            width: 20mm;
            height: 20mm;
            border: 2px dashed rgba(197, 160, 89, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .seal-text {
            font-family: 'Cinzel', serif;
            font-size: 7pt;
            color: rgba(197, 160, 89, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
        }

        .signature-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 9pt;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .signature-name {
            font-family: 'Playfair Display', serif;
            font-size: 12pt;
            font-weight: 700;
            color: var(--primary);
            margin-top: 1mm;
        }

        .date-block {
            text-align: center;
            margin-bottom: 4mm;
        }
        
        .date-value {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 14pt;
            font-weight: 600;
            color: var(--primary);
            padding-bottom: 2mm;
            border-bottom: 1px solid var(--primary);
            width: 40mm;
            margin: 0 auto 2mm auto;
        }

        /* Large Watermark Logo */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 140mm;
            height: 140mm;
            opacity: 0.04;
            z-index: 1;
            pointer-events: none;
            filter: grayscale(100%);
        }
        
        /* Floating Badge Design */
        .badge {
            position: absolute;
            bottom: 30mm;
            left: 50%;
            transform: translateX(-50%);
            width: 35mm;
            height: 35mm;
            z-index: 15;
            object-fit: contain;
            opacity: 0.8;
            pointer-events: none;
        }

    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px;">
        <button id="downloadPdfBtn" style="background: var(--primary); color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: 'Montserrat', sans-serif; letter-spacing: 1px; transition: all 0.2s;">
            <i class="fas fa-file-pdf mr-2"></i> Save Official PDF
        </button>
        <button onclick="window.close()" style="background: white; color: var(--text-dark); padding: 12px 24px; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.05); font-family: 'Montserrat', sans-serif; transition: all 0.2s;">
            Cancel
        </button>
    </div>

    <div class="cert-page" id="cert-content">
        <!-- Borders -->
        <div class="border-layer-1"></div>
        <div class="border-layer-2"></div>
        <div class="border-layer-3"></div>
        
        <!-- Corners -->
        <div class="corner-ornament corner-tl"></div>
        <div class="corner-ornament corner-tr"></div>
        <div class="corner-ornament corner-bl"></div>
        <div class="corner-ornament corner-br"></div>
        
        <div class="inner-corner inner-tl"></div>
        <div class="inner-corner inner-tr"></div>
        <div class="inner-corner inner-bl"></div>
        <div class="inner-corner inner-br"></div>

        <!-- Huge Watermark -->
        <img src="<?= $logoPath ?>" class="watermark" alt="Watermark">

        <div class="cert-main-container">
            
            <div class="school-header">
                <div class="logo-placeholder">
                    <img src="<?= $logoPath ?>?v=<?= time() ?>" class="logo-image" alt="Official Seal">
                </div>
                <h1 class="school-name"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                <div class="school-tagline"><?php echo htmlspecialchars($settings['address_tagline']); ?></div>
            </div>

            <div class="title-container">
                <h2 class="cert-title">Testimonial Certificate</h2>
                <div class="cert-subtitle">Certificate of Academic Achievement</div>
            </div>

            <div class="cert-body-wrapper">
                <div class="text-content" style="text-align: left; font-size: 13pt; padding-top: 0mm; line-height: 1.85;">
                    <div style="font-weight: bold; color: var(--primary); font-size: 14pt; margin-bottom: 2mm;">
                        G.R.No: <span style="display:inline-block; border-bottom: 1px dotted var(--primary); min-width: 15mm; width: auto; text-align: left; padding: 0 5px;"><?= $grNo ?></span>
                    </div>

                    <div style="margin-bottom: 3mm;">
                        I am Glad to certify that <span class="highlight-data" style="white-space: nowrap;"><?= $prefix ?> <?= $studentName ?> <?= $parentagePrefix ?> <?= $fatherName ?></span> 
                        had been a Bonafide Student of this School for the last <span class="highlight-data w-years"><?= htmlspecialchars($years) ?></span> Years.
                    </div>

                    <div style="margin-bottom: 3mm;">
                        <?= $pronoun ?> appeared from this Institution at the <span class="highlight-data w-exam"><?= htmlspecialchars($examType) ?></span> <span class="highlight-data"><?= htmlspecialchars($examCategory) ?></span> Examination 
                        20<span class="highlight-data"><?= htmlspecialchars($displayYear) ?></span> held by the Board of Intermediate and Secondary Education <span class="highlight-data w-board"><?= htmlspecialchars($boardName) ?></span>  
                        Under Seat No <span class="highlight-data"><?= htmlspecialchars($seatNo) ?></span> as a <span class="highlight-data"><?= htmlspecialchars($candidateType) ?></span> of <span class="highlight-data"><?= htmlspecialchars($groupName) ?></span> 
                        and has been declared successful in Grade ,, <span class="highlight-data">"<?= htmlspecialchars($grade) ?>"</span>".
                    </div>

                    <div style="margin-bottom: 3mm;">
                        <?= $possessivePronoun ?> Date of Birth according to Christian Era mentioned in the school 
                        record is <span class="highlight-data w-dob" style="margin: 0 5px;"><?= $dob ?></span> 
                        in words <span class="highlight-data" style="padding: 0 5px; font-size: 11pt;"><?= htmlspecialchars($dobWords) ?></span> 
                    </div>

                    <div style="margin-bottom: 3mm;">
                        <?= $possessivePronoun ?> character was always reported to be good and satisfactory 
                        to the best of my knowledge. <?= $pronoun ?> was never reported to be part in any activity 
                        Subversive to law and order.
                    </div>

                    <div style="margin-bottom: 0;">
                        <?= $pronoun ?> took in out door Game regularly. <?= $pronoun ?> was always reported 
                        to be hard working and obedient by teachers concerned.
                    </div>
                </div>
            </div>

            <div class="cert-footer" style="padding-bottom: 5mm;">
                <div class="signature-block" style="text-align: left;">
                    <br><br>
                    <div class="date-value" style="margin-left: 0; width: 30mm; border: none; font-style: normal; font-weight: normal; font-family: 'Lora', serif; font-size: 13pt; padding: 0;">Dated</div>
                </div>
                
                <div class="signature-block" style="position: relative;">
                    <!-- Optional decorative seal can go here -->
                </div>

                <div class="signature-block">
                    <div class="signature-line">
                        <div class="seal-circle" style="bottom: 0px;">
                            <span class="seal-text" style="font-size: 6pt;">Official<br>Seal</span>
                        </div>
                    </div>
                    <div class="signature-title">Principal Signature</div>
                    <div class="signature-name" style="font-size: 11pt;"><?php echo htmlspecialchars($settings['headmaster_name'] ?? 'Principal'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            const btn = this;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Rendering High-Res PDF...';
            
            const element = document.getElementById('cert-content');
            const studentName = "<?= $studentName ?>";
            const grNo = "<?= $grNo ?>";
            const filename = `Testimonial_Certificate_${studentName}_${grNo}.pdf`;

            // Adjust styles slightly just for the print engine to ensure crisp borders
            element.style.transform = "scale(0.99)"; 
            element.style.transformOrigin = "top left";

            const opt = {
                margin: 0,
                filename: filename,
                image: { type: 'jpeg', quality: 1.0 },
                html2canvas: { 
                    scale: 4, // High resolution
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
                btn.innerHTML = originalContent;
                element.style.transform = "none";
            }).catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = originalContent;
                element.style.transform = "none";
                alert('An error occurred while generating the PDF. Please try again.');
            });
        });
    </script>
</body>
</html>
