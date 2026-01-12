<?php
require_once '../includes/auth_session.php';
require_once '../includes/db.php';

// Check if user can access this page
if (!canAccessPage('alumni.php')) {
    header("Location: index.php");
    exit;
}
$db = new Database();

// Get filter inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$yearFilter = isset($_GET['year']) ? $_GET['year'] : '';

// Get all alumni students
$allStudents = $db->readData();
$alumniStudents = array_filter($allStudents, function($student) use ($search, $yearFilter) {
    $isAlumni = isset($student['student_status']) && $student['student_status'] === 'Alumni';
    if (!$isAlumni) return false;

    // Filter by year
    if ($yearFilter) {
        $gradYear = isset($student['graduation_year']) ? $student['graduation_year'] : 
                    (isset($student['updated_at']) ? date('Y', strtotime($student['updated_at'])) : '');
        if ($gradYear !== $yearFilter) return false;
    }

    // Filter by search keyword
    if ($search) {
        $nameMatch = stripos($student['student_name'] ?? '', $search) !== false;
        $grMatch = stripos($student['gr_no'] ?? '', $search) !== false;
        if (!$nameMatch && !$grMatch) return false;
    }

    return true;
});

// Sort by Graduation Year (desc) then GR number (asc)
usort($alumniStudents, function($a, $b) {
    $yearA = $a['graduation_year'] ?? (isset($a['updated_at']) ? date('Y', strtotime($a['updated_at'])) : '0');
    $yearB = $b['graduation_year'] ?? (isset($b['updated_at']) ? date('Y', strtotime($b['updated_at'])) : '0');
    
    if ($yearA != $yearB) return (int)$yearB - (int)$yearA;
    return (int)$a['gr_no'] - (int)$b['gr_no'];
});

// Get unique years for the filter dropdown
$years = array_unique(array_map(function($s) {
    return $s['graduation_year'] ?? (isset($s['updated_at']) ? date('Y', strtotime($s['updated_at'])) : 'Unknown');
}, array_filter($allStudents, fn($s) => ($s['student_status'] ?? '') === 'Alumni')));
rsort($years);
?>

<?php include '../includes/header.php'; ?>

<div class="bg-gradient-to-r from-primary to-green-900 text-white p-6 rounded-lg shadow-lg mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="text-center md:text-left">
        <h1 class="text-3xl font-bold">Alumni Network</h1>
        <p class="text-green-100 mt-1">Former students of GBPS Ali Bux Jarwar</p>
    </div>
    <div class="text-center md:text-right w-full md:w-auto p-3 bg-white/10 rounded-lg backdrop-blur-sm border border-white/20">
        <div class="text-4xl font-bold"><?php echo count($alumniStudents); ?></div>
        <div class="text-sm text-green-100 uppercase tracking-wider font-semibold">Total Records</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-lg p-6 mb-6 border border-gray-100">
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Alumnus</label>
            <div class="relative">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter Name or GR No..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
        </div>
        <div class="w-full md:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Graduation Year</label>
            <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                <option value="">All Years</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?php echo htmlspecialchars($y); ?>" <?php echo $yearFilter === $y ? 'selected' : ''; ?>><?php echo htmlspecialchars($y); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2 w-full md:w-auto">
            <button type="submit" class="flex-1 md:flex-none bg-primary text-white px-6 py-2 rounded-md hover:bg-accent transition-colors font-semibold">
                Filter
            </button>
            <?php if ($search || $yearFilter): ?>
                <a href="alumni.php" class="flex-1 md:flex-none bg-gray-100 text-gray-600 px-6 py-2 rounded-md hover:bg-gray-200 transition-colors font-semibold text-center">
                    Clear
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100">
    <?php if (empty($alumniStudents)): ?>
    <div class="p-12 text-center">
        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user-graduate text-gray-300 text-4xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800">No Alumni Found</h3>
        <p class="text-gray-500 max-w-xs mx-auto">We couldn't find any alumni records matching your current filters.</p>
        <?php if ($search || $yearFilter): ?>
            <a href="alumni.php" class="mt-4 inline-block text-primary font-semibold hover:underline">Show all alumni</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 font-bold">
                    <th class="p-4 w-16">S#</th>
                    <th class="p-4">GR No</th>
                    <th class="p-4">Student Information</th>
                    <th class="p-4 text-center">Last Class</th>
                    <th class="p-4 text-center">Graduated</th>
                    <th class="p-4">Admission Date</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php $i = 1; foreach ($alumniStudents as $student): 
                    $graduationYear = $student['graduation_year'] ?? (isset($student['updated_at']) ? date('Y', strtotime($student['updated_at'])) : 'Unknown');
                    $lastClass = $student['last_class'] ?? 'N/A';
                    if ($lastClass === 'N/A' && isset($student['current_class']) && strpos($student['current_class'], 'Alumni') === false) {
                        $lastClass = $student['current_class'];
                    }
                ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="p-4 text-gray-400 font-medium text-sm"><?php echo $i++; ?></td>
                    <td class="p-4 text-gray-700 font-bold"><?php echo htmlspecialchars($student['gr_no']); ?></td>
                    <td class="p-4 text-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <?php if (!empty($student['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="w-10 h-10 rounded-lg object-cover shadow-sm">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold border border-emerald-100">
                                        <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 border-2 border-white rounded-full" title="Alumni Status"></div>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 capitalize"><?php echo htmlspecialchars($student['student_name']); ?></div>
                                <div class="text-[10px] text-gray-500 font-medium flex items-center gap-1">
                                    <i class="fas fa-user-friends"></i> F: <?php echo htmlspecialchars($student['father_name']); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-center">
                        <span class="px-2 py-1 rounded bg-blue-50 text-blue-600 text-[10px] font-bold border border-blue-100">
                           Class: <?php echo htmlspecialchars($lastClass); ?>
                        </span>
                    </td>
                    <td class="p-4 text-center">
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-600 text-white shadow-sm inline-flex items-center gap-1">
                            <i class="fas fa-calendar-alt text-[9px]"></i> <?php echo $graduationYear; ?>
                        </span>
                    </td>
                    <td class="p-4 text-gray-500 text-xs italic font-medium"><?php echo htmlspecialchars($student['admission_date']); ?></td>
                    <td class="p-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="student_form.php?id=<?php echo $student['id']; ?>&restore=1" class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Restore Student">
                                <i class="fas fa-undo-alt text-xs"></i>
                            </a>
                            <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="Full Profile">
                                <i class="fas fa-user-graduate text-xs"></i>
                            </a>
                            <a href="generate_id_card.php?id=<?php echo $student['id']; ?>" class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center hover:bg-amber-600 hover:text-white transition-all shadow-sm" title="Print Certificate Placeholder (View ID)">
                                <i class="fas fa-certificate text-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-indigo-600 rounded-xl p-6 text-white shadow-lg overflow-hidden relative">
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4">
            <i class="fas fa-graduation-cap text-9xl"></i>
        </div>
        <h4 class="font-bold mb-2">Class Reunions</h4>
        <p class="text-indigo-100 text-sm mb-4">You can now filter alumni by their graduation year to organize class reunions or track cohort progress.</p>
        <div class="text-[10px] font-bold uppercase tracking-widest opacity-70">Best Practice Implementation</div>
    </div>
    
    <div class="bg-emerald-600 rounded-xl p-6 text-white shadow-lg overflow-hidden relative">
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4">
            <i class="fas fa-search-location text-9xl"></i>
        </div>
        <h4 class="font-bold mb-2">Detailed Tracking</h4>
        <p class="text-emerald-100 text-sm mb-4">We now preserve the "Last Class" attended, even if the student left before completing Class 5.</p>
        <div class="text-[10px] font-bold uppercase tracking-widest opacity-70">Data Integrity</div>
    </div>

    <div class="bg-amber-600 rounded-xl p-6 text-white shadow-lg overflow-hidden relative">
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4">
            <i class="fas fa-chart-line text-9xl"></i>
        </div>
        <h4 class="font-bold mb-2">Future Ready</h4>
        <p class="text-amber-100 text-sm mb-4">This centralized database is ready for integration with career tracking or alumni messaging systems.</p>
        <div class="text-[10px] font-bold uppercase tracking-widest opacity-70">Scalable Architecture</div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php include '../includes/footer.php'; ?>
