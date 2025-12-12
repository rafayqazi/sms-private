<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('parents.php')) {
    header("Location: index.php");
    exit;
}
$db = new Database();

// Fetch all students
$students = $db->readData();

// Deduplicate parents based on Father CNIC
$parents = [];
foreach ($students as $student) {
    $cnic = isset($student['father_cnic']) ? trim($student['father_cnic']) : '';
    
    // If CNIC is empty, we might want to skip or use a unique ID. 
    // For now, let's group by CNIC if present, otherwise treat as unique if name exists.
    // To keep it simple and safe, we'll key by CNIC if available, else by a unique key (like student ID) to show them separately.
    
    $key = $cnic;
    if (empty($key)) {
        $key = 'no_cnic_' . $student['id'];
    }

    if (!isset($parents[$key])) {
        $parents[$key] = [
            'father_name' => $student['father_name'],
            'father_contact' => $student['father_contact'],
            'father_cnic' => $student['father_cnic'],
            'father_cnic_front' => isset($student['father_cnic_front']) ? $student['father_cnic_front'] : '',
            'father_cnic_back' => isset($student['father_cnic_back']) ? $student['father_cnic_back'] : '',
            'children_count' => 0,
            'children' => [] // Changed from children_names to children array of objects
        ];
    }
    
    $parents[$key]['children_count']++;
    // Add detailed child info
    $parents[$key]['children'][] = [
        'id' => $student['id'],
        'name' => $student['student_name'],
        'class' => $student['current_class'],
        'gr_no' => $student['gr_no'],
        'image' => isset($student['profile_image']) ? $student['profile_image'] : ''
    ];
}
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold">Parents Directory</h1>
        <p class="text-green-100 mt-1">View parent details and enrolled children</p>
    </div>
    <div class="w-full md:w-auto">
        <div class="relative text-gray-600 focus-within:text-gray-400">
            <span class="absolute inset-y-0 left-0 flex items-center pl-2">
                <button type="submit" class="p-1 focus:outline-none focus:shadow-outline">
                    <i class="fas fa-search"></i>
                </button>
            </span>
            <input type="search" id="parentSearchInput" class="py-2 text-sm text-gray-900 bg-white rounded-md pl-10 focus:outline-none focus:bg-white focus:text-gray-900 w-full md:w-64" placeholder="Search by Name, Contact, CNIC..." autocomplete="off">
        </div>
    </div>
</div>

<div class="bg-white shadow-lg rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                <tr>
                    <th class="p-4 text-left">Father Name</th>
                    <th class="p-4 text-left">Contact</th>
                    <th class="p-4 text-left">CNIC No</th>
                    <th class="p-4 text-center">CNIC (Front)</th>
                    <th class="p-4 text-center">CNIC (Back)</th>
                    <th class="p-4 text-center">Children</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($parents)): ?>
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-500">No parents found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($parents as $key => $parent): ?>
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="p-4 text-left whitespace-nowrap font-medium text-gray-800 capitalize"><?php echo htmlspecialchars($parent['father_name']); ?></td>
                        <td class="p-4 text-left text-gray-600"><?php echo htmlspecialchars($parent['father_contact']); ?></td>
                        <td class="p-4 text-left text-gray-600"><?php echo htmlspecialchars($parent['father_cnic']); ?></td>
                        <td class="p-4 text-center">
                            <?php if (!empty($parent['father_cnic_front'])): ?>
                                <div class="flex justify-center">
                                    <a href="<?php echo htmlspecialchars($parent['father_cnic_front']); ?>" target="_blank" class="transform hover:scale-110 transition duration-200">
                                        <img src="<?php echo htmlspecialchars($parent['father_cnic_front']); ?>" alt="Front" class="h-10 w-16 object-cover rounded border border-gray-300 shadow-sm">
                                    </a>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs italic">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-center">
                            <?php if (!empty($parent['father_cnic_back'])): ?>
                                <div class="flex justify-center">
                                    <a href="<?php echo htmlspecialchars($parent['father_cnic_back']); ?>" target="_blank" class="transform hover:scale-110 transition duration-200">
                                        <img src="<?php echo htmlspecialchars($parent['father_cnic_back']); ?>" alt="Back" class="h-10 w-16 object-cover rounded border border-gray-300 shadow-sm">
                                    </a>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs italic">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-center">
                            <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-semibold">
                                <?php echo $parent['children_count']; ?> Student(s)
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <button onclick='openParentModal(<?php echo json_encode($parent); ?>)' class="text-indigo-600 hover:text-indigo-900 transform hover:scale-110 transition duration-200" title="View Details">
                                <i class="fas fa-eye text-lg"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Parent Details Modal -->
<div id="parentModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeParentModal()"></div>

        <!-- Modal panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <div class="flex justify-between items-center border-b pb-3 mb-4">
                            <h3 class="text-2xl leading-6 font-bold text-gray-900" id="modal-title">
                                Parent Profile
                            </h3>
                            <button onclick="closeParentModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <!-- Parent Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Personal Details</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <div class="w-8 text-center text-gray-400 mr-3"><i class="fas fa-user"></i></div>
                                        <div>
                                            <p class="text-xs text-gray-500">Father Name</p>
                                            <p class="font-semibold text-gray-800 text-lg" id="modalFatherName"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-8 text-center text-gray-400 mr-3"><i class="fas fa-phone"></i></div>
                                        <div>
                                            <p class="text-xs text-gray-500">Contact</p>
                                            <p class="font-medium text-gray-800" id="modalContact"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-8 text-center text-gray-400 mr-3"><i class="fas fa-id-card"></i></div>
                                        <div>
                                            <p class="text-xs text-gray-500">CNIC Number</p>
                                            <p class="font-medium text-gray-800" id="modalCNIC"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">CNIC Images</h4>
                                <div class="flex space-x-4">
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500 mb-1 text-center">Front</p>
                                        <div id="modalCNICFront" class="h-32 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center overflow-hidden">
                                            <span class="text-gray-400 text-xs">No Image</span>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500 mb-1 text-center">Back</p>
                                        <div id="modalCNICBack" class="h-32 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center overflow-hidden">
                                            <span class="text-gray-400 text-xs">No Image</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Children List -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3 border-b pb-1">Enrolled Children</h4>
                            <div id="modalChildrenList" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Children cards will be injected here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeParentModal()">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openParentModal(parent) {
    // Populate Parent Details
    document.getElementById('modalFatherName').textContent = parent.father_name || 'N/A';
    document.getElementById('modalContact').textContent = parent.father_contact || 'N/A';
    document.getElementById('modalCNIC').textContent = parent.father_cnic || 'N/A';

    // Populate CNIC Images
    const frontContainer = document.getElementById('modalCNICFront');
    if (parent.father_cnic_front) {
        frontContainer.innerHTML = `<a href="${parent.father_cnic_front}" target="_blank"><img src="${parent.father_cnic_front}" class="w-full h-full object-cover" alt="CNIC Front"></a>`;
    } else {
        frontContainer.innerHTML = '<span class="text-gray-400 text-xs">No Image</span>';
    }

    const backContainer = document.getElementById('modalCNICBack');
    if (parent.father_cnic_back) {
        backContainer.innerHTML = `<a href="${parent.father_cnic_back}" target="_blank"><img src="${parent.father_cnic_back}" class="w-full h-full object-cover" alt="CNIC Back"></a>`;
    } else {
        backContainer.innerHTML = '<span class="text-gray-400 text-xs">No Image</span>';
    }

    // Populate Children List
    const childrenList = document.getElementById('modalChildrenList');
    childrenList.innerHTML = '';

    if (parent.children && parent.children.length > 0) {
        parent.children.forEach(child => {
            const childCard = document.createElement('a');
            childCard.href = `student_profile.php?id=${child.id}`;
            childCard.className = 'block group';
            
            const imageSrc = child.image ? child.image : 'assets/img/default-student.png'; // Fallback image
            
            childCard.innerHTML = `
                <div class="flex items-center p-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-indigo-300 transition duration-200">
                    <div class="flex-shrink-0 h-12 w-12">
                        <img class="h-12 w-12 rounded-full object-cover border border-gray-200" src="${imageSrc}" alt="${child.name}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(child.name)}&background=random'">
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">${child.name}</p>
                        <p class="text-xs text-gray-500">Class: ${child.class} | GR: ${child.gr_no}</p>
                    </div>
                    <div class="ml-auto text-gray-400 group-hover:text-indigo-500">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            `;
            childrenList.appendChild(childCard);
        });
    } else {
        childrenList.innerHTML = '<p class="text-gray-500 text-sm col-span-2 text-center py-4">No enrolled children found.</p>';
    }

    // Show Modal
    document.getElementById('parentModal').classList.remove('hidden');
}

function closeParentModal() {
    document.getElementById('parentModal').classList.add('hidden');
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closeParentModal();
    }
});

// Search functionality
document.getElementById('parentSearchInput').addEventListener('keyup', function() {
    let searchValue = this.value.toLowerCase();
    let table = document.querySelector('table tbody');
    let rows = table.getElementsByTagName('tr');
    let hasVisibleRow = false;

    // Remove any existing "no results" row
    let noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) {
        noResultsRow.remove();
    }

    for (let i = 0; i < rows.length; i++) {
        let row = rows[i];
        
        // Skip if it is the "no parents found" message from PHP
        if (row.cells.length === 1 && row.cells[0].colSpan === 7) {
            continue;
        }

        let name = row.cells[0].textContent.toLowerCase();
        let contact = row.cells[1].textContent.toLowerCase();
        let cnic = row.cells[2].textContent.toLowerCase();

        if (name.includes(searchValue) || contact.includes(searchValue) || cnic.includes(searchValue)) {
            row.style.display = "";
            hasVisibleRow = true;
        } else {
            row.style.display = "none";
        }
    }

    if (!hasVisibleRow) {
        let noRow = document.createElement('tr');
        noRow.id = 'noResultsRow';
        noRow.innerHTML = '<td colspan="7" class="p-8 text-center text-gray-500">No matching parents found.</td>';
        table.appendChild(noRow);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
