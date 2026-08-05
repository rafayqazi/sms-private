<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

$db = new Database();
$settings = $db->getSchoolSettings();

$id = $_GET['id'] ?? ($_GET['gr_no'] ?? '');
if (!$id) {
    die('<div style="text-align:center; padding:50px; font-family:sans-serif;"><h2>Error: Student ID or GR No is missing.</h2></div>');
}

$student = $db->getStudent($id);
if (!$student) {
    $student = $db->getStudentByGrNo($id);
}

if (!$student) {
    die('<div style="text-align:center; padding:50px; font-family:sans-serif;"><h2>Error: Student record not found.</h2></div>');
}

$logoPath = (!empty($settings['school_logo']) && file_exists('../' . $settings['school_logo'])) 
    ? '../' . $settings['school_logo'] 
    : '../assets/branding/logo.png';

$profileImg = (!empty($student['profile_image']) && file_exists('../' . str_replace('../', '', $student['profile_image'])))
    ? $student['profile_image']
    : '../assets/default_avatar.png';

// Format Date of Birth
$dobFormatted = !empty($student['date_of_birth']) ? date('d-m-Y', strtotime($student['date_of_birth'])) : 'N/A';
$admDateFormatted = !empty($student['admission_date']) ? date('d-m-Y', strtotime($student['admission_date'])) : date('d-m-Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Form - <?php echo htmlspecialchars($student['student_name']); ?> (GR: <?php echo htmlspecialchars($student['gr_no']); ?>)</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Noto+Nastaliq+Urdu:wght@400;700&family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .page-card {
                box-shadow: none !important;
                border: 2px solid #1e293b !important;
                margin: 0 auto !important;
                width: 100% !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #eef2f6;
            color: #1e293b;
            padding: 20px 10px;
        }

        .no-print-bar {
            max-width: 210mm;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            gap: 10px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-print { background: #4f46e5; color: #ffffff; }
        .btn-print:hover { background: #4338ca; }
        .btn-pdf { background: #059669; color: #ffffff; }
        .btn-pdf:hover { background: #047857; }
        .btn-close { background: #f1f5f9; color: #475569; }
        .btn-close:hover { background: #e2e8f0; }

        .page-card {
            width: 210mm;
            min-height: 280mm;
            background: #ffffff;
            margin: 0 auto;
            padding: 14px 18px;
            border: 2px solid #0f172a;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .form-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px double #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .header-text {
            text-align: center;
            flex: 1;
            padding: 0 15px;
        }

        .school-name {
            font-family: 'Cinzel', serif;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .school-subtitle {
            font-size: 10px;
            font-weight: 700;
            color: #4f46e5;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        .school-address {
            font-size: 10px;
            color: #475569;
            margin-top: 2px;
            font-weight: 500;
        }

        .title-badge-container {
            text-align: center;
            margin-bottom: 10px;
        }

        .form-title-badge {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 1.5px;
            padding: 5px 24px;
            border-radius: 30px;
            text-transform: uppercase;
        }

        .urdu-subtitle {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 12px;
            color: #334155;
            margin-top: 3px;
            font-weight: bold;
        }

        .top-meta-strip {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 6px 12px;
            margin-bottom: 10px;
            font-size: 11px;
            font-weight: 700;
        }

        .meta-item { display: flex; gap: 5px; }
        .meta-label { color: #64748b; text-transform: uppercase; font-size: 10px; }
        .meta-value { color: #0f172a; font-weight: 900; }

        .main-content-layout {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }

        .details-column { flex: 1; }

        .photo-column {
            width: 110px;
            text-align: center;
            flex-shrink: 0;
        }

        .photo-box {
            width: 105px;
            height: 120px;
            border: 2px solid #0f172a;
            border-radius: 8px;
            overflow: hidden;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 4px;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }

        .section-header {
            background: #e0e7ff;
            color: #3730a3;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 4px 10px;
            border-left: 4px solid #4f46e5;
            margin-bottom: 6px;
            border-radius: 0 4px 4px 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .data-table th, .data-table td {
            padding: 4px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .data-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            width: 25%;
            font-size: 10px;
            text-transform: uppercase;
        }

        .data-table td {
            color: #0f172a;
            font-weight: 700;
        }

        .declaration-box {
            border: 2px dashed #94a3b8;
            background: #fafafa;
            border-radius: 10px;
            padding: 9px 14px;
            margin-top: 6px;
            margin-bottom: 12px;
        }

        .declaration-title {
            font-size: 11px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .declaration-text-en {
            font-size: 10px;
            color: #334155;
            line-height: 1.45;
            margin-bottom: 5px;
        }

        .declaration-text-ur {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 11px;
            color: #1e293b;
            direction: rtl;
            text-align: right;
            line-height: 1.7;
            font-weight: bold;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 8px;
            gap: 40px;
        }

        .sig-box {
            flex: 1;
            text-align: center;
        }

        .sig-line {
            border-top: 2px solid #0f172a;
            margin-bottom: 6px;
        }

        .sig-title-en {
            font-size: 12px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
        }

        .sig-title-ur {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 11px;
            color: #475569;
            font-weight: bold;
            margin-top: 2px;
        }

.footer-note {
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <div class="no-print-bar no-print">
        <div style="font-weight: bold; font-size: 14px; color: #1e293b;">
            <i class="fas fa-file-invoice text-indigo-600 mr-2"></i> Student Admission Form
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="fas fa-print"></i> Print Form
            </button>
            <button onclick="downloadPDF()" class="btn-action btn-pdf">
                <i class="fas fa-file-pdf"></i> Download PDF
            </button>
            <button onclick="window.close()" class="btn-action btn-close">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>

    <div class="page-card" id="admission_form_doc">
        <div>
            <!-- Header -->
            <div class="form-header">
                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="School Logo" class="header-logo" onerror="this.style.display='none'">
                <div class="header-text">
                    <div class="school-name"><?php echo htmlspecialchars($settings['school_name'] ?? 'SCHOOL MANAGEMENT SYSTEM'); ?></div>
                    <div class="school-subtitle">GOVERNMENT OF SINDH &bull; EDUCATION & LITERACY DEPARTMENT</div>
                    <div class="school-address">
                        <?php echo htmlspecialchars($settings['school_address'] ?? ''); ?>
                        <?php if (!empty($settings['semis_code'])): ?> | SEMIS Code: <strong><?php echo htmlspecialchars($settings['semis_code']); ?></strong><?php endif; ?>
                    </div>
                </div>
                <div style="width: 85px; text-align: right;" class="no-print">
                    <i class="fas fa-graduation-cap fa-3x" style="color: #cbd5e1;"></i>
                </div>
            </div>

            <!-- Title -->
            <div class="title-badge-container">
                <div class="form-title-badge">STUDENT ADMISSION FORM</div>
                <div class="urdu-subtitle">طالب علم کا داخلہ فارم</div>
            </div>

            <!-- Top Meta Strip -->
            <div class="top-meta-strip">
                <div class="meta-item">
                    <span class="meta-label">GR NO:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($student['gr_no']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">ADMISSION CLASS:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($student['admission_class'] ?: $student['current_class']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">CURRENT CLASS:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($student['current_class']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">ADMISSION DATE:</span>
                    <span class="meta-value"><?php echo $admDateFormatted; ?></span>
                </div>
            </div>

            <!-- Main Info Layout -->
            <div class="main-content-layout">
                <div class="details-column">
                    <div class="section-header"><i class="fas fa-user-graduate mr-1"></i> Student Personal Details</div>
                    <table class="data-table">
                        <tr>
                            <th>Student Full Name</th>
                            <td colspan="3" style="font-size: 13px; font-weight: 900; color: #1e1b4b; text-transform: uppercase;"><?php echo htmlspecialchars($student['student_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Father's Name</th>
                            <td colspan="3" style="font-size: 12px; font-weight: 700; text-transform: uppercase;"><?php echo htmlspecialchars($student['father_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Gender</th>
                            <td><?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></td>
                            <th>Date of Birth</th>
                            <td><?php echo $dobFormatted; ?> <?php echo !empty($student['age']) ? "({$student['age']} Yrs)" : ''; ?></td>
                        </tr>
                        <tr>
                            <th>Caste / Surname</th>
                            <td><?php echo htmlspecialchars($student['caste'] ?? 'N/A'); ?></td>
                            <th>Religion</th>
                            <td><?php echo htmlspecialchars($student['religion'] ?? 'Islam'); ?></td>
                        </tr>
                        <tr>
                            <th>Place of Birth</th>
                            <td colspan="3"><?php echo htmlspecialchars($student['place_of_birth'] ?? 'N/A'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="photo-column">
                    <div class="photo-box">
                        <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Student Photo">
                    </div>
                    <div class="photo-label">Passport Photo</div>
                </div>
            </div>

            <!-- Parent & Contact Info -->
            <div class="section-header"><i class="fas fa-users mr-1"></i> Guardian & Identification Info</div>
            <table class="data-table">
                <tr>
                    <th>B-Form / Student CNIC</th>
                    <td><?php echo htmlspecialchars($student['b_form_no'] ?? 'N/A'); ?></td>
                    <th>Father / Guardian CNIC</th>
                    <td><?php echo htmlspecialchars($student['father_cnic'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Father / Contact Mobile</th>
                    <td><?php echo htmlspecialchars($student['father_contact'] ?? 'N/A'); ?></td>
                    <th>Student Group / Stage</th>
                    <td><?php echo htmlspecialchars($student['student_group'] ?: ($student['stage'] ?? 'N/A')); ?></td>
                </tr>
                <tr>
                    <th>District</th>
                    <td><?php echo htmlspecialchars($student['district'] ?? 'N/A'); ?></td>
                    <th>Taluka</th>
                    <td><?php echo htmlspecialchars($student['taluka'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>School Name</th>
                    <td colspan="3"><?php echo htmlspecialchars($student['school_name'] ?? ($settings['school_name'] ?? 'N/A')); ?></td>
                </tr>
                <?php if (!empty($student['previous_school'])): ?>
                <tr>
                    <th>Previous School</th>
                    <td colspan="3"><?php echo htmlspecialchars($student['previous_school']); ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <!-- Declaration Box -->
            <div class="declaration-box">
                <div class="declaration-title">
                    <i class="fas fa-file-contract text-indigo-600"></i> Parent / Guardian Declaration & Undertaking
                </div>
                <div class="declaration-text-en">
                    I hereby solemnly declare that all the information and particulars provided above regarding my child/ward are true, correct, and complete to the best of my knowledge and belief. No facts have been concealed. I promise to abide by all the rules, regulations, discipline, and fee policies of the school.
                </div>
                <div class="declaration-text-ur">
                    میں حلفاً اقرار کرتا/کرتی ہوں کہ اپنے بچے/بچی کے بارے میں درج بالا دی گئی تمام معلومات میرے علم اور یقین کے مطابق بالکل درست اور صحیح ہیں- میں سکول کے تمام قوانین، نظم و ضبط اور فیس کی پابندی کا مکمل پابند رہوں گا۔
                </div>
            </div>
        </div>

        <div>
            <!-- Signatures -->
            <div class="signature-section">
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-title-en">Parent / Guardian Signature</div>
                    <div class="sig-title-ur">والد / سرپرست کے دستخط</div>
                </div>
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-title-en">Principal Signature & Stamp</div>
                    <div class="sig-title-ur">پرنسپل کے دستخط و مہر</div>
                </div>
            </div>

            <div class="footer-note">
                Generated on <?php echo date('d-m-Y h:i A'); ?> &bull; System Verified Record &bull; <?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>
            </div>
        </div>
    </div>

    <script>
    function downloadPDF() {
        const element = document.getElementById('admission_form_doc');
        const opt = {
            margin:       [5, 5, 5, 5],
            filename:     'Admission_Form_GR_<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $student['gr_no']); ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
    </script>
</body>
</html>
