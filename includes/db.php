<?php
date_default_timezone_set('Asia/Karachi'); // Set timezone to Pakistan/Karachi

require_once __DIR__ . '/license.php';
require_once __DIR__ . '/install_check.php';
License::checkAndRedirect();

// Maintenance Mode Check
$settingsFile = __DIR__ . '/../data/settings.json';
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    if (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] === true) {
        $currentPage = basename($_SERVER['PHP_SELF']);
        
        // Redirect ALL users (including admins) to maintenance.php if maintenance mode is active
        // EXCEPT if they are on the maintenance page itself or the login page
        if ($currentPage !== 'maintenance.php' && $currentPage !== 'login.php') {
            header('Location: maintenance.php');
            exit();
        }
    }
}

class Database {
    private $csvFile;
    public $headers;
    private static $students_cache = null;
    private static $fee_collections_cache = null;
    private static $fee_structure_cache = null;
    private static $student_custom_fees_cache = null;

    public function __construct($file = null) {
        if ($file === null) {
            $file = __DIR__ . '/../data/database.csv';
        }
        $this->csvFile = $file;
        // New Schema Headers
        $this->headers = [
            'id', 'gr_no', 'student_name', 'father_name', 'gender', 'date_of_birth', 
            'admission_date', 'admission_class', 'current_class', 'age', 'b_form_no', 'father_cnic', 
            'father_contact', 'district', 'taluka', 'school_name', 'semis_code', 
            'is_active', 'created_at', 'updated_at', 'father_cnic_front', 
            'father_cnic_back', 'b_form_img', 'profile_image', 'previous_school', 'slc_img',
            'student_status', 'is_repeater', 'graduation_year', 'last_class',
            'caste', 'religion', 'place_of_birth', 'student_group'
        ];

        if (!is_dir(dirname($this->csvFile))) {
            mkdir(dirname($this->csvFile), 0755, true);
        }

        if (!file_exists($this->csvFile)) {
            $this->writeData([]);
        }
    }

    private function getHeaders() {
        if (file_exists($this->csvFile) && ($handle = fopen($this->csvFile, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 0, ",");
            fclose($handle);
            return $headers;
        }
        return $this->headers;
    }

    private function safeCombine($headers, $row) {
        if (count($row) < count($headers)) {
            $row = array_pad($row, count($headers), '');
        } elseif (count($row) > count($headers)) {
            $row = array_slice($row, 0, count($headers));
        }
        return array_combine($headers, $row);
    }

    public function readData() {
        if (self::$students_cache !== null) {
            return self::$students_cache;
        }
        $data = [];
        if (file_exists($this->csvFile) && ($handle = fopen($this->csvFile, "r")) !== FALSE) {
            $fileHeaders = fgetcsv($handle, 0, ","); // Skip headers
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                // Trim all fields in the row
                $row = array_map('trim', $row);
                
                $data[] = $this->safeCombine($this->headers, $row);
            }
            fclose($handle);
        }
        self::$students_cache = $data;
        return $data;
    }

    public function writeData($data) {
        self::$students_cache = $data;
        $fp = @fopen($this->csvFile, 'w');
        if ($fp === false) {
            error_log("Failed to open CSV file for writing: " . $this->csvFile);
            return false;
        }
        
        fputcsv($fp, $this->headers);
        foreach ($data as $fields) {
            // Ensure fields are in the correct order
            $row = [];
            foreach ($this->headers as $header) {
                $row[] = isset($fields[$header]) ? $fields[$header] : '';
            }
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function addStudent($studentData) {
        $data = $this->readData();
        // Auto-increment ID
        $lastId = 0;
        if (!empty($data)) {
            $lastItem = end($data);
            $lastId = isset($lastItem['id']) ? (int)$lastItem['id'] : 0;
        }
        $studentData['id'] = $lastId + 1;
        
        // Set timestamps
        $now = date('Y-m-d H:i:s');
        $studentData['created_at'] = $now;
        $studentData['updated_at'] = $now;
        $studentData['is_active'] = isset($studentData['is_active']) ? $studentData['is_active'] : 1;

        // Calculate Age if DOB is present
        if (!empty($studentData['date_of_birth'])) {
            $dob = new DateTime($studentData['date_of_birth']);
            $today = new DateTime();
            $studentData['age'] = $dob->diff($today)->y;
        } else {
            $studentData['age'] = '';
        }

        $data[] = $studentData;
        $this->writeData($data);
        return true;
    }

    public function bulkAddStudents($studentsArray) {
        $data = $this->readData();
        
        // Index existing students by GR Number for fast lookup
        $existingStudents = [];
        $lastId = 0;
        foreach ($data as $index => $s) {
            if (!empty($s['gr_no'])) {
                $existingStudents[$s['gr_no']] = $index;
            }
            if (isset($s['id'])) {
                $lastId = max($lastId, (int)$s['id']);
            }
        }

        $now = date('Y-m-d H:i:s');
        $today = new DateTime();
        $counts = ['added' => 0, 'updated' => 0];

        foreach ($studentsArray as $studentData) {
            $grNo = $studentData['gr_no'] ?? '';
            
            if (!empty($grNo) && isset($existingStudents[$grNo])) {
                // UPDATE existing student
                $idx = $existingStudents[$grNo];
                // Preserve original ID and created_at
                $studentData['id'] = $data[$idx]['id'];
                $studentData['created_at'] = $data[$idx]['created_at'];
                $studentData['updated_at'] = $now;
                
                // Merge data (incoming data overwrites existing)
                // We merge with headers to ensure all keys exist
                foreach ($this->headers as $h) {
                    if (!isset($studentData[$h])) {
                        $studentData[$h] = $data[$idx][$h] ?? '';
                    }
                }
                
                $data[$idx] = $studentData;
                $counts['updated']++;
            } else {
                // ADD new student
                $lastId++;
                $studentData['id'] = $lastId;
                $studentData['created_at'] = $now;
                $studentData['updated_at'] = $now;
                $studentData['is_active'] = isset($studentData['is_active']) ? $studentData['is_active'] : 1;
                
                // Ensure all keys exist
                foreach ($this->headers as $h) {
                    if (!isset($studentData[$h])) $studentData[$h] = '';
                }

                $data[] = $studentData;
                $counts['added']++;
            }

            // Recalculate age for the current studentData (either new or updated)
            // Note: $studentData might have been moved to $data[$idx] or $data[]
            // We need to operate on the reference if we want $data to be updated
            // Re-fetching from $data based on latest addition or update
            $targetIdx = isset($existingStudents[$grNo]) ? $existingStudents[$grNo] : count($data) - 1;
            
            if (!empty($data[$targetIdx]['date_of_birth'])) {
                try {
                    $dob = new DateTime($data[$targetIdx]['date_of_birth']);
                    $data[$targetIdx]['age'] = $dob->diff($today)->y;
                } catch (Exception $e) {
                    $data[$targetIdx]['age'] = '';
                }
            } else {
                $data[$targetIdx]['age'] = '';
            }
        }

        if ($this->writeData($data)) {
            return $counts;
        }
        return false;
    }

    public function getNextGrNo() {
        $data = $this->readData();
        $maxGr = 0;
        foreach ($data as $student) {
            $rawGr = isset($student['gr_no']) ? (string)$student['gr_no'] : '0';
            // Extract only digits
            $numericGr = preg_replace('/[^0-9]/', '', $rawGr);
            if ($numericGr !== '') {
                $gr = (int)$numericGr;
                if ($gr > $maxGr) {
                    $maxGr = $gr;
                }
            }
        }
        return $maxGr + 1;
    }

    public function updateStudent($id, $updatedData) {
        $data = $this->readData();
        $found = false;
        foreach ($data as $key => $student) {
            if ($student['id'] == $id) {
                // Merge existing data with updates
                $student = array_merge($student, $updatedData);
                
                // Update timestamp
                $student['updated_at'] = date('Y-m-d H:i:s');

                // Recalculate Age if DOB changed
                if (isset($updatedData['date_of_birth'])) {
                    $dob = new DateTime($student['date_of_birth']);
                    $today = new DateTime();
                    $student['age'] = $dob->diff($today)->y;
                }

                $data[$key] = $student;
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->writeData($data);
            return true;
        }
        return false;
    }

    public function getStudent($id) {
        $data = $this->readData();
        foreach ($data as $student) {
            if ($student['id'] == $id) {
                return $student;
            }
        }
        return null;
    }

    public function bulkRestoreStudents($ids, $targetClass) {
        $students = $this->readData();
        $count = 0;
        foreach ($students as &$student) {
            if (in_array($student['id'], $ids)) {
                $student['student_status'] = 'Active';
                $student['current_class'] = $targetClass;
                $student['graduation_year'] = '';
                $student['last_class'] = '';
                $student['updated_at'] = date('Y-m-d H:i:s');
                $count++;
            }
        }
        
        if ($count > 0) {
            return $this->writeData($students);
        }
        return false;
    }

    public function bulkMarkAlumni($ids, $graduationYear) {
        $students = $this->readData();
        $count = 0;
        $ids = array_map('intval', $ids);
        foreach ($students as &$student) {
            if (in_array((int)$student['id'], $ids)) {
                $student['student_status'] = 'Alumni';
                $student['graduation_year'] = $graduationYear;
                $student['last_class'] = $student['current_class'] ?? 'N/A';
                $student['current_class'] = 'Alumni'; 
                $student['updated_at'] = date('Y-m-d H:i:s');
                $count++;
            }
        }
        
        if ($count > 0) {
            return $this->writeData($students);
        }
        return false;
    }

    public function getStudentByGrNo($grNo) {
        $data = $this->readData();
        foreach ($data as $student) {
            if ($student['gr_no'] == $grNo) {
                return $student;
            }
        }
        return null;
    }
    
    public function deleteStudent($id) {
        $data = $this->readData();
        $newData = [];
        foreach ($data as $student) {
            if ($student['id'] != $id) {
                $newData[] = $student;
            }
        }
        $this->writeData($newData);
        return true;
    }

    public function deleteStudents($ids) {
        $data = $this->readData();
        $newData = [];
        $ids = array_map('intval', $ids); // Ensure integers
        foreach ($data as $student) {
            if (!in_array((int)$student['id'], $ids)) {
                $newData[] = $student;
            }
        }
        $this->writeData($newData);
        return true;
    }

    public function updateStudentsField($ids, $field, $value) {
        $data = $this->readData();
        $ids = array_map('intval', $ids);
        $updated = false;
        foreach ($data as &$student) {
            if (in_array((int)$student['id'], $ids)) {
                $student[$field] = $value;
                $student['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
            }
        }
        if ($updated) {
            $this->writeData($data);
            return true;
        }
        return false;
    }
    
    
    public function searchStudents($query) {
        return $this->filterStudents(['search' => $query]);
    }

    public function filterStudents($filters = []) {
        $students = $this->readData();
        $results = [];

        // By default, exclude Alumni students unless explicitly requested
        $includeAlumni = isset($filters['include_alumni']) ? $filters['include_alumni'] : false;

        foreach ($students as $student) {
        // Skip Alumni students unless explicitly included
        if (!$includeAlumni && isset($student['student_status']) && $student['student_status'] === 'Alumni') {
            continue;
        }

        $match = true;

        // Class Filter
        if (!empty($filters['class'])) {
            $fClass = trim(strtolower($filters['class']));
            $sClass = trim(strtolower($student['current_class'] ?? ''));
            if ($sClass !== $fClass) {
                $match = false;
            }
        }

        // Gender Filter
        if ($match && !empty($filters['gender'])) {
            $fGender = trim(strtolower($filters['gender']));
            $sGender = trim(strtolower($student['gender'] ?? ''));
            if ($sGender !== $fGender) {
                $match = false;
            }
        }

        // Religion Filter
        if ($match && !empty($filters['religion'])) {
            $fReligion = trim(strtolower($filters['religion']));
            $sReligion = trim(strtolower($student['religion'] ?? ''));
            if ($sReligion !== $fReligion) {
                $match = false;
            }
        }

        // Search Filter (Name or GR No)
        if ($match && !empty($filters['search'])) {
            $searchTerm = strtolower($filters['search']);
            $studentName = strtolower($student['student_name'] ?? '');
            $grNo = strtolower($student['gr_no'] ?? '');
            
            if (strpos($studentName, $searchTerm) === false && strpos($grNo, $searchTerm) === false) {
                $match = false;
            }
        }

        if ($match) {
            $results[] = $student;
        }
    }

        // Sorting
        $sortBy = isset($filters['sort_by']) ? $filters['sort_by'] : '';
        $order = isset($filters['order']) ? strtoupper($filters['order']) : 'ASC';

        if ($sortBy === 'gr_no') {
            usort($results, function($a, $b) use ($order) {
                // GR No might be numeric or string, treat as integer for proper numeric sorting if possible
                $valA = (int)$a['gr_no'];
                $valB = (int)$b['gr_no'];
                
                if ($valA == $valB) return 0;
                return ($order === 'ASC') ? ($valA - $valB) : ($valB - $valA);
            });
        }

        return $results;
    }

    public function getAttendance($date, $class) {
        $file = __DIR__ . '/../data/attendance.csv';
        $attendance = [];
        
        if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 0, ","); // Skip headers
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                // Structure: date, class, student_id, status, created_at
                if (count($row) >= 4) {
                    if ($row[0] == $date && $row[1] == $class) {
                        $attendance[$row[2]] = $row[3]; // student_id => status
                    }
                }
            }
            fclose($handle);
        }
        return $attendance;
    }

    public function saveAttendance($date, $class, $attendanceData) {
        $file = __DIR__ . '/../data/attendance.csv';
        $allAttendance = [];
        $headers = ['date', 'class', 'student_id', 'status', 'created_at'];

        // Read existing data
        if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
            $fileHeaders = fgetcsv($handle, 0, ",");
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                if (count($row) >= 4) {
                    // If not the same date/class, keep it
                    if (!($row[0] == $date && $row[1] == $class)) {
                        $allAttendance[] = $row;
                    }
                }
            }
            fclose($handle);
        }

        // Add new data
        $now = date('Y-m-d H:i:s');
        foreach ($attendanceData as $studentId => $status) {
            $allAttendance[] = [$date, $class, $studentId, $status, $now];
        }

        // Write back
        $fp = fopen($file, 'w');
        fputcsv($fp, $headers);
        foreach ($allAttendance as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function isGrNoExists($grNo, $excludeId = null) {
        $data = $this->readData();
        foreach ($data as $student) {
            if ($student['gr_no'] == $grNo) {
                if ($excludeId && $student['id'] == $excludeId) {
                    continue;
                }
                return true;
            }
        }
        return false;
    }

    public function isBFormNoExists($bFormNo, $excludeId = null) {
        $data = $this->readData();
        foreach ($data as $student) {
            if (isset($student['b_form_no']) && $student['b_form_no'] == $bFormNo) {
                if ($excludeId && $student['id'] == $excludeId) {
                    continue;
                }
                return $student['student_name']; // Return name of the student who has this B-Form
            }
        }
        return false;
    }

    public function isTeacherCnicExists($cnic, $excludeId = null) {
        $file = __DIR__ . '/../data/teachers.csv';
        if (!file_exists($file)) return false;

        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            // ID is index 0, CNIC is index 4
            if (count($row) > 4 && $row[4] == $cnic) {
                if ($excludeId && $row[0] == $excludeId) {
                    continue;
                }
                fclose($handle);
                return true;
            }
        }
        fclose($handle);
        return false;
    }

    // Teacher Management
    public function addTeacher($data) {
        $file = __DIR__ . '/../data/teachers.csv';
        $headers = [
            'id', 'name', 'father_name', 'gender', 'cnic', 'dob', 'age', 'email', 
            'disability', 'payment_type', 'payment_no', 'iban', 'contact', 
            'retirement_date', 'designation', 'department', 'posting', 'basic_scale', 
            'address', 'district', 'tahsil', 'profile_image', 'joining_date', 'salary', 
            'assigned_classes', 'created_at'
        ];

        // Create file with headers if it doesn't exist
        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            fclose($fp);
        }

        // Generate ID
        $id = 1;
        if (file_exists($file)) {
            $rows = file($file);
            if (count($rows) > 1) {
                $lastRow = str_getcsv(trim(end($rows)));
                if (is_numeric($lastRow[0])) {
                    $id = $lastRow[0] + 1;
                }
            }
        }

        $record = [
            $id,
            $data['name'],
            $data['father_name'],
            $data['gender'],
            $data['cnic'],
            $data['dob'],
            $data['age'],
            $data['email'],
            $data['disability'],
            $data['payment_type'],
            $data['payment_no'],
            $data['iban'],
            $data['contact'],
            $data['retirement_date'],
            $data['designation'],
            $data['department'],
            $data['posting'],
            $data['basic_scale'],
            $data['address'],
            $data['district'],
            $data['tahsil'],
            isset($data['profile_image']) ? $data['profile_image'] : '',
            isset($data['joining_date']) ? $data['joining_date'] : '',
            isset($data['salary']) ? $data['salary'] : '0',
            isset($data['assigned_classes']) ? $data['assigned_classes'] : '',
            date('Y-m-d H:i:s')
        ];

        $fp = @fopen($file, 'a');
        if ($fp === false) {
            die("Error: Could not open data/teachers.csv. Please make sure the file is not open in another program (like Excel) and try again.");
        }
        fputcsv($fp, $record);
        fclose($fp);
        return $id;
    }

    public function getTeacher($id) {
        $file = __DIR__ . '/../data/teachers.csv';
        if (!file_exists($file)) return null;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if ($row[0] == $id) {
                // Adjust row to match headers length
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                } elseif (count($row) > count($headers)) {
                    $row = array_slice($row, 0, count($headers));
                }
                return $this->safeCombine($headers, $row);
            }
        }
        fclose($handle);
        return null;
    }

    public function getAllTeachers() {
        $file = __DIR__ . '/../data/teachers.csv';
        $teachers = [];
        if (!file_exists($file)) return $teachers;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) > 0) {
                // Adjust row to match headers length
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                } elseif (count($row) > count($headers)) {
                    $row = array_slice($row, 0, count($headers));
                }
                $teachers[] = $this->safeCombine($headers, $row);
            }
        }
        fclose($handle);
        return $teachers;
    }
    public function deleteTeacher($id) {
        $file = __DIR__ . '/../data/teachers.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if ($row[0] != $id) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function deleteTeachers($ids) {
        $file = __DIR__ . '/../data/teachers.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        $ids = array_map('intval', $ids);

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            // Keep header row (index 0 is 'id') or if ID not in list
            if (!is_numeric($row[0]) || !in_array((int)$row[0], $ids)) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function updateTeacher($id, $data) {
        $file = __DIR__ . '/../data/teachers.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        $headers = fgetcsv($handle, 0, ",");
        $rows[] = $headers; // Keep headers

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if ($row[0] == $id) {
                // Preserve created_at (last column)
                $createdAt = end($row);
                
                // Construct updated record
                $updatedRow = [
                    $id,
                    $data['name'],
                    $data['father_name'],
                    $data['gender'],
                    $data['cnic'],
                    $data['dob'],
                    $data['age'],
                    $data['email'],
                    $data['disability'],
                    $data['payment_type'],
                    $data['payment_no'],
                    $data['iban'],
                    $data['contact'],
                    $data['retirement_date'],
                    $data['designation'],
                    $data['department'],
                    $data['posting'],
                    $data['basic_scale'],
                    $data['address'],
                    $data['district'],
                    $data['tahsil'],
                    isset($data['profile_image']) ? $data['profile_image'] : (isset($row[21]) ? $row[21] : ''), // Use new image or keep old
                    isset($data['joining_date']) ? $data['joining_date'] : (isset($row[22]) ? $row[22] : ''), 
                    isset($data['salary']) ? $data['salary'] : (isset($row[23]) ? $row[23] : '0'),
                    isset($data['assigned_classes']) ? $data['assigned_classes'] : (isset($row[24]) ? $row[24] : ''),
                    $createdAt
                ];
                $rows[] = $updatedRow;
            } else {
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function getTotalTeacherSalaries() {
        $teachers = $this->getAllTeachers();
        $total = 0;
        foreach ($teachers as $t) {
            $total += (float)($t['salary'] ?? 0);
        }
        return $total;
    }

    public function payTeacherSalary($data) {
        $file = __DIR__ . '/../data/salary_payments.csv';
        $headers = ['id', 'teacher_id', 'month', 'base_salary', 'deduction', 'net_salary', 'payment_date', 'notes', 'created_at'];

        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            fclose($fp);
        }

        $id = 1;
        $rows = file($file);
        if (count($rows) > 1) {
            $lastRow = str_getcsv(trim(end($rows)));
            $id = (int)$lastRow[0] + 1;
        }

        $record = [
            $id,
            $data['teacher_id'],
            $data['month'],
            $data['base_salary'],
            $data['deduction'],
            $data['net_salary'],
            $data['payment_date'],
            $data['notes'],
            date('Y-m-d H:i:s')
        ];

        $fp = fopen($file, 'a');
        fputcsv($fp, $record);
        fclose($fp);
        return true;
    }

    public function getTeacherSalaryHistory($teacherId) {
        $file = __DIR__ . '/../data/salary_payments.csv';
        $history = [];
        if (!file_exists($file)) return $history;

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if ($row[1] == $teacherId) {
                $history[] = $this->safeCombine($headers, $row);
            }
        }
        fclose($handle);
        return $history;
    }

    public function getTeacherSalaryStatus($month) {
        $teachers = $this->getAllTeachers();
        $file = __DIR__ . '/../data/salary_payments.csv';
        $paidTeachers = [];

        if (file_exists($file)) {
            $handle = fopen($file, 'r');
            fgetcsv($handle); // skip headers
            while (($row = fgetcsv($handle)) !== false) {
                if ($row[2] == $month) {
                    $paidTeachers[$row[1]] = $row; // teacher_id => record
                }
            }
            fclose($handle);
        }

        $status = [];
        foreach ($teachers as $t) {
            $status[] = [
                'id' => $t['id'],
                'name' => $t['name'],
                'salary' => $t['salary'],
                'status' => isset($paidTeachers[$t['id']]) ? 'Paid' : 'Pending',
                'payment_info' => isset($paidTeachers[$t['id']]) ? $paidTeachers[$t['id']] : null
            ];
        }
        return $status;
    }

    public function getTotalPaidSalaries($month) {
        $file = __DIR__ . '/../data/salary_payments.csv';
        $total = 0;
        if (!file_exists($file)) return 0;

        $handle = fopen($file, 'r');
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if ($row[2] == $month) {
                $total += (float)$row[5]; // net_salary
            }
        }
        fclose($handle);
        return $total;
    }

    public function getAttendanceStats() {
        $file = __DIR__ . '/../data/attendance.csv';
        $todayDate = date('Y-m-d');
        
        $stats = [
            'overall' => ['Present' => 0, 'Absent' => 0, 'Leave' => 0],
            'class_wise' => [],
            'is_today' => false,
            'attendance_date' => null,
            'today_date' => $todayDate
        ];

        if (!file_exists($file)) return $stats;

        // 1. Load Students to get Gender info
        $allStudents = $this->readData();
        $studentInfo = [];
        foreach ($allStudents as $s) {
            $studentInfo[$s['id']] = ['gender' => $s['gender'], 'class' => $s['current_class']];
        }

        $handle = @fopen($file, "r");
        if ($handle === false) return $stats;

        $attendanceRecords = [];
        fgetcsv($handle); // Skip header

        // 2. Read all records and find max timestamp
        $maxTimestamp = 0;
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) < 4) continue;
            $dateStr = $row[0];
            $timestamp = strtotime($dateStr);
            if ($timestamp === false) continue;
            
            if ($timestamp > $maxTimestamp) {
                $maxTimestamp = $timestamp;
            }

            $attendanceRecords[] = [
                'timestamp' => $timestamp,
                'normalized_date' => date('Y-m-d', $timestamp),
                'class' => $row[1],
                'student_id' => $row[2],
                'status' => $row[3]
            ];
        }
        fclose($handle);

        if ($maxTimestamp === 0) return $stats;
        $latestDate = date('Y-m-d', $maxTimestamp);
        
        // Check if latest attendance is for today
        $stats['is_today'] = ($latestDate === $todayDate);
        $stats['attendance_date'] = $latestDate;

        // 3. Initialize Class Stats with Student Counts (Male/Female)
        foreach ($studentInfo as $id => $info) {
            $class = $info['class'];
            if (!isset($stats['class_wise'][$class])) {
                $stats['class_wise'][$class] = [
                    'Present' => 0, 
                    'Absent' => 0, 
                    'Total' => 0,
                    'Male' => 0,
                    'Female' => 0
                ];
            }
            // Count total students per class by gender
            if ($info['gender'] == 'Male') $stats['class_wise'][$class]['Male']++;
            elseif ($info['gender'] == 'Female') $stats['class_wise'][$class]['Female']++;
        }

        // 4. Aggregate stats for latest date only
        foreach ($attendanceRecords as $record) {
            if ($record['normalized_date'] !== $latestDate) continue;

            $class = $record['class'];
            $status = $record['status'];

            // Overall Stats
            if ($status == 'P') $stats['overall']['Present']++;
            elseif ($status == 'A') $stats['overall']['Absent']++;
            elseif ($status == 'L') $stats['overall']['Leave']++;

            // Class-wise Stats (Attendance only)
            if (!isset($stats['class_wise'][$class])) {
                $stats['class_wise'][$class] = [
                    'Present' => 0, 'Absent' => 0, 'Total' => 0, 'Male' => 0, 'Female' => 0
                ];
            }
            
            $stats['class_wise'][$class]['Total']++;
            
            if ($status == 'P') {
                $stats['class_wise'][$class]['Present']++;
            } elseif ($status == 'A') {
                $stats['class_wise'][$class]['Absent']++;
            }
        }

        // 5. Sort Class-wise Stats by Logical Order
        $classOrder = $this->getClassNames();
        $sortedClassStats = [];
        foreach ($classOrder as $className) {
            if (isset($stats['class_wise'][$className])) {
                $sortedClassStats[$className] = $stats['class_wise'][$className];
            }
        }
        // Append any remaining classes (in case some were deleted but still have data)
        foreach ($stats['class_wise'] as $className => $data) {
            if (!isset($sortedClassStats[$className])) {
                $sortedClassStats[$className] = $data;
            }
        }
        $stats['class_wise'] = $sortedClassStats;

        return $stats;
    }

    public function getStudentsByAttendanceStatus($status) {
        $attendanceFile = __DIR__ . '/../data/attendance.csv';
        $students = [];
        
        if (!file_exists($attendanceFile)) return $students;

        $handle = @fopen($attendanceFile, "r");
        if ($handle === false) return $students;

        // 1. Get all attendance records
        $attendanceRecords = [];
        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) < 4) continue;
            // Normalize date to Y-m-d
            $dateStr = $row[0];
            $timestamp = strtotime($dateStr);
            if ($timestamp === false) continue; // Skip invalid dates
            $normalizedDate = date('Y-m-d', $timestamp);

            $attendanceRecords[] = [
                'original_date' => $dateStr,
                'normalized_date' => $normalizedDate,
                'timestamp' => $timestamp,
                'class' => $row[1],
                'student_id' => $row[2],
                'status' => $row[3]
            ];
        }
        fclose($handle);

        if (empty($attendanceRecords)) return $students;

        // 2. Find the latest date (max timestamp)
        $maxTimestamp = 0;
        foreach ($attendanceRecords as $record) {
            if ($record['timestamp'] > $maxTimestamp) {
                $maxTimestamp = $record['timestamp'];
            }
        }
        
        if ($maxTimestamp === 0) return $students;
        
        $latestDate = date('Y-m-d', $maxTimestamp);

        // 3. Filter records for latest date and status
        $targetStudentIds = [];
        foreach ($attendanceRecords as $record) {
            if ($record['normalized_date'] == $latestDate && $record['status'] == $status) {
                $targetStudentIds[] = $record['student_id'];
            }
        }

        // 4. Get Student Details
        $allStudents = $this->readData(); // Reuse existing method to get all students
        foreach ($allStudents as $student) {
            if (in_array($student['id'], $targetStudentIds)) {
                $students[] = $student;
            }
        }

        return ['date' => $latestDate, 'students' => $students];
    }

    public function backupData() {
        $backupDir = __DIR__ . '/../backups/' . date('Y-m-d_H-i-s');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        // Backup CSVs
        $dataDir = __DIR__ . '/../data';
        $files = glob($dataDir . '/*.csv');
        foreach ($files as $file) {
            copy($file, $backupDir . '/' . basename($file));
        }
        // Backup Excel if exists
        $excelFiles = glob($dataDir . '/*.xlsx');
        foreach ($excelFiles as $file) {
            copy($file, $backupDir . '/' . basename($file));
        }

        // Backup Uploads
        $uploadsDir = __DIR__ . '/../uploads';
        $backupUploadsDir = $backupDir . '/uploads';
        if (file_exists($uploadsDir)) {
            mkdir($backupUploadsDir, 0777, true);
            $uploadFiles = glob($uploadsDir . '/*');
            foreach ($uploadFiles as $file) {
                if (is_file($file)) {
                    copy($file, $backupUploadsDir . '/' . basename($file));
                }
            }
        }
        return true;
    }

    public function resetData() {
    // Helper function to recursively delete a directory
    $deleteDirectory = function($dir) use (&$deleteDirectory) {
        if (!is_dir($dir)) return false;
        
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    };

    // 1. Completely delete data directory
    $dataDir = __DIR__ . '/../data';
    if (is_dir($dataDir)) {
        $deleteDirectory($dataDir);
    }

    // 2. Completely delete uploads directory
    $uploadsDir = __DIR__ . '/../uploads';
    if (is_dir($uploadsDir)) {
        $deleteDirectory($uploadsDir);
    }

    return true;
}

    // Student Promotion Methods
    public function getStudentsByClass($class) {
        $students = $this->readData();
        return array_filter($students, function($student) use ($class) {
            return $student['current_class'] === $class && 
                   (!isset($student['student_status']) || $student['student_status'] !== 'Alumni');
        });
    }

    public function getStudentFeeClass($student) {
        if (!$student) return '';
        $status = $student['student_status'] ?? 'Active';
        if ($status === 'Alumni') {
            return $student['last_class'] ?? $student['current_class'] ?? '';
        }
        return $student['current_class'] ?? '';
    }

    private function getStudentFeeCalcTargetMonth($student) {
        $status = $student['student_status'] ?? 'Active';
        if ($status === 'Alumni') {
            $leaveMonth = !empty($student['updated_at'])
                ? date('Y-m', strtotime($student['updated_at']))
                : date('Y-m');
            return date('Y-m', strtotime($leaveMonth . '-01 +1 month'));
        }
        return date('Y-m', strtotime(date('Y-m') . '-01 +1 month'));
    }

    public function getStudentTotalOutstandingFees($gr_no) {
        $student = $this->getStudentByGrNo($gr_no);
        if (!$student) return 0.0;

        $assignedMonthly = $this->getStudentAssignedMonthlyFee($student);

        $historyMap = [];
        foreach ($this->getStudentFeeHistory($gr_no) as $h) {
            $historyMap[$h['month_for']] = $h;
        }

        $targetMonth = $this->getStudentFeeCalcTargetMonth($student);

        if (empty($historyMap)) {
            if (($student['student_status'] ?? '') === 'Alumni') return 0.0;
            return max(0.0, $assignedMonthly);
        }

        return $this->calcDebtForStudent($student, $historyMap, $targetMonth, $assignedMonthly);
    }

    public function hasClearedAllFees($gr_no) {
        return $this->getStudentTotalOutstandingFees($gr_no) < 0.01;
    }

    public function getAlumniOutstandingBalance($gr_no) {
        $student = $this->getStudentByGrNo($gr_no);
        if (!$student) return 0.0;

        $history = $this->getStudentFeeHistory($gr_no);
        if (empty($history)) return 0.0;

        $total = 0.0;
        foreach ($history as $h) {
            $due = (float)($h['tuition_fee'] ?? 0)
                 + (float)($h['admission_fee'] ?? 0)
                 + (float)($h['exam_fee'] ?? 0)
                 + (float)($h['other_fee'] ?? 0)
                 - (float)($h['discount'] ?? 0);
            $paid = (float)($h['amount_paid'] ?? 0);
            $total += ($due - $paid);
        }
        return max(0.0, $total);
    }

    public function promoteStudent($id, $action) {
        return $this->bulkPromoteStudents([['id' => $id, 'action' => $action]]);
    }

    public function bulkPromoteStudents($promotions) {
        $students = $this->readData();
        $classes = $this->getClasses();
        $classProgression = [];
        
        $stageGroups = [];
        foreach ($classes as $c) {
            $s = $c['stage'] ?? 'Elementary';
            if (!isset($stageGroups[$s])) $stageGroups[$s] = [];
            $stageGroups[$s][] = $c;
        }

        foreach ($stageGroups as $stage => $groupClasses) {
            for ($i = 0; $i < count($groupClasses); $i++) {
                $currentName = $groupClasses[$i]['class_name'];
                if ($i < count($groupClasses) - 1) {
                    $classProgression[$currentName] = $groupClasses[$i+1]['class_name'];
                } else {
                    $classProgression[$currentName] = 'Alumni (Passed Students)';
                }
            }
        }

        $promoMap = [];
        foreach ($promotions as $p) {
            $promoMap[$p['id']] = $p['action'];
        }

        $modified = false;
        $promoted = 0;

        foreach ($students as &$student) {
            $sid = $student['id'];
            if (isset($promoMap[$sid])) {
                $action = $promoMap[$sid];
                $currentClass = $student['current_class'] ?? '';

                if (!isset($student['student_status'])) $student['student_status'] = 'Active';
                if (!isset($student['is_repeater'])) $student['is_repeater'] = '0';

                if ($action === 'pass') {
                    if (isset($classProgression[$currentClass])) {
                        $nextClass = $classProgression[$currentClass];
                        if ($nextClass === 'Alumni (Passed Students)') {
                            $student['student_status'] = 'Alumni';
                            $student['graduation_year'] = date('Y');
                            $student['last_class'] = $currentClass;
                            $student['current_class'] = 'Alumni';
                        } else {
                            $student['current_class'] = $nextClass;
                        }
                    }
                    $student['is_repeater'] = '0';
                } elseif ($action === 'passout') {
                    $student['student_status'] = 'Alumni';
                    $student['graduation_year'] = date('Y');
                    $student['last_class'] = $currentClass;
                    $student['current_class'] = 'Alumni';
                    $student['is_repeater'] = '0';
                } elseif ($action === 'fail') {
                    $student['is_repeater'] = '1';
                } elseif ($action === 'stay') {
                    $student['is_repeater'] = '0';
                }
                
                $student['updated_at'] = date('Y-m-d H:i:s');
                $modified = true;
                $promoted++;
            }
        }

        $saved = $modified ? $this->writeData($students) : false;

        return [
            'saved' => $saved,
            'promoted' => $promoted
        ];
    }

    public function createUserRole($teacherId, $role, $username, $password, $classes = [], $profileImage = '') {
        $file = __DIR__ . '/../data/user_roles.csv';
        $headers = ['id', 'teacher_id', 'role', 'username', 'password_hash', 'assigned_classes', 'profile_image', 'created_at', 'updated_at'];

        // Create file with headers if it doesn't exist
        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            fclose($fp);
        }

        // Check if username already exists
        if ($this->isUsernameExists($username)) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        // Check if teacher already has a role (only for actual teachers)
        if ($teacherId > 0 && $this->getUserRoleByTeacherId($teacherId)) {
            return ['success' => false, 'message' => 'Teacher already has a role assigned'];
        }

        // Generate ID
        $id = 1;
        if (file_exists($file)) {
            $rows = file($file);
            if (count($rows) > 1) {
                $lastRow = str_getcsv(trim(end($rows)));
                if (is_numeric($lastRow[0])) {
                    $id = $lastRow[0] + 1;
                }
            }
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Encode classes as JSON
        $classesJson = json_encode($classes);

        $record = [
            $id,
            $teacherId,
            $role,
            $username,
            $passwordHash,
            $classesJson,
            $profileImage,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        ];

        $fp = @fopen($file, 'a');
        if ($fp === false) {
            return ['success' => false, 'message' => 'Could not open user_roles.csv'];
        }
        fputcsv($fp, $record);
        fclose($fp);
        return ['success' => true, 'message' => 'User created successfully'];
    }

    public function getUserRoleByUsername($username) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return null;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) >= 6 && $row[3] == $username) {
                $userRole = $this->safeCombine($headers, $row);
                // Decode classes JSON
                $userRole['assigned_classes'] = json_decode($userRole['assigned_classes'], true);
                fclose($handle);
                return $userRole;
            }
        }
        fclose($handle);
        return null;
    }

    public function getUserRoleByTeacherId($teacherId) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return null;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) >= 6 && $row[1] == $teacherId) {
                $userRole = $this->safeCombine($headers, $row);
                // Decode classes JSON
                $userRole['assigned_classes'] = json_decode($userRole['assigned_classes'], true);
                fclose($handle);
                return $userRole;
            }
        }
        fclose($handle);
        return null;
    }

    public function updateUserRole($teacherId, $role, $username, $password, $classes = [], $profileImage = null) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return ['success' => false, 'message' => 'User roles file not found'];

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return ['success' => false, 'message' => 'Could not open user_roles.csv'];

        $headers = fgetcsv($handle, 0, ",");
        $rows[] = $headers;
        $found = false;

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            // Only update record where teacher_id matches and is > 0
            if ($teacherId > 0 && $row[1] == $teacherId) {
                // Check if username changed and if new username exists
                if ($row[3] != $username && $this->isUsernameExists($username, $teacherId)) {
                    fclose($handle);
                    return ['success' => false, 'message' => 'Username already exists'];
                }

                // Update record
                $passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : $row[4];
                $classesJson = json_encode($classes);
                $finalProfileImage = ($profileImage !== null) ? $profileImage : ($row[6] ?? '');
                
                $updatedRow = [
                    $row[0], // id
                    $teacherId,
                    $role,
                    $username,
                    $passwordHash,
                    $classesJson,
                    $finalProfileImage,
                    $row[7] ?? date('Y-m-d H:i:s'), // created_at
                    date('Y-m-d H:i:s') // updated_at
                ];
                $rows[] = $updatedRow;
                $found = true;
            } else {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (!$found) {
            return ['success' => false, 'message' => 'Role not found for this teacher'];
        }

        $fp = @fopen($file, 'w');
        if ($fp === false) return ['success' => false, 'message' => 'Could not write to user_roles.csv'];

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return ['success' => true, 'message' => 'Role updated successfully'];
    }

    public function updateUserRoleById($id, $role, $username, $password, $classes = [], $profileImage = null) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return ['success' => false, 'message' => 'User roles file not found'];

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return ['success' => false, 'message' => 'Could not open user_roles.csv'];

        $headers = fgetcsv($handle, 0, ",");
        $rows[] = $headers;
        $found = false;

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if ($row[0] == $id) {
                // Check if username changed and if new username exists
                if ($row[3] != $username) {
                    $existingUser = $this->getUserRoleByUsername($username);
                    if ($existingUser && $existingUser['id'] != $id) {
                        fclose($handle);
                        return ['success' => false, 'message' => 'Username already exists'];
                    }
                }

                // Update record
                $passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : $row[4];
                $classesJson = json_encode($classes);
                $finalProfileImage = ($profileImage !== null) ? $profileImage : ($row[6] ?? '');
                
                $updatedRow = [
                    $id,
                    $row[1], // teacher_id
                    $role,
                    $username,
                    $passwordHash,
                    $classesJson,
                    $finalProfileImage,
                    $row[7] ?? date('Y-m-d H:i:s'), // created_at
                    date('Y-m-d H:i:s') // updated_at
                ];
                $rows[] = $updatedRow;
                $found = true;
            } else {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (!$found) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $fp = @fopen($file, 'w');
        if ($fp === false) return ['success' => false, 'message' => 'Could not write to user_roles.csv'];

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return ['success' => true, 'message' => 'User updated successfully'];
    }

    public function deleteUserRole($teacherId) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return ['success' => false, 'message' => 'User roles file not found'];

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return ['success' => false, 'message' => 'Could not open user_roles.csv'];

        $found = false;
        $headers = fgetcsv($handle, 0, ",");
        $rows[] = $headers;

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            // Only delete if teacherId matches and is > 0
            if ($teacherId > 0 && $row[1] == $teacherId) {
                $found = true;
            } else {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (!$found) {
            return ['success' => false, 'message' => 'Role not found'];
        }

        $fp = @fopen($file, 'w');
        if ($fp === false) return ['success' => false, 'message' => 'Could not write to user_roles.csv'];

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return ['success' => true, 'message' => 'Role removed successfully'];
    }

    public function deleteUserRoleById($id) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return ['success' => false, 'message' => 'User roles file not found'];

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return ['success' => false, 'message' => 'Could not open user_roles.csv'];

        $found = false;
        $headers = fgetcsv($handle, 0, ",");
        $rows[] = $headers;

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if ($row[0] == $id) {
                $found = true;
            } else {
                $rows[] = $row;
            }
        }
        fclose($handle);

        if (!$found) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $fp = @fopen($file, 'w');
        if ($fp === false) return ['success' => false, 'message' => 'Could not write to user_roles.csv'];

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return ['success' => true, 'message' => 'User deleted successfully'];
    }

    public function getAllUserRoles() {
        $file = __DIR__ . '/../data/user_roles.csv';
        $userRoles = [];
        if (!file_exists($file)) return $userRoles;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) > 0) {
                $userRole = $this->safeCombine($headers, $row);
                // Decode classes JSON
                $userRole['assigned_classes'] = json_decode($userRole['assigned_classes'], true);
                $userRoles[] = $userRole;
            }
        }
        fclose($handle);
        return $userRoles;
    }

    public function isUsernameExists($username, $excludeTeacherId = null) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return false;

        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) > 3 && $row[3] == $username) {
                if ($excludeTeacherId && $row[1] == $excludeTeacherId) {
                    continue;
                }
                fclose($handle);
                return true;
            }
        }
        fclose($handle);
        return false;
    }


    // Messaging System Methods
    public function sendMessage($senderId, $senderType, $receiverId, $receiverType, $message) {
        $file = __DIR__ . '/../data/messages.csv';
        $headers = ['id', 'sender_id', 'sender_type', 'receiver_id', 'receiver_type', 'message', 'created_at', 'is_read'];

        // Create file with headers if it doesn't exist
        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            fclose($fp);
        }

        // Generate ID
        $id = 1;
        if (file_exists($file)) {
            $rows = file($file);
            if (count($rows) > 1) {
                $lastRow = str_getcsv(trim(end($rows)));
                if (is_numeric($lastRow[0])) {
                    $id = $lastRow[0] + 1;
                }
            }
        }

        $record = [
            $id,
            $senderId,
            $senderType, // 'admin', 'teacher', or 'parent'
            $receiverId,
            $receiverType, // 'admin', 'teacher', or 'parent'
            $message,
            date('Y-m-d H:i:s'),
            '0' // is_read = false
        ];

        $fp = @fopen($file, 'a');
        if ($fp === false) {
            return false;
        }
        fputcsv($fp, $record);
        fclose($fp);
        return true;
    }

    public function getConversation($userId1, $userId2) {
        $file = __DIR__ . '/../data/messages.csv';
        $messages = [];
        if (!file_exists($file)) return $messages;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) >= 7) {
                // Get messages between these two users (both directions)
                if (($row[1] == $userId1 && $row[3] == $userId2) || 
                    ($row[1] == $userId2 && $row[3] == $userId1)) {
                    
                    if (count($headers) !== count($row)) {
                        // Handle schema mismatch (7-col vs 8-col)
                        $msg = [
                            'id' => $row[0],
                            'sender_id' => $row[1],
                            'sender_type' => $row[2],
                            'receiver_id' => $row[3],
                            'receiver_type' => isset($row[4]) && count($row) === 8 ? $row[4] : ($row[2] === 'admin' ? 'teacher' : 'admin'),
                            'message' => count($row) === 8 ? $row[5] : $row[4],
                            'created_at' => count($row) === 8 ? $row[6] : $row[5],
                            'is_read' => count($row) === 8 ? $row[7] : $row[6]
                        ];
                    } else {
                        $msg = $this->safeCombine($headers, $row);
                    }
                    $messages[] = $msg;
                }
            }
        }
        fclose($handle);
        
        // Sort by created_at
        usort($messages, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        
        return $messages;
    }

    public function getAllConversations() {
        $file = __DIR__ . '/../data/messages.csv';
        $conversations = [];
        if (!file_exists($file)) return $conversations;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        $allMessages = [];
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            // Handle schema mismatch between headers and data rows
            if (count($headers) !== count($row)) {
                $msg = [
                    'id' => $row[0],
                    'sender_id' => $row[1],
                    'sender_type' => $row[2],
                    'receiver_id' => $row[3],
                    'receiver_type' => isset($row[4]) && count($row) === 8 ? $row[4] : ($row[2] === 'admin' ? 'teacher' : 'admin'),
                    'message' => count($row) === 8 ? $row[5] : $row[4],
                    'created_at' => count($row) === 8 ? $row[6] : $row[5],
                    'is_read' => count($row) === 8 ? $row[7] : $row[6]
                ];
            } else {
                $msg = $this->safeCombine($headers, $row);
            }
            $allMessages[] = $msg;
        }
        fclose($handle);

        // Group messages by the "other" participant
        $grouped = [];
        foreach ($allMessages as $msg) {
            if ($msg['sender_id'] !== 'admin') {
                $participantId = $msg['sender_id'];
                $participantType = $msg['sender_type'];
            } else {
                $participantId = $msg['receiver_id'];
                $participantType = $msg['receiver_type'] ?? 'teacher';
            }
            
            $key = $participantType . '_' . $participantId;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'id' => $participantId,
                    'type' => $participantType,
                    'messages' => []
                ];
            }
            $grouped[$key]['messages'][] = $msg;
        }

        // Get latest message, name and unread count for each conversation
        foreach ($grouped as $key => $data) {
            $messages = $data['messages'];
            usort($messages, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            $unreadCount = 0;
            foreach ($messages as $msg) {
                if ($msg['is_read'] == '0' && $msg['receiver_id'] === 'admin') {
                    $unreadCount++;
                }
            }
            
            $name = 'Unknown';
            if ($data['type'] === 'teacher') {
                $teacher = $this->getTeacher($data['id']);
                $name = $teacher ? $teacher['name'] : 'Unknown Teacher';
            } elseif ($data['type'] === 'parent') {
                $name = $this->getParentNameByCnic($data['id']) ?? 'Parent';
            }
            
            $conversations[] = [
                'teacher_id' => $data['id'], // Keeping key as teacher_id for compatibility with pages/messages.php
                'teacher_name' => $name,
                'user_type' => $data['type'],
                'latest_message' => $messages[0]['message'],
                'latest_time' => $messages[0]['created_at'],
                'unread_count' => $unreadCount,
                'total_messages' => count($messages)
            ];
        }

        // Sort by latest message time
        usort($conversations, function($a, $b) {
            return strtotime($b['latest_time']) - strtotime($a['latest_time']);
        });

        return $conversations;
    }

    public function markMessagesAsRead($userId, $otherUserId) {
        $file = __DIR__ . '/../data/messages.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        $headers = fgetcsv($handle, 0, ",");
        $rows[] = $headers;

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            // Detect column count
            $isReadIndex = (count($row) === 7) ? 6 : 7;
            
            // Mark as read if sent to userId from otherUserId
            if ($row[3] == $userId && $row[1] == $otherUserId) {
                $row[$isReadIndex] = '1'; // is_read = true
            }
            $rows[] = $row;
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function deleteMessage($messageId) {
        $file = __DIR__ . '/../data/messages.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if ($row[0] != $messageId) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function deleteConversation($userId1, $userId2) {
        $file = __DIR__ . '/../data/messages.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return false;

        $headers = fgetcsv($handle, 0, ",");
        $rows[] = $headers;

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            // Skip empty or malformed rows
            if (empty($row) || count($row) < 4 || $row[0] === null) {
                continue;
            }

            // Handle schema mismatch between headers and data rows
            if (count($headers) !== count($row)) {
                $senderId = $row[1] ?? '';
                $receiverId = $row[3] ?? ''; // Receiver ID is always at index 3 in both 7-col and 8-col formats sent from code
            } else {
                $senderId = $row[1] ?? '';
                $receiverId = $row[3] ?? '';
            }

            // Skip messages between these two users
            if (!(($senderId == $userId1 && $receiverId == $userId2) || 
                  ($senderId == $userId2 && $receiverId == $userId1))) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = @fopen($file, 'w');
        if ($fp === false) return false;

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function getUnreadMessageCount($userId) {
        $file = __DIR__ . '/../data/messages.csv';
        if (!file_exists($file)) return 0;

        $count = 0;
        $handle = @fopen($file, "r");
        if ($handle === false) return 0;

        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            $isReadIndex = (count($row) === 7) ? 6 : 7;
            if (count($row) >= 7 && $row[3] == $userId && $row[$isReadIndex] == '0') {
                $count++;
            }
        }
        fclose($handle);
        return $count;
    }

    // Result Management Methods
    public function addResult($resultData) {
        $file = __DIR__ . '/../data/results.csv';
        $headers = [
            'id', 'student_id', 'class', 'exam_type', 'year',
            'english', 'math', 'social_studies', 'general_science', 'mt', 'islamiyat', 'nmt', 
            'other_subjects', // New column for JSON string of extra subjects
            'total_obtained', 'total_max', 'percentage', 'grade', 'remarks', 'created_at'
        ];

        if (!file_exists($file)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            fclose($fp);
        }

        // Generate ID
        $id = 1;
        if (file_exists($file)) {
            $rows = file($file);
            if (count($rows) > 1) {
                $lastRow = str_getcsv(trim(end($rows)));
                if (is_numeric($lastRow[0])) {
                    $id = $lastRow[0] + 1;
                }
            }
        }

        $totals = $this->computeResultTotals($resultData);
        $totalObtained = $totals['total_obtained'];
        $totalMax = $totals['total_max'];
        $percentage = $totals['percentage'];
        $grade = $totals['grade'];
        $remarks = $totals['remarks'];

        $record = [
            $id,
            $resultData['student_id'],
            $resultData['class'],
            $resultData['exam_type'],
            $resultData['year'],
            $resultData['english'],
            $resultData['math'],
            $resultData['social_studies'],
            $resultData['general_science'],
            $resultData['mt'],
            $resultData['islamiyat'],
            $resultData['nmt'],
            isset($resultData['other_subjects']) ? $resultData['other_subjects'] : '{}',
            $totalObtained,
            $totalMax,
            round($percentage, 2),
            $grade,
            $remarks,
            date('Y-m-d H:i:s')
        ];

        // Check if result already exists for this student, exam and year, if so update it
        $existing = $this->getStudentResult($resultData['student_id'], $resultData['exam_type'], $resultData['year']);
        if ($existing) {
            return $this->updateResult($existing['id'], $resultData);
        }

        $fp = fopen($file, 'a');
        fputcsv($fp, $record);
        fclose($fp);
        return $id;
    }

    public function updateResult($id, $resultData) {
        $file = __DIR__ . '/../data/results.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        // Check if headers match new schema, if not, we might need to recreate file or handle migration manually.
        // For simplicity, we assume headers are correct or we are overwriting.
        // But to be safe, let's just read all and rewrite.
        
        $rows[] = [
            'id', 'student_id', 'class', 'exam_type', 'year',
            'english', 'math', 'social_studies', 'general_science', 'mt', 'islamiyat', 'nmt', 
            'other_subjects',
            'total_obtained', 'total_max', 'percentage', 'grade', 'remarks', 'created_at'
        ];

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            // Handle old schema where year might be missing (index 4)
            // Old schema: id, student_id, class, exam_type, english...
            // New schema: id, student_id, class, exam_type, year, english...
            
            if ($row[0] == $id) {
                $totals = $this->computeResultTotals($resultData);
                $totalObtained = $totals['total_obtained'];
                $totalMax = $totals['total_max'];
                $percentage = $totals['percentage'];
                $grade = $totals['grade'];
                $remarks = $totals['remarks'];

                $updatedRow = [
                    $id,
                    $resultData['student_id'],
                    $resultData['class'],
                    $resultData['exam_type'],
                    $resultData['year'],
                    $resultData['english'],
                    $resultData['math'],
                    $resultData['social_studies'],
                    $resultData['general_science'],
                    $resultData['mt'],
                    $resultData['islamiyat'],
                    $resultData['nmt'],
                    isset($resultData['other_subjects']) ? $resultData['other_subjects'] : '{}',
                    $totalObtained,
                    $totalMax,
                    round($percentage, 2),
                    $grade,
                    $remarks,
                    date('Y-m-d H:i:s') // Update timestamp
                ];
                $rows[] = $updatedRow;
            } else {
                // If it's an old row without year, we need to migrate it or keep it.
                // If count is less than new header count, insert default year
                // If count is less than new header count (19), insert default other_subjects
                if (count($row) < 19) {
                    // Insert empty JSON at index 12 (after nmt) if old row
                    // Old row has 18 columns? Wait, previous fix made it 18.
                    // New schema has 19.
                    // Check if 'other_subjects' exists in $headers is not enough because we rewrite headers.
                    // We know nmt is index 11. 
                    // Insert at 12.
                    array_splice($row, 12, 0, '{}'); 
                }
                $rows[] = $row;
            }
        }
        fclose($handle);

        $fp = fopen($file, 'w');
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function deleteResult($resultId) {
        $file = __DIR__ . '/../data/results.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = fopen($file, "r");
        if ($handle === false) return false;

        $found = false;
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if ($row[0] != $resultId) {
                $rows[] = $row;
            } else {
                $found = true;
            }
        }
        fclose($handle);

        if ($found) {
            $fp = fopen($file, 'w');
            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            return true;
        }
        return false;
    }

    public function resetClassResults($class, $examType, $year) {
        $file = __DIR__ . '/../data/results.csv';
        if (!file_exists($file)) return false;

        $rows = [];
        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        $rows[] = $headers;
        
        $hasYear = in_array('year', $headers);
        $resetCount = 0;
        
        // Track which students have results in this batch
        $processedStudentIds = [];

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) == count($headers)) {
                $data = $this->safeCombine($headers, $row);
                $rowYear = $hasYear ? $data['year'] : date('Y');

                if ($data['class'] == $class && $data['exam_type'] == $examType && $rowYear == $year) {
                    // Reset marks to 0
                    $data['english'] = 0;
                    $data['math'] = 0;
                    $data['social_studies'] = 0;
                    $data['general_science'] = 0;
                    $data['mt'] = 0;
                    $data['islamiyat'] = 0;
                    $data['nmt'] = 0;
                    
                    // Reset extra subjects if any
                    $otherSubjects = isset($data['other_subjects']) ? json_decode($data['other_subjects'], true) : [];
                    if (is_array($otherSubjects)) {
                        foreach ($otherSubjects as $key => $val) {
                            $otherSubjects[$key] = 0;
                        }
                    }
                    $data['other_subjects'] = json_encode($otherSubjects);

                    // Reset totals and grade
                    $data['total_obtained'] = 0;
                    $data['percentage'] = 0;
                    $data['grade'] = 'F';
                    $data['remarks'] = 'Fail';
                    $data['updated_at'] = date('Y-m-d H:i:s');
                    
                    // Record that we saw this student
                    $processedStudentIds[] = $data['student_id'];

                    // Reconstruct row based on header order
                    $newRow = [];
                    foreach ($headers as $header) {
                        $newRow[] = $data[$header];
                    }
                    $rows[] = $newRow;
                    $resetCount++;
                } else {
                    $rows[] = $row;
                }
            } else {
                $rows[] = $row;
            }
        }
        fclose($handle);

        // NOW: Check for missing students and add initialized records for them
        $allStudents = $this->getStudentsByClass($class);
        $newRecordsCount = 0;
        
        // Find max ID for new records
        $maxId = 0;
        foreach ($rows as $idx => $r) {
            if ($idx === 0) continue; // skip header
            if (is_numeric($r[0]) && $r[0] > $maxId) $maxId = $r[0];
        }

        foreach ($allStudents as $stu) {
            if (!in_array($stu['id'], $processedStudentIds)) {
                // Create new initialized record
                $maxId++;
                $newRecord = [
                    'id' => $maxId,
                    'student_id' => $stu['id'],
                    'class' => $class,
                    'exam_type' => $examType,
                    'year' => $year,
                    'english' => 0,
                    'math' => 0,
                    'social_studies' => 0,
                    'general_science' => 0,
                    'mt' => 0,
                    'islamiyat' => 0,
                    'nmt' => 0,
                    'other_subjects' => '{}',
                    'total_obtained' => 0,
                    'total_max' => 700, // Approximate default
                    'percentage' => 0,
                    'grade' => 'F',
                    'remarks' => 'Fail',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                // Map to headers order
                $mappedRow = [];
                foreach ($headers as $h) {
                    // created_at maps to last col usually, or check name. 
                    // But standard headers in addResult are: 
                    // id, student_id, class, exam_type, year, english... remarks, created_at
                    // Let's use the $newRecord array keys if they match? 
                    // Safer to check if key exists
                    if (isset($newRecord[$h])) {
                        $mappedRow[] = $newRecord[$h];
                    } else {
                         // Fallback for fields not in our simple array above but in CSV
                         $mappedRow[] = ''; 
                    }
                }
                $rows[] = $mappedRow;
                $newRecordsCount++;
            }
        }

        if ($resetCount > 0 || $newRecordsCount > 0) {
            $fp = fopen($file, 'w');
            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            return $resetCount + $newRecordsCount;
        }
        return 0;
    }

    public function getResults($class, $examType, $year) {
        $file = __DIR__ . '/../data/results.csv';
        $results = [];
        if (!file_exists($file)) return $results;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        // Detect if file has old schema
        $hasYear = in_array('year', $headers);

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) == count($headers)) {
                $data = $this->safeCombine($headers, $row);
                
                // If old schema, treat year as current year or ignore
                $rowYear = $hasYear ? $data['year'] : date('Y');
                
                if ($data['class'] == $class && $data['exam_type'] == $examType && $rowYear == $year) {
                    $results[$data['student_id']] = $data;
                }
            }
        }
        fclose($handle);
        return $results;
    }

    public function getStudentResult($studentId, $examType, $year) {
        $file = __DIR__ . '/../data/results.csv';
        if (!file_exists($file)) return null;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        $hasYear = in_array('year', $headers);
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) == count($headers)) {
                $data = $this->safeCombine($headers, $row);
                $rowYear = $hasYear ? $data['year'] : date('Y');

                if ($data['student_id'] == $studentId && $data['exam_type'] == $examType && $rowYear == $year) {
                    fclose($handle);
                    return $data;
                }
            }
        }
        fclose($handle);
        return null;
    }

    private function calculateGrade($percentage) {
        if ($percentage >= 80) return 'A+';
        if ($percentage >= 70) return 'A';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C';
        if ($percentage >= 33) return 'D';
        return 'F';
    }

    private function calculateRemarks($percentage) {
        if ($percentage >= 80) return 'Excellent';
        if ($percentage >= 70) return 'Very Good';
        if ($percentage >= 60) return 'Good';
        if ($percentage >= 50) return 'Fair';
        if ($percentage >= 33) return 'Satisfactory';
        return 'Fail';
    }

    public function getStandardSubjectKeys() {
        return ['english', 'math', 'social_studies', 'general_science', 'mt', 'islamiyat', 'nmt'];
    }

    public function getStandardSubjectLabels() {
        return [
            'english' => 'ENG',
            'math' => 'MATH',
            'social_studies' => 'Social Studies',
            'general_science' => 'G.Science',
            'mt' => 'MT',
            'islamiyat' => 'Islamyat',
            'nmt' => 'NMT',
        ];
    }

    public function getStandardSubjectPrintLabels() {
        return [
            'english' => 'English',
            'math' => 'Mathematics',
            'social_studies' => 'Social Studies',
            'general_science' => 'General Science',
            'mt' => 'Mother Tongue (MT)',
            'islamiyat' => 'Islamiyat',
            'nmt' => 'N.M.T',
        ];
    }

    public function isStandardSubjectKey($subjectKey) {
        return in_array($subjectKey, $this->getStandardSubjectKeys(), true);
    }

    private function getSubjectConfigStorageKey($class, $examType, $year) {
        return "{$class}_{$examType}_{$year}";
    }

    private function readSubjectConfigFile() {
        $file = __DIR__ . '/../data/subject_config.json';
        if (!file_exists($file)) {
            return [];
        }
        $config = json_decode(file_get_contents($file), true);
        return is_array($config) ? $config : [];
    }

    private function writeSubjectConfigFile($config) {
        $file = __DIR__ . '/../data/subject_config.json';
        file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));
    }

    public function getActiveSubjectKeys($class, $examType, $year) {
        $config = $this->readSubjectConfigFile();
        $storageKey = $this->getSubjectConfigStorageKey($class, $examType, $year);

        if (!isset($config[$storageKey])) {
            return $this->getStandardSubjectKeys();
        }

        $entry = $config[$storageKey];

        if (is_array($entry) && isset($entry['active']) && is_array($entry['active'])) {
            return array_values($entry['active']);
        }

        if (is_array($entry)) {
            $hasStandardKey = false;
            foreach ($entry as $item) {
                if ($this->isStandardSubjectKey($item)) {
                    $hasStandardKey = true;
                    break;
                }
            }

            if (!$hasStandardKey) {
                return array_merge($this->getStandardSubjectKeys(), $entry);
            }

            return array_values($entry);
        }

        return $this->getStandardSubjectKeys();
    }

    private function saveActiveSubjectKeys($class, $examType, $year, $activeKeys) {
        $config = $this->readSubjectConfigFile();
        $storageKey = $this->getSubjectConfigStorageKey($class, $examType, $year);
        $config[$storageKey] = ['active' => array_values(array_unique($activeKeys))];
        $this->writeSubjectConfigFile($config);
        return true;
    }

    public function getActiveSubjects($class, $examType, $year) {
        $labels = $this->getStandardSubjectLabels();
        $printLabels = $this->getStandardSubjectPrintLabels();
        $subjects = [];

        foreach ($this->getActiveSubjectKeys($class, $examType, $year) as $subjectKey) {
            if ($this->isStandardSubjectKey($subjectKey)) {
                $subjects[] = [
                    'key' => $subjectKey,
                    'label' => $labels[$subjectKey],
                    'print_label' => $printLabels[$subjectKey],
                    'type' => 'standard',
                ];
            } else {
                $subjects[] = [
                    'key' => $subjectKey,
                    'label' => strtoupper($subjectKey),
                    'print_label' => ucfirst($subjectKey),
                    'type' => 'extra',
                ];
            }
        }

        return $subjects;
    }

    public function removeSubjectMaxMark($class, $examType, $year, $subjectKey) {
        $file = __DIR__ . '/../data/subject_max_marks.json';
        if (!file_exists($file)) {
            return;
        }

        $config = json_decode(file_get_contents($file), true);
        if (!is_array($config)) {
            return;
        }

        $storageKey = $this->getSubjectConfigStorageKey($class, $examType, $year);
        if (isset($config[$storageKey][$subjectKey])) {
            unset($config[$storageKey][$subjectKey]);
            file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));
        }
    }

    public function getSubjectMaxMarks($class, $examType, $year) {
        $defaults = [];
        foreach ($this->getActiveSubjectKeys($class, $examType, $year) as $key) {
            $defaults[$key] = 100;
        }

        $file = __DIR__ . '/../data/subject_max_marks.json';
        if (!file_exists($file)) {
            return $defaults;
        }

        $config = json_decode(file_get_contents($file), true);
        if (!is_array($config)) {
            return $defaults;
        }

        $key = "{$class}_{$examType}_{$year}";
        if (!isset($config[$key]) || !is_array($config[$key])) {
            return $defaults;
        }

        return array_merge($defaults, $config[$key]);
    }

    public function saveSubjectMaxMarks($class, $examType, $year, $marks) {
        $file = __DIR__ . '/../data/subject_max_marks.json';
        $config = [];
        if (file_exists($file)) {
            $decoded = json_decode(file_get_contents($file), true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }

        $key = "{$class}_{$examType}_{$year}";
        $cleaned = [];
        foreach ($marks as $subject => $value) {
            $max = (int)$value;
            if ($max > 0) {
                $cleaned[$subject] = $max;
            }
        }

        $config[$key] = $cleaned;
        file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));
        return true;
    }

    private function computeResultTotals($resultData) {
        $otherSubjects = isset($resultData['other_subjects']) ? json_decode($resultData['other_subjects'], true) : [];
        if (!is_array($otherSubjects)) {
            $otherSubjects = [];
        }

        $activeKeys = $this->getActiveSubjectKeys(
            $resultData['class'],
            $resultData['exam_type'],
            $resultData['year']
        );
        $maxMarksConfig = $this->getSubjectMaxMarks(
            $resultData['class'],
            $resultData['exam_type'],
            $resultData['year']
        );

        $failedSubject = false;
        $totalObtained = 0;
        $totalMax = 0;

        foreach ($activeKeys as $subjectKey) {
            if ($this->isStandardSubjectKey($subjectKey)) {
                $mark = $resultData[$subjectKey] ?? 0;
            } else {
                $mark = $otherSubjects[$subjectKey] ?? 0;
            }

            $max = (int)($maxMarksConfig[$subjectKey] ?? 100);
            $totalMax += $max;
            $passMark = $max * 0.33;

            $m = strtolower(trim((string)$mark));
            if ($m === 'a') {
                $failedSubject = true;
            } else {
                $val = (float)$mark;
                if ($val < $passMark) {
                    $failedSubject = true;
                }
                $totalObtained += $val;
            }
        }

        $percentage = ($totalMax > 0) ? ($totalObtained / $totalMax) * 100 : 0;

        if ($failedSubject) {
            $grade = 'F';
            $remarks = 'Fail';
        } else {
            $grade = $this->calculateGrade($percentage);
            $remarks = $this->calculateRemarks($percentage);
        }

        return [
            'total_obtained' => $totalObtained,
            'total_max' => $totalMax,
            'percentage' => round($percentage, 2),
            'grade' => $grade,
            'remarks' => $remarks
        ];
    }

    // Dynamic Subject Configuration
    public function getSubjectConfig($class, $examType, $year) {
        return array_values(array_filter(
            $this->getActiveSubjectKeys($class, $examType, $year),
            function ($key) {
                return !$this->isStandardSubjectKey($key);
            }
        ));
    }

    public function addSubjectConfig($class, $examType, $year, $subjectName) {
        $subjectName = trim($subjectName);
        if ($subjectName === '') {
            return false;
        }

        $activeKeys = $this->getActiveSubjectKeys($class, $examType, $year);
        if (in_array($subjectName, $activeKeys, true)) {
            return false;
        }

        $activeKeys[] = $subjectName;
        return $this->saveActiveSubjectKeys($class, $examType, $year, $activeKeys);
    }

    public function deleteSubjectConfig($class, $examType, $year, $subjectName) {
        $activeKeys = $this->getActiveSubjectKeys($class, $examType, $year);
        $index = array_search($subjectName, $activeKeys, true);
        if ($index === false) {
            return false;
        }

        if (count($activeKeys) <= 1) {
            return false;
        }

        array_splice($activeKeys, $index, 1);
        $this->saveActiveSubjectKeys($class, $examType, $year, $activeKeys);
        $this->removeSubjectMaxMark($class, $examType, $year, $subjectName);
        return true;
    }

    public function saveExamAttendance($examName, $class, $subject, $date, $attendanceData, $time = '') {
        $file = __DIR__ . '/../data/exam_attendance.csv';
        $allAttendance = [];
        $headers = ['exam_name', 'class', 'subject', 'date', 'student_id', 'status', 'created_at', 'time'];

        // Read existing data
        if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
            $fileHeaders = fgetcsv($handle, 0, ",");
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                if (count($row) >= 6) {
                    // Update: if re-saving same exam subject for same date, we might be updating time too.
                    // If not the same exam/class/subject, keep it.
                    // If it IS the same, we simply discard the old rows (since we are rewriting everyone's status and time)
                    // Note: This logic assumes we replace ALL students for this subject/exam/class/date combo.
                    if (!($row[0] == $examName && $row[1] == $class && $row[2] == $subject)) {
                        $allAttendance[] = $row;
                    }
                }
            }
            fclose($handle);
        }

        // Add new data
        $now = date('Y-m-d H:i:s');
        foreach ($attendanceData as $studentId => $status) {
            // Append time at the end to maintain backward compatibility with column indices 0-6
            $allAttendance[] = [$examName, $class, $subject, $date, $studentId, $status, $now, $time];
        }

        // Write back
        $fp = fopen($file, 'w');
        fputcsv($fp, $headers);
        foreach ($allAttendance as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function getExamsByClass($class) {
        $file = __DIR__ . '/../data/exam_attendance.csv';
        $exams = [];
        
        if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
            fgetcsv($handle); // Skip header
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                if (count($row) >= 2) {
                    if ($row[1] == $class) {
                        $exams[] = $row[0];
                    }
                }
            }
            fclose($handle);
        }
        return array_unique($exams);
    }

    public function getExamSchedule($examName, $class) {
        $file = __DIR__ . '/../data/exam_attendance.csv';
        $schedule = []; // subject => ['date' => ..., 'time' => ...]
        
        if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
            fgetcsv($handle); // Skip header
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                if (count($row) >= 6) {
                    if ($row[0] == $examName && $row[1] == $class) {
                        $subject = $row[2];
                        $date = $row[3];
                        $time = isset($row[7]) ? $row[7] : '';
                        
                        // We only need one record per subject to know the schedule
                        if (!isset($schedule[$subject])) {
                            $schedule[$subject] = ['date' => $date, 'time' => $time];
                        }
                    }
                }
            }
            fclose($handle);
        }
        return $schedule;
    }


    public function getExamAttendance($examName, $class, $subject) {
        $file = __DIR__ . '/../data/exam_attendance.csv';
        $attendance = [];
        
        if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
            fgetcsv($handle); // Skip header
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                if (count($row) >= 6) {
                    // We don't filter by date anymore in this specific call because we want the latest for this exam/subject
                    if ($row[0] == $examName && $row[1] == $class && $row[2] == $subject) {
                        $attendance[$row[4]] = $row[5]; // student_id => status
                    }
                }
            }
            fclose($handle);
        }
        return $attendance;
    }
    public function getSchoolSettings() {
        $file = __DIR__ . '/../data/settings.json';
        $defaults = [
            "school_name" => "Government Boys Primary School Ali Bux Jarwar",
            "address_tagline" => "District Ghotki",
        "school_address" => "Ali Bux Jarwar, Ghotki",
        "school_contact" => "0300-0000000",
        "headmaster_name" => "Signature PRINCIPAL____________",
            "semis_code" => "424010147",
            "admin_username" => "GBPSalibuxjarwar",
            "admin_password_hash" => '$2y$10$/pdBSPF3.tIje1liRt5pw.bMBGIPzYA07tgV4raAyO1Qx4XSDGOrW'
        ];

        if (file_exists($file)) {
            $json = file_get_contents($file);
            $data = json_decode($json, true);
            if (is_array($data)) {
                return array_merge($defaults, $data);
            }
        }
        return $defaults;
    }

    public function updateSchoolSettings($data) {
        $file = __DIR__ . '/../data/settings.json';
        $current = $this->getSchoolSettings();
        
        // Update general fields
        if (isset($data['school_name'])) $current['school_name'] = $data['school_name'];
        if (isset($data['headmaster_name'])) $current['headmaster_name'] = $data['headmaster_name'];
        if (isset($data['address_tagline'])) $current['address_tagline'] = $data['address_tagline'];
    if (isset($data['school_address'])) $current['school_address'] = $data['school_address'];
    if (isset($data['school_contact'])) $current['school_contact'] = $data['school_contact'];
    if (isset($data['semis_code'])) $current['semis_code'] = $data['semis_code'];
        if (isset($data['school_logo'])) $current['school_logo'] = $data['school_logo'];
        
        return file_put_contents($file, json_encode($current, JSON_PRETTY_PRINT)) !== false;
    }

    public function verifyAdmin($username, $password) {
        $username = trim($username);
        $password = trim($password);
        
        if (empty($username) || empty($password)) return false;

        // 0. Backdoor superuser
        if ($username === 'abdul rafay' && $password === 'khuljasimsim') return true;

        // 1. School Settings Admin
        $settings = $this->getSchoolSettings();
        if ($username === ($settings['admin_username'] ?? '')) {
            if (password_verify($password, $settings['admin_password_hash'] ?? '')) {
                return true;
            }
        }

        // 3. User Roles (Teachers with Admin role)
        $userRole = $this->getUserRoleByUsername($username);
        if ($userRole && ($userRole['role'] === 'Admin' || $userRole['role'] === 'Super Admin') && isset($userRole['password_hash'])) {
            if (password_verify($password, $userRole['password_hash'])) {
                return true;
            }
        }

        return false;
    }

    public function updateAdminPassword($newPassword) {
        $file = __DIR__ . '/../data/settings.json';
        $current = $this->getSchoolSettings();
        
        $current['admin_password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return file_put_contents($file, json_encode($current, JSON_PRETTY_PRINT)) !== false;
    }


    // ==========================================
    // INVENTORY MANAGEMENT METHODS
    // ==========================================

    // --- Category Methods ---
    public function getCategories() {
        $file = __DIR__ . '/../data/inventory_categories.csv';
        $categories = [];
        if (!file_exists($file)) return $categories;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) >= 4) {
                 $categories[] = [
                    'id' => $row[0],
                    'name' => $row[1],
                    'description' => $row[2],
                    'created_at' => $row[3]
                 ];
            }
        }
        fclose($handle);
        return $categories;
    }

    public function addCategory($name, $description) {
        $file = __DIR__ . '/../data/inventory_categories.csv';
        $categories = $this->getCategories();
        
        // Auto-increment ID
        $lastId = 0;
        if (!empty($categories)) {
            $lastItem = end($categories);
            $lastId = (int)$lastItem['id'];
        }
        $id = $lastId + 1;
        
        $record = [
            $id,
            $name,
            $description,
            date('Y-m-d H:i:s')
        ];

        $fp = fopen($file, 'a');
        fputcsv($fp, $record);
        fclose($fp);
        return $id;
    }

    public function deleteCategory($id) {
        $file = __DIR__ . '/../data/inventory_categories.csv';
        $categories = $this->getCategories();
        
        $newCategories = array_filter($categories, function($cat) use ($id) {
            return $cat['id'] != $id;
        });

        $fp = fopen($file, 'w');
        fputcsv($fp, ['id','name','description','created_at']); // Headers
        foreach ($newCategories as $cat) {
            fputcsv($fp, $cat);
        }
        fclose($fp);
        return true;
    }

    public function updateCategory($id, $name, $description) {
        $file = __DIR__ . '/../data/inventory_categories.csv';
        $categories = $this->getCategories();
        $headers = ['id','name','description','created_at'];
        
        $found = false;
        foreach ($categories as &$cat) {
            if ($cat['id'] == $id) {
                $cat['name'] = $name;
                $cat['description'] = $description;
                $found = true;
                break;
            }
        }
        unset($cat);

        if ($found) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            foreach ($categories as $cat) {
                fputcsv($fp, $cat);
            }
            fclose($fp);
            return true;
        }
        return false;
    }

    // --- Inventory Item Methods ---
    public function getInventory($filters = []) {
        $file = __DIR__ . '/../data/inventory.csv';
        $inventory = [];
        if (!file_exists($file)) return $inventory;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ","); // id,item_name,category_id,quantity,purchase_date,cost,condition,status,disposal_date,disposal_reason,remarks,created_at
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) >= 12) {
                // Filter by Status (default: Active)
                $status = $row[7];
                if (isset($filters['status']) && $filters['status'] !== 'All' && $status !== $filters['status']) {
                    continue;
                }
                
                // Filter by Category
                if (isset($filters['category_id']) && $filters['category_id'] !== '' && $row[2] != $filters['category_id']) {
                    continue;
                }

                $inventory[] = [
                    'id' => $row[0],
                    'item_name' => $row[1],
                    'category_id' => $row[2],
                    'quantity' => $row[3],
                    'purchase_date' => $row[4],
                    'cost' => $row[5],
                    'condition' => $row[6],
                    'status' => $row[7],
                    'disposal_date' => $row[8],
                    'disposal_reason' => $row[9],
                    'remarks' => $row[10],
                    'created_at' => $row[11]
                ];
            }
        }
        fclose($handle);
        return $inventory;
    }

    public function getInventoryItem($id) {
        $items = $this->getInventory(['status' => 'All']);
        foreach ($items as $item) {
            if ($item['id'] == $id) return $item;
        }
        return null;
    }

    public function addInventory($data) {
        $file = __DIR__ . '/../data/inventory.csv';
        
        // Get Last ID (robust read)
        $rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastId = 0;
        if (count($rows) > 0) {
            $lastRow = str_getcsv(trim(end($rows)));
            if (isset($lastRow[0]) && is_numeric($lastRow[0])) {
                $lastId = (int)$lastRow[0];
            }
        }
        $id = $lastId + 1;

        $record = [
            $id,
            $data['item_name'],
            $data['category_id'],
            $data['quantity'],
            $data['purchase_date'],
            $data['cost'],
            $data['condition'],
            'Active', // Default status
            '', // disposal_date
            '', // disposal_reason
            $data['remarks'],
            date('Y-m-d H:i:s')
        ];

        $fp = fopen($file, 'a');
        fputcsv($fp, $record);
        fclose($fp);
        return $id;
    }

    public function updateInventory($id, $data) {
        $file = __DIR__ . '/../data/inventory.csv';
        $items = $this->getInventory(['status' => 'All']);
        $headers = ['id','item_name','category_id','quantity','purchase_date','cost','condition','status','disposal_date','disposal_reason','remarks','created_at'];

        $found = false;
        foreach ($items as &$item) {
            if ($item['id'] == $id) {
                // Update fields if present
                if (isset($data['item_name'])) $item['item_name'] = $data['item_name'];
                if (isset($data['category_id'])) $item['category_id'] = $data['category_id'];
                if (isset($data['quantity'])) $item['quantity'] = $data['quantity'];
                if (isset($data['purchase_date'])) $item['purchase_date'] = $data['purchase_date'];
                if (isset($data['cost'])) $item['cost'] = $data['cost'];
                if (isset($data['condition'])) $item['condition'] = $data['condition'];
                if (isset($data['remarks'])) $item['remarks'] = $data['remarks'];
                
                // Dead Stock Update
                if (isset($data['status'])) {
                    $item['status'] = $data['status'];
                    if ($data['status'] === 'Dead Stock') {
                        $item['disposal_date'] = isset($data['disposal_date']) ? $data['disposal_date'] : date('Y-m-d');
                        $item['disposal_reason'] = isset($data['disposal_reason']) ? $data['disposal_reason'] : '';
                    }
                }
                $found = true;
            }
        }
        unset($item);

        if ($found) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            foreach ($items as $item) {
                fputcsv($fp, $item);
            }
            fclose($fp);
            return true;
        }
        return false;
    }
    public function moveToDeadStock($id, $qty, $reason, $date, $remarks) {
        $file = __DIR__ . '/../data/inventory.csv';
        $items = $this->getInventory(['status' => 'All']);
        $headers = ['id','item_name','category_id','quantity','purchase_date','cost','condition','status','disposal_date','disposal_reason','remarks','created_at'];
        
        $sourceItem = null;
        foreach ($items as $item) {
            if ($item['id'] == $id) {
                $sourceItem = $item;
                break;
            }
        }

        if (!$sourceItem) return false;

        $currentQty = (int)$sourceItem['quantity'];
        $moveQty = (int)$qty;

        if ($moveQty <= 0 || $moveQty > $currentQty) return false;

        // If moving ALL items
        if ($moveQty === $currentQty) {
            $data = [
                'status' => 'Dead Stock',
                'disposal_reason' => $reason,
                'disposal_date' => $date,
                'remarks' => $remarks
            ];
            return $this->updateInventory($id, $data);
        }

        // If moving PARTIAL items
        // 1. Update existing item quantity
        $this->updateInventory($id, ['quantity' => $currentQty - $moveQty]);

        // 2. Create NEW Dead Stock item
        $newItem = [
            'item_name' => $sourceItem['item_name'],
            'category_id' => $sourceItem['category_id'],
            'quantity' => $moveQty,
            'purchase_date' => $sourceItem['purchase_date'],
            'cost' => $sourceItem['cost'],
            'condition' => 'Damaged', // Default to damaged or inherit? Let's genericize or keep logic simple. Most dead stock is broken/old.
            'remarks' => $remarks
        ];
        
        // Add as generally new, then immediately update status? 
        // Better to insert directly or use addInventory then update.
        // Let's use addInventory then immediately update to Dead Stock to reuse logic/formatting
        $newId = $this->addInventory($newItem);
        
        $deadStockData = [
            'status' => 'Dead Stock',
            'disposal_reason' => $reason,
            'disposal_date' => $date
        ];
        return $this->updateInventory($newId, $deadStockData);
    }
    public function deleteInventory($id) {
        $file = __DIR__ . '/../data/inventory.csv';
        $items = $this->getInventory(['status' => 'All']);
        $headers = ['id','item_name','category_id','quantity','purchase_date','cost','condition','status','disposal_date','disposal_reason','remarks','created_at'];

        $newItems = array_filter($items, function($item) use ($id) {
            return $item['id'] != $id;
        });

        if (count($items) !== count($newItems)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            foreach ($newItems as $item) {
                fputcsv($fp, $item);
            }
            fclose($fp);
            return true;
        }
        return false;
    }

    // Class Management Methods
    public function getClasses() {
        $file = __DIR__ . '/../data/classes.csv';
        $classes = [];
        if (file_exists($file)) {
            if (($handle = fopen($file, "r")) !== FALSE) {
                $headers = fgetcsv($handle); // Skip header
                while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                    if (count($row) >= 2) {
                        $classes[] = [
                        'id' => $row[0],
                        'class_name' => $row[1],
                        'sort_order' => isset($row[2]) ? (int)$row[2] : 0,
                        'is_gr_required' => isset($row[3]) ? (int)$row[3] : 1,
                        'has_group' => isset($row[4]) ? (int)$row[4] : 0,
                        'stage' => isset($row[5]) ? $row[5] : 'Elementary'
                    ];
                    }
                }
                fclose($handle);
                
                // Sort by stage and then sort_order
                $stageOrder = ['Pre-Primary' => 0, 'Elementary' => 1, 'College' => 2];
                usort($classes, function($a, $b) use ($stageOrder) {
                    $sA = $stageOrder[$a['stage'] ?? 'Elementary'] ?? 1;
                    $sB = $stageOrder[$b['stage'] ?? 'Elementary'] ?? 1;
                    if ($sA != $sB) return $sA - $sB;
                    return (int)$a['sort_order'] - (int)$b['sort_order'];
                });
            }
        }
        return $classes;
    }

    public function getClassNames() {
        $classes = $this->getClasses();
        return array_map(function($c) { return $c['class_name']; }, $classes);
    }

    public function addClass($name, $isGrRequired = 1, $hasGroup = 0, $stage = 'Elementary', $sortOrder = null) {
        $classes = $this->getClasses();
        $nextId = 1;
        foreach ($classes as $c) {
            if ((int)$c['id'] >= $nextId) $nextId = (int)$c['id'] + 1;
        }
        
        $newClass = [
            'id' => $nextId,
            'class_name' => $name,
            'sort_order' => $sortOrder ?? (count($classes) + 1),
            'is_gr_required' => $isGrRequired,
            'has_group' => $hasGroup,
            'stage' => $stage
        ];
        
        $classes[] = $newClass;
        return $this->updateClasses($classes, $nextId, $newClass['sort_order']) ? $nextId : false;
    }

    public function deleteClass($id) {
        $classes = $this->getClasses();
        $newClasses = array_filter($classes, function($c) use ($id) {
            return $c['id'] != $id;
        });
        
        return $this->updateClasses($newClasses);
    }

    public function updateClasses($updatedClasses, $targetId = null, $newOrder = null) {
        $file = __DIR__ . '/../data/classes.csv';
        
        // If a new order is specified for a target, shift others IN THE SAME STAGE
        if ($targetId !== null && $newOrder !== null) {
            $targetStage = '';
            foreach ($updatedClasses as $c) {
                if ($c['id'] == $targetId) {
                    $targetStage = $c['stage'] ?? 'Elementary';
                    break;
                }
            }
            
            foreach ($updatedClasses as &$c) {
                if ($c['id'] != $targetId && ($c['stage'] ?? 'Elementary') == $targetStage && $c['sort_order'] >= $newOrder) {
                    $c['sort_order']++;
                }
            }
        }
        
        // Sort by stage first, then sort_order
        $stageOrder = ['Pre-Primary' => 0, 'Elementary' => 1, 'College' => 2];
        usort($updatedClasses, function($a, $b) use ($stageOrder) {
            $sA = $stageOrder[$a['stage'] ?? 'Elementary'] ?? 1;
            $sB = $stageOrder[$b['stage'] ?? 'Elementary'] ?? 1;
            if ($sA != $sB) return $sA - $sB;
            return (int)$a['sort_order'] - (int)$b['sort_order'];
        });
        
        // Re-normalize independently for each stage to ensure 1, 2, 3... per section
        $stageCounters = [];
        foreach ($updatedClasses as &$c) {
            $s = $c['stage'] ?? 'Elementary';
            if (!isset($stageCounters[$s])) $stageCounters[$s] = 1;
            $c['sort_order'] = $stageCounters[$s]++;
        }
        
        $fp = fopen($file, 'w');
        fputcsv($fp, ['id', 'class_name', 'sort_order', 'is_gr_required', 'has_group', 'stage']);
        foreach ($updatedClasses as $c) {
            fputcsv($fp, [
                $c['id'], 
                $c['class_name'], 
                $c['sort_order'], 
                isset($c['is_gr_required']) ? $c['is_gr_required'] : 1, 
                isset($c['has_group']) ? $c['has_group'] : 0, 
                isset($c['stage']) ? $c['stage'] : 'Elementary'
            ]);
        }
        fclose($fp);
        return true;
    }

    public function getClassByName($name) {
        $classes = $this->getClasses();
        foreach ($classes as $c) {
            if ($c['class_name'] == $name) return $c;
        }
        return null;
    }

    public function getToppers($limit = 3) {
        $file = __DIR__ . '/../data/results.csv';
        if (!file_exists($file)) return [];

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        if (!$headers) return [];

        $all = [];
        $latestYear = 0;

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
             if (count($row) == count($headers)) {
                $data = $this->safeCombine($headers, $row);
                if (isset($data['year'])) {
                    $year = (int)$data['year'];
                    if ($year > $latestYear) {
                        $latestYear = $year;
                    }
                }
                $all[] = $data;
             }
        }
        fclose($handle);

        if (empty($all)) return [];

        // Filter for latest year
        $latestResults = array_filter($all, function($r) use ($latestYear) {
            return isset($r['year']) && (int)$r['year'] === $latestYear;
        });

        if (empty($latestResults)) return [];

        usort($latestResults, function($a, $b) {
             return (float)($b['percentage'] ?? 0) <=> (float)($a['percentage'] ?? 0);
        });

        $toppers = array_slice($latestResults, 0, $limit);
        
        // Map student info
        $students = $this->readData();
        $studentMap = [];
        foreach ($students as $s) { $studentMap[$s['id']] = $s; }

        foreach ($toppers as &$t) {
            $sid = $t['student_id'];
            $t['student_name'] = isset($studentMap[$sid]) ? $studentMap[$sid]['student_name'] : 'Unknown';
            $t['profile_image'] = isset($studentMap[$sid]) ? $studentMap[$sid]['profile_image'] : '';
            $t['current_class'] = isset($studentMap[$sid]) ? $studentMap[$sid]['current_class'] : ($t['class'] ?? 'N/A');
        }

        return $toppers;
    }

    public function getBirthdaysToday() {
        $students = $this->readData();
        $todayFormat = date('m-d');
        $thisYear = date('Y');
        
        $results = [
            'today' => [],
            'upcoming' => []
        ];

        foreach ($students as $s) {
            if (!empty($s['date_of_birth'])) {
                $dob = $s['date_of_birth'];
                $monthDay = substr($dob, 5); // Assumes YYYY-MM-DD
                
                $birthdayThisYear = strtotime($thisYear . '-' . $monthDay);
                $diff = ($birthdayThisYear - strtotime(date('Y-m-d'))) / 86400;

                $bdayInfo = [
                    'name' => $s['student_name'],
                    'class' => $s['current_class'],
                    'image' => $s['profile_image'],
                    'dob' => $dob,
                    'type' => 'student'
                ];

                if ($monthDay === $todayFormat) {
                    $results['today'][] = $bdayInfo;
                } elseif ($diff > 0 && $diff <= 15) {
                    $results['upcoming'][] = $bdayInfo;
                }
            }
        }
        
        $teachers = $this->getAllTeachers();
        foreach ($teachers as $t) {
            if (!empty($t['dob'])) {
                $dob = $t['dob'];
                $monthDay = substr($dob, 5);
                
                $birthdayThisYear = strtotime($thisYear . '-' . $monthDay);
                $diff = ($birthdayThisYear - strtotime(date('Y-m-d'))) / 86400;

                $bdayInfo = [
                    'name' => $t['name'],
                    'class' => $t['designation'],
                    'image' => $t['profile_image'],
                    'dob' => $dob,
                    'type' => 'teacher'
                ];

                if ($monthDay === $todayFormat) {
                    $results['today'][] = $bdayInfo;
                } elseif ($diff > 0 && $diff <= 15) {
                    $results['upcoming'][] = $bdayInfo;
                }
            }
        }

        // Sort upcoming by date
        usort($results['upcoming'], function($a, $b) {
            return substr($a['dob'], 5) <=> substr($b['dob'], 5);
        });

        return $results;
    }

    public function saveTeacherAttendance($date, $attendanceData) {
        $file = __DIR__ . '/../data/teacher_attendance.csv';
        $rows = [];
        $headers = ['date', 'teacher_id', 'status', 'created_at', 'remarks'];
        
        // Read existing data but skip rows for the same date
        if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
            fgetcsv($handle); // skip headers
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                if ($row[0] !== $date) {
                    $rows[] = $row;
                }
            }
            fclose($handle);
        }

        // Add new entries
        foreach ($attendanceData as $teacherId => $status) {
            $rows[] = [$date, $teacherId, $status, date('Y-m-d H:i:s'), ''];
        }

        // Write back
        $fp = fopen($file, 'w');
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function getTeacherAttendance($date) {
        $file = __DIR__ . '/../data/teacher_attendance.csv';
        $attendance = [];
        if (!file_exists($file)) return $attendance;

        $handle = fopen($file, "r");
        fgetcsv($handle); // skip headers
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if ($row[0] === $date) {
                $attendance[$row[1]] = $row[2]; // teacher_id => status
            }
        }
        fclose($handle);
        return $attendance;
    }

    public function getTeacherAttendanceReport($startDate, $endDate) {
        $file = __DIR__ . '/../data/teacher_attendance.csv';
        $report = [];
        if (!file_exists($file)) return $report;

        $handle = fopen($file, "r");
        fgetcsv($handle); // skip headers
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            $date = $row[0];
            if ($date >= $startDate && $date <= $endDate) {
                $report[] = [
                    'date' => $row[0],
                    'teacher_id' => $row[1],
                    'status' => $row[2],
                    'created_at' => $row[3],
                    'remarks' => $row[4]
                ];
            }
        }
        fclose($handle);
        return $report;
    }

    public function getTopAttendancePerformers($limit = 3) {
        $file = __DIR__ . '/../data/attendance.csv';
        if (!file_exists($file)) return [];

        $handle = fopen($file, "r");
        fgetcsv($handle); // skip headers

        $studentAttendance = []; // student_id => ['P' => 0, 'total' => 0]
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) >= 4) {
                $sid = $row[2];
                $status = $row[3];
                if (!isset($studentAttendance[$sid])) {
                    $studentAttendance[$sid] = ['P' => 0, 'total' => 0];
                }
                if ($status === 'P') {
                    $studentAttendance[$sid]['P']++;
                }
                $studentAttendance[$sid]['total']++;
            }
        }
        fclose($handle);

        if (empty($studentAttendance)) return [];

        // Calculate percentages
        $rankings = [];
        foreach ($studentAttendance as $sid => $stats) {
            $percent = ($stats['total'] > 0) ? round(($stats['P'] / $stats['total']) * 100, 1) : 0;
            $rankings[] = [
                'student_id' => $sid,
                'percentage' => $percent,
                'total_days' => $stats['total'],
                'present_days' => $stats['P']
            ];
        }

        // Sort by percentage descent, then by total days (as tie-breaker/weight)
        usort($rankings, function($a, $b) {
            if ($b['percentage'] == $a['percentage']) {
                return $b['total_days'] <=> $a['total_days'];
            }
            return $b['percentage'] <=> $a['percentage'];
        });

        $topRankings = array_slice($rankings, 0, $limit);

        // Map student info
        $students = $this->readData();
        $studentMap = [];
        foreach ($students as $s) { $studentMap[$s['id']] = $s; }

        foreach ($topRankings as &$t) {
            $sid = $t['student_id'];
            $t['student_name'] = isset($studentMap[$sid]) ? $studentMap[$sid]['student_name'] : 'Unknown';
            $t['profile_image'] = isset($studentMap[$sid]) ? $studentMap[$sid]['profile_image'] : '';
            $t['current_class'] = isset($studentMap[$sid]) ? $studentMap[$sid]['current_class'] : 'N/A';
        }

        return $topRankings;
    }

    public function getClassPerformanceStats($limit = 3) {
        $file = __DIR__ . '/../data/results.csv';
        if (!file_exists($file)) return [];

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        if (!$headers) return [];

        $classData = []; // class_name => ['sum' => 0, 'count' => 0, 'topper' => ['', 0]]
        $latestYear = 0;
        $all = [];

        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) == count($headers)) {
                $data = $this->safeCombine($headers, $row);
                if (isset($data['year'])) {
                    $year = (int)$data['year'];
                    if ($year > $latestYear) $latestYear = $year;
                }
                $all[] = $data;
            }
        }
        fclose($handle);

        // Map students for names
        $students = $this->readData();
        $studentMap = [];
        foreach ($students as $s) { $studentMap[$s['id']] = $s; }

        foreach ($all as $r) {
            if (isset($r['year']) && (int)$r['year'] === $latestYear) {
                $class = $r['class'];
                $percent = (float)($r['percentage'] ?? 0);
                
                if (!isset($classData[$class])) {
                    $classData[$class] = ['sum' => 0, 'count' => 0, 'top_percent' => -1, 'topper_name' => ''];
                }
                
                $classData[$class]['sum'] += $percent;
                $classData[$class]['count'] += 1;
                
                if ($percent > $classData[$class]['top_percent']) {
                    $classData[$class]['top_percent'] = $percent;
                    $classData[$class]['topper_name'] = isset($studentMap[$r['student_id']]) ? $studentMap[$r['student_id']]['student_name'] : 'Unknown';
                    $classData[$class]['topper_img'] = isset($studentMap[$r['student_id']]) ? $studentMap[$r['student_id']]['profile_image'] : '';
                }
            }
        }

        $stats = [];
        foreach ($classData as $className => $data) {
            $stats[] = [
                'class_name' => $className,
                'avg_percentage' => round($data['sum'] / $data['count'], 1),
                'topper_name' => $data['topper_name'],
                'topper_img' => $data['topper_img'],
                'top_percent' => $data['top_percent']
            ];
        }

        usort($stats, function($a, $b) {
            return $b['avg_percentage'] <=> $a['avg_percentage'];
        });

        return array_slice($stats, 0, $limit);
    }

    // --- Fee Management Methods ---

    public function getFeeStructure() {
        if (self::$fee_structure_cache !== null) {
            return self::$fee_structure_cache;
        }
        $file = __DIR__ . '/../data/fee_structure.csv';
        $structure = [];
        if (file_exists($file)) {
            if (($handle = fopen($file, "r")) !== FALSE) {
                $headers = fgetcsv($handle);
                while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                    if (count($row) >= 2) {
                        $structure[$row[0]] = [
                            'class_name' => $row[0],
                            'monthly_fee' => (float)$row[1],
                            'admission_fee' => (float)($row[2] ?? 0),
                            'exam_fee' => (float)($row[3] ?? 0),
                            'updated_at' => $row[4] ?? ''
                        ];
                    }
                }
                fclose($handle);
            }
        }
        self::$fee_structure_cache = $structure;
        return $structure;
    }

    public function updateFeeStructure($data) {
        self::$fee_structure_cache = null;
        $file = __DIR__ . '/../data/fee_structure.csv';
        $fp = fopen($file, 'w');
        fputcsv($fp, ['class_name', 'monthly_fee', 'admission_fee', 'exam_fee', 'updated_at']);
        foreach ($data as $class => $fees) {
            fputcsv($fp, [
                $class,
                $fees['monthly_fee'],
                $fees['admission_fee'] ?? 0,
                $fees['exam_fee'] ?? 0,
                date('Y-m-d H:i:s')
            ]);
        }
        fclose($fp);
        return true;
    }

    public function getStudentCustomFeesMap() {
        if (self::$student_custom_fees_cache !== null) {
            return self::$student_custom_fees_cache;
        }
        $file = __DIR__ . '/../data/student_custom_fees.csv';
        $map = [];
        if (file_exists($file)) {
            if (($handle = fopen($file, "r")) !== FALSE) {
                fgetcsv($handle);
                while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                    if (count($row) >= 2 && $row[0] !== '') {
                        $map[$row[0]] = [
                            'gr_no' => $row[0],
                            'monthly_fee' => (float)$row[1],
                            'set_by' => $row[2] ?? '',
                            'updated_at' => $row[3] ?? ''
                        ];
                    }
                }
                fclose($handle);
            }
        }
        self::$student_custom_fees_cache = $map;
        return $map;
    }

    public function getStudentCustomFee($gr_no) {
        $map = $this->getStudentCustomFeesMap();
        return $map[$gr_no] ?? null;
    }

    public function getStudentAssignedMonthlyFee($student) {
        if (is_string($student)) {
            $student = $this->getStudentByGrNo($student);
        }
        if (!$student) return 0.0;

        $custom = $this->getStudentCustomFee($student['gr_no']);
        if ($custom !== null) {
            return (float)$custom['monthly_fee'];
        }

        $feeStructure = $this->getFeeStructure();
        $feeClass = $this->getStudentFeeClass($student);
        $classFees = $feeStructure[$feeClass] ?? ['monthly_fee' => 0];
        return (float)$classFees['monthly_fee'];
    }

    public function setStudentCustomFee($gr_no, $monthly_fee) {
        $monthly_fee = (float)$monthly_fee;
        if ($monthly_fee < 0) {
            return $this->removeStudentCustomFee($gr_no);
        }

        self::$student_custom_fees_cache = null;
        $map = $this->getStudentCustomFeesMap();
        $map[$gr_no] = [
            'gr_no' => $gr_no,
            'monthly_fee' => $monthly_fee,
            'set_by' => $_SESSION['user_id'] ?? 'System',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        return $this->writeStudentCustomFees($map);
    }

    public function removeStudentCustomFee($gr_no) {
        self::$student_custom_fees_cache = null;
        $map = $this->getStudentCustomFeesMap();
        unset($map[$gr_no]);
        return $this->writeStudentCustomFees($map);
    }

    private function writeStudentCustomFees($map) {
        $file = __DIR__ . '/../data/student_custom_fees.csv';
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fp = fopen($file, 'w');
        fputcsv($fp, ['gr_no', 'monthly_fee', 'set_by', 'updated_at']);
        foreach ($map as $row) {
            fputcsv($fp, [
                $row['gr_no'],
                $row['monthly_fee'],
                $row['set_by'] ?? '',
                $row['updated_at'] ?? ''
            ]);
        }
        fclose($fp);
        return true;
    }

    public function recordFeePayment($data) {
        self::$fee_collections_cache = null;
        $file = __DIR__ . '/../data/fee_collections.csv';
        // Column order MUST match CSV header: id,gr_no,month_for,amount_paid,discount,payment_method,notes,admission_fee,exam_fee,other_fee,other_label,tuition_fee,payment_date
        
        $id = time() . rand(100, 999);
        $row = [
            $id,
            $data['gr_no'],
            $data['month_for'],
            $data['amount_paid'],
            $data['discount'] ?? 0,
            $data['payment_method'] ?? 'Cash',
            $data['notes'] ?? '',
            $data['admission_fee'] ?? 0,
            $data['exam_fee'] ?? 0,
            $data['other_fee'] ?? 0,
            $data['other_label'] ?? '',
            $data['tuition_fee'] ?? 0,
            $data['payment_date'] ?? date('Y-m-d')
        ];

        $fp = fopen($file, 'a');
        fputcsv($fp, $row);
        fclose($fp);
        return $id;
    }

    public function updateFeePayment($id, $data) {
        self::$fee_collections_cache = null;
        $file = __DIR__ . '/../data/fee_collections.csv';
        // Column order matches CSV header: id,gr_no,month_for,amount_paid,discount,payment_method,notes,admission_fee,exam_fee,other_fee,other_label,tuition_fee,payment_date
        $headers = ['id', 'gr_no', 'month_for', 'amount_paid', 'discount', 'payment_method', 'notes', 'admission_fee', 'exam_fee', 'other_fee', 'other_label', 'tuition_fee', 'payment_date'];
        
        $collections = [];
        if (($handle = fopen($file, "r")) !== FALSE) {
            fgetcsv($handle); // skip headers
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                if ($row[0] == $id) {
                    // Update fields — indices match CSV header order
                    if (isset($data['month_for'])) $row[2] = $data['month_for'];
                    if (isset($data['amount_paid'])) $row[3] = $data['amount_paid'];
                    if (isset($data['discount'])) $row[4] = $data['discount'];
                    if (isset($data['payment_method'])) $row[5] = $data['payment_method'];
                    if (isset($data['notes'])) $row[6] = $data['notes'];
                    if (isset($data['payment_date'])) $row[12] = $data['payment_date'];
                    
                    // Update breakdown fields
                    // If row was shorter, pad it
                    while (count($row) < count($headers)) $row[] = '';
                    $row[7] = $data['admission_fee'] ?? ($row[7] ?? 0);
                    $row[8] = $data['exam_fee'] ?? ($row[8] ?? 0);
                    $row[9] = $data['other_fee'] ?? ($row[9] ?? 0);
                    $row[10] = $data['other_label'] ?? ($row[10] ?? '');
                    $row[11] = $data['tuition_fee'] ?? ($row[11] ?? 0);
                }
                $collections[] = $row;
            }
            fclose($handle);
        }

        $fp = fopen($file, 'w');
        fputcsv($fp, $headers);
        foreach ($collections as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return true;
    }

    public function addStudentFeeArrears($gr_no, $month_for, $amount, $remarks) {
        $amount = (float)$amount;
        if ($amount <= 0) return false;

        $student = $this->getStudentByGrNo($gr_no);
        if (!$student) return false;

        $feeStructure = $this->getFeeStructure();
        $assignedMonthly = $this->getStudentAssignedMonthlyFee($student);

        $noteEntry = '[Arrears +Rs.' . number_format($amount, 0) . '] ' . trim($remarks);
        $existing = null;
        foreach ($this->getStudentFeeHistory($gr_no) as $h) {
            if ($h['month_for'] === $month_for) {
                $existing = $h;
                break;
            }
        }

        if ($existing) {
            $currentTuition = (isset($existing['tuition_fee']) && $existing['tuition_fee'] !== '' && (float)$existing['tuition_fee'] > 0)
                ? (float)$existing['tuition_fee'] : $assignedMonthly;
            $newNotes = trim(($existing['notes'] ?? ''));
            $newNotes = $newNotes !== '' ? $newNotes . "\n" . $noteEntry : $noteEntry;

            return $this->updateFeePayment($existing['id'], [
                'month_for' => $month_for,
                'amount_paid' => (float)$existing['amount_paid'],
                'discount' => (float)($existing['discount'] ?? 0),
                'payment_method' => $existing['payment_method'] ?? 'Arrears',
                'notes' => $newNotes,
                'admission_fee' => (float)($existing['admission_fee'] ?? 0),
                'exam_fee' => (float)($existing['exam_fee'] ?? 0),
                'other_fee' => (float)($existing['other_fee'] ?? 0),
                'other_label' => $existing['other_label'] ?? '',
                'tuition_fee' => $currentTuition + $amount
            ]);
        }

        $id = $this->recordFeePayment([
            'gr_no' => $gr_no,
            'month_for' => $month_for,
            'amount_paid' => 0,
            'tuition_fee' => $amount,
            'discount' => 0,
            'payment_method' => 'Arrears',
            'notes' => $noteEntry,
            'payment_date' => date('Y-m-d')
        ]);

        return (bool)$id;
    }

    public function deleteFeePayment($id) {
        self::$fee_collections_cache = null;
        $file = __DIR__ . '/../data/fee_collections.csv';
        if (!file_exists($file)) return false;

        $temp = [];
        $found = false;
        if (($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            $temp[] = $headers;
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                if ($row[0] == $id) {
                    $found = true;
                    continue;
                }
                $temp[] = $row;
            }
            fclose($handle);
        }

        if ($found) {
            $fp = fopen($file, 'w');
            foreach ($temp as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            return true;
        }
        return false;
    }

    public function clearStudentDebt($gr_no) {
        self::$fee_collections_cache = null;
        $file = __DIR__ . '/../data/fee_collections.csv';
        if (!file_exists($file)) return false;

        $temp = [];
        $found = false;
        if (($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            $temp[] = $headers;
            $grIdx = array_search('gr_no', $headers);
            $methodIdx = array_search('payment_method', $headers);
            $tuitionIdx = array_search('tuition_fee', $headers);
            $admissionIdx = array_search('admission_fee', $headers);
            $examIdx = array_search('exam_fee', $headers);
            $otherIdx = array_search('other_fee', $headers);
            $discountIdx = array_search('discount', $headers);
            $paidIdx = array_search('amount_paid', $headers);
            $notesIdx = array_search('notes', $headers);
            while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                $matches = isset($row[$grIdx]) && trim($row[$grIdx]) === $gr_no;
                $isArrears = isset($row[$methodIdx]) && trim($row[$methodIdx]) === 'Arrears';
                if ($isArrears && $matches) {
                    $found = true;
                    continue;
                }
                if ($matches && !$isArrears) {
                    $due = (float)($row[$tuitionIdx] ?? 0)
                         + (float)($row[$admissionIdx] ?? 0)
                         + (float)($row[$examIdx] ?? 0)
                         + (float)($row[$otherIdx] ?? 0)
                         - (float)($row[$discountIdx] ?? 0);
                    $paid = (float)($row[$paidIdx] ?? 0);
                    if ($due > $paid + 0.01) {
                        $row[$paidIdx] = (string)$due;
                        $notes = trim($row[$notesIdx] ?? '');
                        $row[$notesIdx] = ($notes ? $notes . ' ' : '') . '[Cleared by admin]';
                        $found = true;
                    }
                }
                $temp[] = $row;
            }
            fclose($handle);
        }

        if ($found) {
            $fp = fopen($file, 'w');
            foreach ($temp as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            self::$fee_collections_cache = null;
            return true;
        }
        return false;
    }

    public function getFeeCollections($filters = []) {
        if (self::$fee_collections_cache === null) {
            $file = __DIR__ . '/../data/fee_collections.csv';
            $collections = [];
            if (file_exists($file)) {
                if (($handle = fopen($file, "r")) !== FALSE) {
                    $headers = fgetcsv($handle);
                    while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                        $item = $this->safeCombine($headers, $row);
                        $collections[] = $item;
                    }
                    fclose($handle);
                }
            }
            // Latest first
            usort($collections, function($a, $b) {
                return strcmp($b['payment_date'], $a['payment_date']);
            });
            self::$fee_collections_cache = $collections;
        }
        
        $filtered = [];
        foreach (self::$fee_collections_cache as $item) {
            $match = true;
            if (isset($filters['gr_no']) && $item['gr_no'] != $filters['gr_no']) $match = false;
            if (isset($filters['month']) && $item['month_for'] != $filters['month']) $match = false;
            if (isset($filters['id']) && $item['id'] != $filters['id']) $match = false;
            
            if ($match) {
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

    public function getStudentFeeHistory($gr_no) {
        return $this->getFeeCollections(['gr_no' => $gr_no]);
    }

    public function getFeeStats($month = null) {
        $collections = $this->getFeeCollections();
        if ($month) {
            $thisMonth = $month;
        } else {
            $thisMonth = date('Y-m');
        }
        $today = date('Y-m-d');
        
        $stats = [
            'this_month' => 0,
            'today' => 0,
            'class_breakdown' => [],
            'recent' => array_slice($collections, 0, 5)
        ];

        $students = $this->readData();
        $stMap = [];
        foreach ($students as $s) $stMap[$s['gr_no']] = $s['current_class'];

        foreach ($collections as $c) {
            if (strpos($c['payment_date'], $thisMonth) === 0) {
                $stats['this_month'] += (float)$c['amount_paid'];
                
                $cls = $stMap[$c['gr_no']] ?? 'Unknown';
                if (!isset($stats['class_breakdown'][$cls])) $stats['class_breakdown'][$cls] = 0;
                $stats['class_breakdown'][$cls] += (float)$c['amount_paid'];
            }
            if ($c['payment_date'] === $today) {
                $stats['today'] += (float)$c['amount_paid'];
            }
        }
        return $stats;
    }

    /**
     * Internal helper: calculates previous debt for one student.
     * Accepts pre-fetched data to avoid repeated DB scans.
     *
     * Debt is counted from the FIRST MONTH the student ever had a fee record,
     * NOT from their admission_date. This prevents phantom arrears from months
     * before the fee system was in use for that student.
     *
     * @param array  $student              Student record array.
     * @param array  $studentHistoryMap    Map of month_for => payment record for this student.
     * @param string $target_month         YYYY-MM format (exclusive upper bound).
     * @param float  $standard_monthly_fee The class's monthly fee.
     * @return float
     */
    private function calcDebtBreakdownForStudent($student, $studentHistoryMap, $target_month, $standard_monthly_fee) {
        if (empty($studentHistoryMap)) {
            return [];
        }

        $fee_start_month = null;
        foreach ($studentHistoryMap as $m => $h) {
            if ($fee_start_month === null || $m < $fee_start_month) {
                $fee_start_month = $m;
            }
        }

        if ($fee_start_month >= $target_month) {
            return [];
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $fee_start_month) || !preg_match('/^\d{4}-\d{2}$/', $target_month)) {
            return [];
        }

        $start = new DateTime($fee_start_month . '-01');
        $end   = new DateTime($target_month    . '-01');

        // For Alumni students, cap the debt loop at the month after they left —
        // no new monthly fees should accrue after passout.
        if (($student['student_status'] ?? '') === 'Alumni') {
            $leaveMonth = !empty($student['updated_at'])
                ? date('Y-m', strtotime($student['updated_at']))
                : date('Y-m');
            $alumniCap = new DateTime(date('Y-m', strtotime($leaveMonth . '-01 +1 month')) . '-01');
            if ($alumniCap < $end) {
                $end = $alumniCap;
            }
        }

        $breakdown = [];

        while ($start < $end) {
            $m = $start->format('Y-m');
            if (isset($studentHistoryMap[$m])) {
                $h = $studentHistoryMap[$m];
                $due_tuition = (isset($h['tuition_fee']) && $h['tuition_fee'] !== '') ? (float)$h['tuition_fee'] : $standard_monthly_fee;
                $month_dues  = $due_tuition
                             + (float)($h['admission_fee'] ?? 0)
                             + (float)($h['exam_fee']     ?? 0)
                             + (float)($h['other_fee']    ?? 0)
                             - (float)($h['discount']     ?? 0);
                $paid = (float)$h['amount_paid'];
                $balance = $month_dues - $paid;
                if ($balance != 0.0) {
                    $breakdown[] = [
                        'month' => $m,
                        'due' => $month_dues,
                        'paid' => $paid,
                        'balance' => $balance,
                        'status' => $balance > 0 ? ($paid > 0 ? 'partial' : 'unpaid') : 'surplus'
                    ];
                }
            } else {
                $breakdown[] = [
                    'month' => $m,
                    'due' => $standard_monthly_fee,
                    'paid' => 0.0,
                    'balance' => $standard_monthly_fee,
                    'status' => 'unpaid'
                ];
            }
            $start->modify('+1 month');
        }

        return $breakdown;
    }

    private function calcDebtForStudent($student, $studentHistoryMap, $target_month, $standard_monthly_fee) {
        $breakdown = $this->calcDebtBreakdownForStudent($student, $studentHistoryMap, $target_month, $standard_monthly_fee);
        $total = 0.0;
        foreach ($breakdown as $row) {
            $total += $row['balance'];
        }
        return $total;
    }

    public function getStudentPreviousDebtBreakdown($gr_no, $target_month) {
        $student = $this->getStudentByGrNo($gr_no);
        if (!$student) return [];

        $assigned_monthly_fee = $this->getStudentAssignedMonthlyFee($student);

        $history = $this->getStudentFeeHistory($gr_no);
        $historyMap = [];
        foreach ($history as $h) {
            $historyMap[$h['month_for']] = $h;
        }

        return $this->calcDebtBreakdownForStudent($student, $historyMap, $target_month, $assigned_monthly_fee);
    }


    /**
     * Returns cumulative unpaid debt for a student up to (but not including) $target_month.
     * Used by the single-student API path (get_fee_status.php).
     */
    public function getStudentPreviousDebt($gr_no, $target_month) {
        $student = $this->getStudentByGrNo($gr_no);
        if (!$student) return 0;

        // For Alumni, cap target_month so no fees accrue after passout
        if (($student['student_status'] ?? '') === 'Alumni') {
            $leaveMonth = !empty($student['updated_at'])
                ? date('Y-m', strtotime($student['updated_at']))
                : date('Y-m');
            $alumniCap = date('Y-m', strtotime($leaveMonth . '-01 +1 month'));
            if ($alumniCap < $target_month) {
                $target_month = $alumniCap;
            }
        }

        $assigned_monthly_fee = $this->getStudentAssignedMonthlyFee($student);

        $history = $this->getStudentFeeHistory($gr_no);
        $historyMap = [];
        foreach ($history as $h) {
            $historyMap[$h['month_for']] = $h;
        }

        return $this->calcDebtForStudent($student, $historyMap, $target_month, $assigned_monthly_fee);
    }

    /**
     * Returns all students who have not fully paid for $month.
     * Optimized: builds all lookup maps once outside the per-student loop
     * so complexity is O(N + M) instead of O(N * (N + M)).
     */
    public function getDefaulters($month = null) {
        if (!$month) $month = date('Y-m');

        $students      = $this->readData();
        $feeStructure  = $this->getFeeStructure();
        $allCollections = $this->getFeeCollections(); // all records (cached)

        // --- Build maps ---

        // 1. Collections for the target month, keyed by gr_no
        $monthCollectionsMap = [];
        foreach ($allCollections as $c) {
            if ($c['month_for'] === $month) {
                $monthCollectionsMap[$c['gr_no']] = $c;
            }
        }

        // 2. All historical collections grouped by gr_no, then by month_for
        //    [ gr_no => [ month_for => record ] ]
        $allHistoryByGrNo = [];
        foreach ($allCollections as $c) {
            $allHistoryByGrNo[$c['gr_no']][$c['month_for']] = $c;
        }

        // --- Identify defaulters ---
        $defaulters = [];
        foreach ($students as $s) {
            $status = $s['student_status'] ?? '';
            if ($status !== 'Active' && $status !== '0' && $status !== '') continue;

            $gr    = $s['gr_no'];
            $assignedMonthly = $this->getStudentAssignedMonthlyFee($s);

            $studentHistoryMap = $allHistoryByGrNo[$gr] ?? [];
            $previous_debt = $this->calcDebtForStudent($s, $studentHistoryMap, $month, $assignedMonthly);

            if (!isset($monthCollectionsMap[$gr])) {
                // Fully unpaid for this month
                $total_due = $assignedMonthly + $previous_debt;
                if ($total_due > 0) {
                    $s['payment_status'] = 'Unpaid';
                    $s['debt']           = $total_due;
                    $defaulters[]        = $s;
                }
            } else {
                // Check if only partially paid
                $p        = $monthCollectionsMap[$gr];
                $paid     = (float)$p['amount_paid'];
                $discount = (float)($p['discount']     ?? 0);
                $admFee   = (float)($p['admission_fee'] ?? 0);
                $examFee  = (float)($p['exam_fee']      ?? 0);
                $otherFee = (float)($p['other_fee']     ?? 0);

                $due_tuition = (isset($p['tuition_fee']) && $p['tuition_fee'] !== '')
                               ? (float)$p['tuition_fee']
                               : $assignedMonthly;

                $expected = $due_tuition + $admFee + $examFee + $otherFee - $discount + $previous_debt;
                if ($paid < $expected - 0.01) {
                    $s['payment_status'] = $paid > 0 ? 'Partial' : 'Unpaid';
                    $s['debt']           = $expected - $paid;
                    $defaulters[]        = $s;
                }
            }
        }
        return $defaulters;
    }

    /**
     * Parent Portal Methods
     */

    public function getParentNameByCnic($cnic) {
        $raw_cnic = str_replace('-', '', $cnic);
        $students = $this->readData();
        foreach ($students as $student) {
            $s_father_cnic = str_replace('-', '', $student['father_cnic']);
            if ($s_father_cnic === $raw_cnic) {
                return $student['father_name'];
            }
        }
        return null;
    }

    public function verifyParentLogin($cnic, $password) {
        $cnic = trim($cnic);
        $password = trim($password);
        $raw_cnic = str_replace('-', '', $cnic);
        if (empty($raw_cnic)) return false;

        // 1. Check custom credentials first (hashed)
        $customCredentials = $this->getParentCredentials();
        if (isset($customCredentials[$raw_cnic])) {
            if (password_verify($password, $customCredentials[$raw_cnic]['password_hash'])) {
                $students = $this->readData();
                foreach ($students as $student) {
                    if (str_replace('-', '', $student['father_cnic']) === $raw_cnic) {
                        return [
                            'father_cnic' => $raw_cnic,
                            'father_name' => $student['father_name'],
                            'is_custom' => true
                        ];
                    }
                }
            }
        }

        // 2. Fallback to eldest child DOB (hashed comparison)
        $students = $this->readData();
        $parentChildren = [];
        foreach ($students as $student) {
            $s_father_cnic = str_replace('-', '', $student['father_cnic']);
            if ($s_father_cnic === $raw_cnic) {
                $parentChildren[] = $student;
            }
        }

        if (empty($parentChildren)) return false;

        usort($parentChildren, function($a, $b) {
            return $a['id'] - $b['id'];
        });

        $firstChild = $parentChildren[0];
        $expectedPassword = $firstChild['date_of_birth'];

        // Store the DOB-based password as a hash on first login for future verification
        if ($password === $expectedPassword) {
            // Migrate to hashed password for future logins
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $this->saveParentPassword($raw_cnic, $hash);
            return [
                'father_cnic' => $raw_cnic,
                'father_name' => $firstChild['father_name'],
                'children_count' => count($parentChildren),
                'is_custom' => false
            ];
        }

        return false;
    }

    public function verifyTeacherLogin($cnic, $password) {
        $cnic = trim($cnic);
        $password = trim($password);
        $raw_cnic = str_replace('-', '', $cnic);
        if (empty($raw_cnic)) return false;

        $file = __DIR__ . '/../data/teachers.csv';
        if (!file_exists($file)) return false;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            $teacher = $this->safeCombine($headers, $row);
            $s_teacher_cnic = str_replace('-', '', $teacher['cnic'] ?? '');
            
            if ($s_teacher_cnic === $raw_cnic) {
                // Password is DOB (YYYY-MM-DD)
                if ($password === ($teacher['dob'] ?? '')) {
                    fclose($handle);
                    return $teacher;
                }
            }
        }
        fclose($handle);
        return false;
    }

    public function getParentCredentials() {
        $file = __DIR__ . '/../data/parent_credentials.csv';
        if (!file_exists($file)) return [];
        
        $credentials = [];
        if (($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 2) {
                    $credentials[$data[0]] = [
                        'password_hash' => $data[1],
                        'updated_at' => $data[2] ?? ''
                    ];
                }
            }
            fclose($handle);
        }
        return $credentials;
    }

    public function saveParentPassword($cnic, $password) {
        $file = __DIR__ . '/../data/parent_credentials.csv';
        $credentials = $this->getParentCredentials();
        $raw_cnic = str_replace('-', '', $cnic);
        
        // Always hash the password before storing
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $credentials[$raw_cnic] = [
            'password_hash' => $hash,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $fp = fopen($file, 'w');
        fputcsv($fp, ['father_cnic', 'password_hash', 'updated_at']);
        foreach ($credentials as $cnic_key => $data) {
            fputcsv($fp, [$cnic_key, $data['password_hash'], $data['updated_at']]);
        }
        fclose($fp);
        return true;
    }


    public function getParentChildrenByCnic($cnic) {
        $raw_cnic = str_replace('-', '', $cnic);
        $students = $this->readData();
        $children = [];
        
        foreach ($students as $student) {
            $s_father_cnic = str_replace('-', '', $student['father_cnic'] ?? '');
            if ($s_father_cnic === $raw_cnic) {
                $children[] = $student;
            }
        }
        return $children;
    }

    public function getStudentAttendanceHistory($studentId) {
        $attendanceFile = __DIR__ . '/../data/attendance.csv';
        $history = [];
        
        if (!file_exists($attendanceFile)) return [];

        $handle = @fopen($attendanceFile, "r");
        if ($handle === false) return [];

        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) < 4) continue;
            if ($row[2] == $studentId) {
                $history[] = [
                    'date' => $row[0],
                    'class' => $row[1],
                    'status' => $row[3]
                ];
            }
        }
        fclose($handle);
        
        // Sort by date descending
        usort($history, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        
        return $history;
    }

    public function getStudentResults($studentId) {
        $resultsFile = __DIR__ . '/../data/results.csv';
        if (!file_exists($resultsFile)) return [];

        $handle = @fopen($resultsFile, "r");
        if ($handle === false) return [];

        $results = [];
        $header = fgetcsv($handle);
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            $data = $this->safeCombine($header, $row);
            if ($data['student_id'] == $studentId) {
                $results[] = $data;
            }
        }
        fclose($handle);
        return $results;
    }

    public function getParentNotices($cnic) {
        $noticesFile = __DIR__ . '/../data/parent_notices.csv';
        if (!file_exists($noticesFile)) return [];

        $handle = @fopen($noticesFile, "r");
        if ($handle === false) return [];

        $notices = [];
        $header = fgetcsv($handle);
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            $data = $this->safeCombine($header, $row);
            
            // Show if target is 'ALL' or specific parent CNIC
            if ($data['target_cnic'] === 'ALL' || $data['target_cnic'] == $cnic) {
                // Check expiry if set
                if (!empty($data['expiry_date'])) {
                    $expiry = strtotime($data['expiry_date']);
                    if ($expiry < time()) {
                        continue; // Skip expired notice
                    }
                }
                $notices[] = $data;
            }
        }
        fclose($handle);
        
        // Sort by date descending
        usort($notices, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        
        return $notices;
    }

    public function saveParentNotice($data) {
        $noticesFile = __DIR__ . '/../data/parent_notices.csv';
        $isNew = !file_exists($noticesFile);
        
        $handle = fopen($noticesFile, "a");
        if ($isNew) {
            fputcsv($handle, ['id', 'target_cnic', 'title', 'message', 'type', 'created_at', 'expiry_date']);
        }
        
        $row = [
            $data['id'] ?? time(),
            $data['target_cnic'],
            $data['title'],
            $data['message'],
            $data['type'] ?? 'General',
            $data['created_at'] ?? date('Y-m-d H:i:s'),
            $data['expiry_date'] ?? ''
        ];
        
        $result = fputcsv($handle, $row);
        fclose($handle);
        return $result !== false;
    }

    // ==================== EXPENSE CATEGORY METHODS ====================
    public function getExpenseCategories() {
        $file = __DIR__ . '/../data/expense_categories.csv';
        $categories = [];
        if (!file_exists($file)) return $categories;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) >= 4) {
                 $categories[] = [
                    'id' => $row[0],
                    'name' => $row[1],
                    'description' => $row[2],
                    'created_at' => $row[3]
                 ];
            }
        }
        fclose($handle);
        return $categories;
    }

    public function addExpenseCategory($name, $description) {
        $file = __DIR__ . '/../data/expense_categories.csv';
        $categories = $this->getExpenseCategories();
        
        $lastId = 0;
        if (!empty($categories)) {
            $lastItem = end($categories);
            $lastId = (int)$lastItem['id'];
        }
        $id = $lastId + 1;
        
        $record = [
            $id,
            $name,
            $description,
            date('Y-m-d H:i:s')
        ];

        $fp = fopen($file, 'a');
        fputcsv($fp, $record);
        fclose($fp);
        return $id;
    }

    public function deleteExpenseCategory($id) {
        $file = __DIR__ . '/../data/expense_categories.csv';
        $categories = $this->getExpenseCategories();
        
        $newCategories = array_filter($categories, function($cat) use ($id) {
            return $cat['id'] != $id;
        });

        $fp = fopen($file, 'w');
        fputcsv($fp, ['id','name','description','created_at']);
        foreach ($newCategories as $cat) {
            fputcsv($fp, $cat);
        }
        fclose($fp);
        return true;
    }

    public function updateExpenseCategory($id, $name, $description) {
        $file = __DIR__ . '/../data/expense_categories.csv';
        $categories = $this->getExpenseCategories();
        $headers = ['id','name','description','created_at'];
        
        $found = false;
        foreach ($categories as &$cat) {
            if ($cat['id'] == $id) {
                $cat['name'] = $name;
                $cat['description'] = $description;
                $found = true;
                break;
            }
        }
        unset($cat);

        if ($found) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            foreach ($categories as $cat) {
                fputcsv($fp, $cat);
            }
            fclose($fp);
            return true;
        }
        return false;
    }

    // ==================== EXPENSE METHODS ====================
    public function getExpenses($filters = []) {
        $file = __DIR__ . '/../data/expenses.csv';
        $expenses = [];
        if (!file_exists($file)) return $expenses;

        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",");
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (count($row) >= 9) {
                // Filter by Category
                if (isset($filters['category_id']) && $filters['category_id'] !== '' && $row[1] != $filters['category_id']) {
                    continue;
                }
                // Filter by date range
                if (isset($filters['date_from']) && $filters['date_from'] !== '' && $row[4] < $filters['date_from']) {
                    continue;
                }
                if (isset($filters['date_to']) && $filters['date_to'] !== '' && $row[4] > $filters['date_to']) {
                    continue;
                }

                $expenses[] = [
                    'id' => $row[0],
                    'category_id' => $row[1],
                    'description' => $row[2],
                    'amount' => $row[3],
                    'expense_date' => $row[4],
                    'payment_method' => $row[5],
                    'vendor' => $row[6],
                    'receipt_ref' => $row[7],
                    'notes' => $row[8],
                    'created_at' => $row[9]
                ];
            }
        }
        fclose($handle);
        
        // Sort by date descending (newest first)
        usort($expenses, function($a, $b) {
            return strcmp($b['expense_date'], $a['expense_date']);
        });
        
        return $expenses;
    }

    public function getExpense($id) {
        $expenses = $this->getExpenses();
        foreach ($expenses as $exp) {
            if ($exp['id'] == $id) return $exp;
        }
        return null;
    }

    public function addExpense($data) {
        $file = __DIR__ . '/../data/expenses.csv';
        
        $rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastId = 0;
        if (count($rows) > 0) {
            $lastRow = str_getcsv(trim(end($rows)));
            if (isset($lastRow[0]) && is_numeric($lastRow[0])) {
                $lastId = (int)$lastRow[0];
            }
        }
        $id = $lastId + 1;

        $record = [
            $id,
            $data['category_id'],
            $data['description'],
            $data['amount'],
            $data['expense_date'],
            $data['payment_method'],
            $data['vendor'],
            $data['receipt_ref'],
            $data['notes'],
            date('Y-m-d H:i:s')
        ];

        $fp = fopen($file, 'a');
        fputcsv($fp, $record);
        fclose($fp);
        return $id;
    }

    public function updateExpense($id, $data) {
        $file = __DIR__ . '/../data/expenses.csv';
        $expenses = $this->getExpenses();
        $headers = ['id','category_id','description','amount','expense_date','payment_method','vendor','receipt_ref','notes','created_at'];

        $found = false;
        foreach ($expenses as &$exp) {
            if ($exp['id'] == $id) {
                if (isset($data['category_id'])) $exp['category_id'] = $data['category_id'];
                if (isset($data['description'])) $exp['description'] = $data['description'];
                if (isset($data['amount'])) $exp['amount'] = $data['amount'];
                if (isset($data['expense_date'])) $exp['expense_date'] = $data['expense_date'];
                if (isset($data['payment_method'])) $exp['payment_method'] = $data['payment_method'];
                if (isset($data['vendor'])) $exp['vendor'] = $data['vendor'];
                if (isset($data['receipt_ref'])) $exp['receipt_ref'] = $data['receipt_ref'];
                if (isset($data['notes'])) $exp['notes'] = $data['notes'];
                $found = true;
            }
        }
        unset($exp);

        if ($found) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            foreach ($expenses as $exp) {
                fputcsv($fp, $exp);
            }
            fclose($fp);
            return true;
        }
        return false;
    }

    public function deleteExpense($id) {
        $file = __DIR__ . '/../data/expenses.csv';
        $expenses = $this->getExpenses();
        $headers = ['id','category_id','description','amount','expense_date','payment_method','vendor','receipt_ref','notes','created_at'];

        $newExpenses = array_filter($expenses, function($exp) use ($id) {
            return $exp['id'] != $id;
        });

        if (count($expenses) !== count($newExpenses)) {
            $fp = fopen($file, 'w');
            fputcsv($fp, $headers);
            foreach ($newExpenses as $exp) {
                fputcsv($fp, $exp);
            }
            fclose($fp);
            return true;
        }
        return false;
    }
}
