<?php
require_once '../includes/auth_session.php';
require_once '../includes/book_db.php';
require_once '../includes/db.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'issued';
$bookDb = new BookDatabase();
$db = new Database();

$pageTitle = "Book Bank Report";
$data = [];

$data = [];

// Determine type and fetch data
if ($type == 'issued') {
    $pageTitle = "Currently Issued Books";
    $rawIssued = $bookDb->getAllIssuedBooksDetails();
    
    // Fetch all students for mapping
    $allStudents = $db->readData(); 
    $studentMap = [];
    foreach ($allStudents as $s) {
        $studentMap[$s['id']] = $s;
    }

    foreach ($rawIssued as $issue) {
        // Only process student records (skip teachers)
        if (!isset($issue['recipient_type']) || $issue['recipient_type'] !== 'student') {
            continue;
        }
        
        $studentId = trim((string)$issue['recipient_id']); // Use recipient_id instead of student_id
        $student = null;
        
        // Direct map lookup
        if (isset($studentMap[$studentId])) {
            $student = $studentMap[$studentId];
        } 
        // Fallback for type mismatches
        else {
            foreach ($allStudents as $s) {
                if (trim((string)$s['id']) === $studentId) {
                    $student = $s;
                    break;
                }
            }
        }

        if ($student) {
            $issue['student_name'] = $student['student_name'];
            $issue['gr_no'] = $student['gr_no'];
            $issue['class'] = $student['current_class'];
            $issue['student_id'] = $studentId; // Add for backwards compatibility in view
            
            // Add to data
            $data[] = $issue;
        }
    }
} else {
    // Inventory or other
    header("Location: book_bank.php");
    exit;
}

$settings = $db->getSchoolSettings();
?>

<?php include '../includes/header.php'; ?>

<!-- Official Print Header -->
<div class="hidden print:block print:mb-8 border-b-2 border-gray-800 pb-4">
    <div class="flex justify-between items-start">
        <div class="flex items-center gap-4">
            <img src="../GBPS_LOGO.png" alt="Logo" class="w-24 h-24 object-contain">
            <div class="text-left">
                <h1 class="text-2xl font-extrabold uppercase text-black leading-tight"><?php echo htmlspecialchars($settings['school_name']); ?></h1>
                <p class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                    <?php echo htmlspecialchars($settings['address_tagline']); ?>
                </p>
                <p class="text-xs font-bold text-gray-900 mt-1">SEMIS CODE: <?php echo htmlspecialchars($settings['semis_code']); ?></p>
            </div>
        </div>
        <div class="text-right mt-2">
            <p class="text-xs text-gray-600">Generated: <?php echo date('d-M-Y h:i A'); ?></p>
        </div>
    </div>
    <div class="mt-6 text-center">
        <h2 class="text-xl font-bold uppercase underline decoration-2 underline-offset-4"><?php echo htmlspecialchars($pageTitle); ?></h2>
        <div id="printCurrentFilter" class="text-sm font-medium mt-1 italic hidden ml-2"></div>
    </div>
</div>

<div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
    <div class="flex items-center gap-2">
        <a href="book_bank.php" class="text-teal-600 hover:text-teal-800 flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <h1 class="text-2xl font-bold text-gray-800 ml-4"><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
        <div class="flex gap-2 w-full md:w-auto">
            <select id="classFilter" class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-teal-500 focus:border-teal-500">
                <option value="">All Classes</option>
                <?php foreach ($db->getClassNames() as $c): ?>
                    <option value="<?php echo $c; ?>">Class <?php echo $c; ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" id="searchInput" placeholder="Search..." class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-teal-500 focus:border-teal-500 w-full md:w-48">
        </div>
        <button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded shadow-sm hover:bg-gray-50 text-sm">
            <i class="fas fa-print mr-2"></i> Print
        </button>
    </div>
</div>

<div class="bg-white rounded-lg shadow-lg overflow-hidden p-6 print:shadow-none print:p-0">
    <div class="text-center mb-6 print:hidden">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="text-gray-500 text-sm mt-1">Generated on <?php echo date('d-M-Y'); ?></p>
    </div>


    <?php if (empty($data)): ?>
        <p class="text-center text-gray-500 py-8">No records found.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="reportTable">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="p-3 font-semibold text-gray-600 text-sm">Student Name</th>
                        <th class="p-3 font-semibold text-gray-600 text-sm">GR No</th>
                        <th class="p-3 font-semibold text-gray-600 text-sm">Class</th>
                        <th class="p-3 font-semibold text-gray-600 text-sm">Book Name</th>
                        <th class="p-3 font-semibold text-gray-600 text-sm">Subject</th>
                        <th class="p-3 font-semibold text-gray-600 text-sm">Issue Date</th>
                        <th class="p-3 font-semibold text-gray-600 text-sm no-print">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($data as $row): 
                        $searchText = strtolower($row['student_name'] . ' ' . $row['gr_no'] . ' ' . ($row['book_details']['name'] ?? ''));
                    ?>
                    <tr class="hover:bg-gray-50 filter-row" data-class="<?php echo htmlspecialchars($row['class']); ?>" data-search="<?php echo htmlspecialchars($searchText); ?>">
                        <td class="p-3 font-medium text-gray-800"><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($row['gr_no']); ?></td>
                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($row['class']); ?></td>
                        <td class="p-3 text-gray-800"><?php echo htmlspecialchars($row['book_details']['name'] ?? 'Unknown'); ?></td>
                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($row['book_details']['subject'] ?? '-'); ?></td>
                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($row['issue_date']); ?></td>
                        <td class="p-3 no-print">
                            <a href="book_bank_actions.php?student_id=<?php echo $row['student_id']; ?>" class="text-teal-600 hover:text-teal-800 text-sm font-medium">
                                Manage
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- No Results Message -->
            <p id="noResultsMsg" class="text-center text-gray-500 py-8 hidden">No matching records found.</p>
        </div>
    <?php endif; ?>

    <!-- Official Print Footer (Inside the container to keep it together or outside to stick to bottom? Inside is safer for short lists) -->
    <div class="hidden print:block mt-24 pt-4">
        <div class="flex justify-between items-end">
             <div class="text-center">
                <p class="font-bold border-t border-black pt-2 px-10 text-black">Incharge Book Bank</p>
            </div>
            <div class="text-center">
                <p class="font-bold text-lg mb-2 text-black"><?php echo htmlspecialchars($settings['headmaster_name']); ?></p>
                <p class="font-bold border-t border-black pt-2 px-10 text-black">Headmaster Signature</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classFilter = document.getElementById('classFilter');
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('.filter-row');
    const noResultsMsg = document.getElementById('noResultsMsg');

    function filterTable() {
        const selectedClass = classFilter.value;
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        rows.forEach(row => {
            const rowClass = row.getAttribute('data-class');
            const rowSearch = row.getAttribute('data-search');
            
            let classMatch = !selectedClass || rowClass === selectedClass;
            let searchMatch = !searchTerm || rowSearch.includes(searchTerm);

            if (classMatch && searchMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCount === 0 && rows.length > 0) {
            noResultsMsg.classList.remove('hidden');
        } else {
            noResultsMsg.classList.add('hidden');
        }

        // Print Filter Label Update
        const printLabel = document.getElementById('printCurrentFilter');
        if (printLabel) {
            if (selectedClass) {
                printLabel.textContent = '(Filtered by Class: ' + selectedClass + ')';
                printLabel.classList.remove('hidden');
                printLabel.classList.add('inline-block');
            } else {
                printLabel.classList.add('hidden');
                printLabel.classList.remove('inline-block');
            }
        }
    }

    classFilter.addEventListener('change', filterTable);
    searchInput.addEventListener('input', filterTable);
});
    // Print Fallback: Force hide known elements via JS if CSS fails
    window.onbeforeprint = function() {
        const elementsToHide = [
            'sidebar', 
            'chat-widget-btn', 
            'chat-widget-window', 
            'mobile-menu-btn', 
            'user-menu-btn'
        ];
        elementsToHide.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.style.setProperty('display', 'none', 'important');
        });
        
        // Hide all headers and footers
        document.querySelectorAll('header, footer, nav').forEach(el => {
            el.style.setProperty('display', 'none', 'important');
        });
    };
    
    window.onafterprint = function() {
        // Reload to restore state properly, or reset styles
        window.location.reload();
    };
});
</script>

<style>
    @media print {
        @page {
            margin: 0.5cm;
            size: auto;
        }

        /* Hiding Elements - Be very specific and broad */
        .no-print, 
        #sidebar, 
        .sidebar,
        #chat-widget-btn, 
        #chat-widget-window, 
        #mobile-menu-btn,
        header, 
        nav, 
        footer,
        .app-container > aside { 
            display: none !important; 
        }

        /* Reset Body */
        body { 
            background: white !important; 
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
        }
        
        /* Layout Reset */
        .app-container {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .main-content { 
            margin: 0 !important; 
            padding: 0 !important; 
            width: 100% !important;
            max-width: 100% !important;
        }
        
        /* Remove Shadows */
        .shadow-lg, .shadow-sm, .shadow { 
            box-shadow: none !important; 
        }
        
        /* Table Styling */
        table { 
            border-collapse: collapse !important; 
            width: 100% !important; 
        }
        th, td { 
            border: 1px solid black !important; 
            padding: 8px !important; 
            color: black !important; 
            font-size: 12pt !important;
        }
        thead th { 
            background-color: #f3f4f6 !important; 
            font-weight: bold !important; 
        }
        
        /* Hide Action Column */
        td:last-child, th:last-child { 
            display: none !important; 
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
