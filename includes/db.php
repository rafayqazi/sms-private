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

    public function __construct($file = null) {
        if ($file === null) {
            $file = __DIR__ . '/../data/database.csv';
        }
        $this->csvFile = $file;
        // New Schema Headers
        $this->headers = [
            'id', 'gr_no', 'student_name', 'father_name', 'gender', 'date_of_birth', 
            'admission_date', 'current_class', 'age', 'b_form_no', 'father_cnic', 
            'father_contact', 'district', 'taluka', 'school_name', 'semis_code', 
            'is_active', 'created_at', 'updated_at', 'father_cnic_front', 
            'father_cnic_back', 'b_form_img', 'profile_image', 'previous_school', 'slc_img',
            'student_status', 'is_repeater', 'graduation_year', 'last_class'
        ];

        if (!file_exists($this->csvFile)) {
            $this->writeData([]);
        }
    }

    private function getHeaders() {
        if (file_exists($this->csvFile) && ($handle = fopen($this->csvFile, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",");
            fclose($handle);
            return $headers;
        }
        return $this->headers;
    }

    public function readData() {
        $data = [];
        if (file_exists($this->csvFile) && ($handle = fopen($this->csvFile, "r")) !== FALSE) {
            $fileHeaders = fgetcsv($handle, 1000, ","); // Skip headers
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Adjust row to match headers length (for robustness against schema updates)
                if (count($row) < count($this->headers)) {
                    $row = array_pad($row, count($this->headers), '');
                } elseif (count($row) > count($this->headers)) {
                    $row = array_slice($row, 0, count($this->headers));
                }
                
                $data[] = array_combine($this->headers, $row);
            }
            fclose($handle);
        }
        return $data;
    }

    public function writeData($data) {
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
        
        // Get Start ID
        $lastId = 0;
        if (!empty($data)) {
            $lastItem = end($data);
            $lastId = isset($lastItem['id']) ? (int)$lastItem['id'] : 0;
        }

        $now = date('Y-m-d H:i:s');
        $today = new DateTime();

        foreach ($studentsArray as $studentData) {
            $lastId++;
            $studentData['id'] = $lastId;
            $studentData['created_at'] = $now;
            $studentData['updated_at'] = $now;
            $studentData['is_active'] = isset($studentData['is_active']) ? $studentData['is_active'] : 1;

            // Age calculation
            if (!empty($studentData['date_of_birth'])) {
                try {
                    $dob = new DateTime($studentData['date_of_birth']);
                    $studentData['age'] = $dob->diff($today)->y;
                } catch (Exception $e) {
                    $studentData['age'] = '';
                }
            } else {
                $studentData['age'] = '';
            }

            // Default values for missing fields to avoid array_combine mismatch if internal write logic changes
            $data[] = $studentData;
        }

        return $this->writeData($data);
    }

    public function getNextGrNo() {
        $data = $this->readData();
        $maxGr = 0;
        foreach ($data as $student) {
            $gr = isset($student['gr_no']) ? (int)$student['gr_no'] : 0;
            if ($gr > $maxGr) {
                $maxGr = $gr;
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
            if (isset($filters['class']) && !empty($filters['class'])) {
                if ($student['current_class'] != $filters['class']) {
                    $match = false;
                }
            }

            // Gender Filter
            if (isset($filters['gender']) && !empty($filters['gender'])) {
                if ($student['gender'] != $filters['gender']) {
                    $match = false;
                }
            }

            // Search Filter (Name or GR No)
            if (isset($filters['search']) && !empty($filters['search'])) {
                $searchTerm = strtolower($filters['search']);
                $nameMatch = stripos($student['student_name'], $searchTerm) !== false;
                $grMatch = stripos($student['gr_no'], $searchTerm) !== false;
                
                if (!$nameMatch && !$grMatch) {
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
            $headers = fgetcsv($handle, 1000, ","); // Skip headers
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
            $fileHeaders = fgetcsv($handle, 1000, ",");
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
            'address', 'district', 'tahsil', 'profile_image', 'joining_date', 'created_at'
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
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row[0] == $id) {
                // Adjust row to match headers length
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                } elseif (count($row) > count($headers)) {
                    $row = array_slice($row, 0, count($headers));
                }
                return array_combine($headers, $row);
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
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) > 0) {
                // Adjust row to match headers length
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                } elseif (count($row) > count($headers)) {
                    $row = array_slice($row, 0, count($headers));
                }
                $teachers[] = array_combine($headers, $row);
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

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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

        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers; // Keep headers

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
                    isset($data['profile_image']) ? $data['profile_image'] : $row[21], // Use new image or keep old
                    isset($data['joining_date']) ? $data['joining_date'] : (isset($row[22]) ? $row[22] : ''), // Preserve old value if not editing, though here we update all
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
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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

    public function promoteStudent($id, $action) {
        $students = $this->readData();
        
        // Generate dynamic class progression
        $classes = $this->getClasses();
        $classProgression = [];
        $lastClassName = '';
        for ($i = 0; $i < count($classes); $i++) {
            $currentName = $classes[$i]['class_name'];
            if ($i < count($classes) - 1) {
                $classProgression[$currentName] = $classes[$i+1]['class_name'];
            } else {
                // Last class promotes to Alumni
                $classProgression[$currentName] = 'Alumni (Passed Students)';
                $lastClassName = $currentName;
            }
        }

        foreach ($students as &$student) {
            if ($student['id'] == $id) {
                // Set defaults for existing students without these fields
                if (!isset($student['student_status'])) {
                    $student['student_status'] = 'Active';
                }
                if (!isset($student['is_repeater'])) {
                    $student['is_repeater'] = '0';
                }

                $currentClass = $student['current_class'];
                
                if ($action === 'pass') {
                    // Promote to next class
                    if (isset($classProgression[$currentClass])) {
                        $nextClass = $classProgression[$currentClass];
                        $student['current_class'] = $nextClass;
                        
                        // If promoted from the last class, mark as Alumni
                        if ($currentClass === $lastClassName) {
                            $student['student_status'] = 'Alumni';
                            $student['graduation_year'] = date('Y');
                            $student['last_class'] = $currentClass;
                        }
                    }
                    $student['is_repeater'] = '0';
                } elseif ($action === 'passout') {
                    // Mark as Alumni from any class
                    $student['student_status'] = 'Alumni';
                    $student['graduation_year'] = date('Y');
                    $student['last_class'] = $currentClass;
                    $student['is_repeater'] = '0';
                } elseif ($action === 'fail') {
                    $student['is_repeater'] = '1';
                } elseif ($action === 'stay') {
                    $student['is_repeater'] = '0';
                }
                
                $student['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        return $this->writeData($students);
    }

    public function createUserRole($teacherId, $role, $username, $password, $classes = []) {
        $file = __DIR__ . '/../data/user_roles.csv';
        $headers = ['id', 'teacher_id', 'role', 'username', 'password_hash', 'assigned_classes', 'created_at', 'updated_at'];

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
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) >= 6 && $row[3] == $username) {
                $userRole = array_combine($headers, $row);
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
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) >= 6 && $row[1] == $teacherId) {
                $userRole = array_combine($headers, $row);
                // Decode classes JSON
                $userRole['assigned_classes'] = json_decode($userRole['assigned_classes'], true);
                fclose($handle);
                return $userRole;
            }
        }
        fclose($handle);
        return null;
    }

    public function updateUserRole($teacherId, $role, $username, $password, $classes = []) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return ['success' => false, 'message' => 'User roles file not found'];

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return ['success' => false, 'message' => 'Could not open user_roles.csv'];

        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers;
        $found = false;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
                
                $updatedRow = [
                    $row[0], // id
                    $teacherId,
                    $role,
                    $username,
                    $passwordHash,
                    $classesJson,
                    $row[6], // created_at
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

    public function updateUserRoleById($id, $role, $username, $password, $classes = []) {
        $file = __DIR__ . '/../data/user_roles.csv';
        if (!file_exists($file)) return ['success' => false, 'message' => 'User roles file not found'];

        $rows = [];
        $handle = @fopen($file, "r");
        if ($handle === false) return ['success' => false, 'message' => 'Could not open user_roles.csv'];

        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers;
        $found = false;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
                
                $updatedRow = [
                    $id,
                    $row[1], // teacher_id
                    $role,
                    $username,
                    $passwordHash,
                    $classesJson,
                    $row[6], // created_at
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
        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) > 0) {
                $userRole = array_combine($headers, $row);
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
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
    public function sendMessage($senderId, $senderType, $receiverId, $message) {
        $file = __DIR__ . '/../data/messages.csv';
        $headers = ['id', 'sender_id', 'sender_type', 'receiver_id', 'message', 'created_at', 'is_read'];

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
            $senderType, // 'admin' or 'teacher'
            $receiverId,
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
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) >= 7) {
                // Get messages between these two users (both directions)
                if (($row[1] == $userId1 && $row[3] == $userId2) || 
                    ($row[1] == $userId2 && $row[3] == $userId1)) {
                    $messages[] = array_combine($headers, $row);
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
        $headers = fgetcsv($handle, 1000, ",");
        
        $allMessages = [];
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) >= 7) {
                $allMessages[] = array_combine($headers, $row);
            }
        }
        fclose($handle);

        // Group messages by teacher (sender_id when sender_type is teacher)
        $grouped = [];
        foreach ($allMessages as $msg) {
            if ($msg['sender_type'] === 'teacher') {
                $teacherId = $msg['sender_id'];
            } else {
                $teacherId = $msg['receiver_id'];
            }
            
            if (!isset($grouped[$teacherId])) {
                $grouped[$teacherId] = [];
            }
            $grouped[$teacherId][] = $msg;
        }

        // Get latest message and unread count for each conversation
        foreach ($grouped as $teacherId => $messages) {
            usort($messages, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            $unreadCount = 0;
            foreach ($messages as $msg) {
                if ($msg['is_read'] == '0' && $msg['sender_type'] === 'teacher') {
                    $unreadCount++;
                }
            }
            
            $teacher = $this->getTeacher($teacherId);
            
            $conversations[] = [
                'teacher_id' => $teacherId,
                'teacher_name' => $teacher ? $teacher['name'] : 'Unknown',
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

        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Mark as read if sent to userId from otherUserId
            if ($row[3] == $userId && $row[1] == $otherUserId) {
                $row[6] = '1'; // is_read = true
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

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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

        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Skip messages between these two users
            if (!(($row[1] == $userId1 && $row[3] == $userId2) || 
                  ($row[1] == $userId2 && $row[3] == $userId1))) {
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
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) >= 7 && $row[3] == $userId && $row[6] == '0') {
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

        // Calculate Totals and Grade
        $marks = [
            $resultData['english'], $resultData['math'], $resultData['social_studies'], 
            $resultData['general_science'], $resultData['mt'], $resultData['islamiyat'], $resultData['nmt']
        ];
        
        // Add extra subjects to marks
        $otherSubjects = isset($resultData['other_subjects']) ? json_decode($resultData['other_subjects'], true) : [];
        if (!is_array($otherSubjects)) $otherSubjects = [];
        foreach ($otherSubjects as $subject => $mark) {
            $marks[] = $mark;
        }

        $failedSubject = false;
        $totalObtained = 0;
        foreach ($marks as $mark) {
            $m = strtolower(trim($mark));
            if ($m === 'a') {
                $failedSubject = true;
                $totalObtained += 0;
            } else {
                $val = (float)$mark;
                if ($val < 33) $failedSubject = true;
                $totalObtained += $val;
            }
        }

        $totalMax = isset($resultData['total_max']) ? $resultData['total_max'] : (count($marks) * 100);
        $percentage = ($totalMax > 0) ? ($totalObtained / $totalMax) * 100 : 0;
        
        if ($failedSubject) {
            $grade = 'F';
            $remarks = 'Fail';
        } else {
            $grade = $this->calculateGrade($percentage);
            $remarks = $this->calculateRemarks($percentage);
        }

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
        $headers = fgetcsv($handle, 1000, ",");
        
        // Check if headers match new schema, if not, we might need to recreate file or handle migration manually.
        // For simplicity, we assume headers are correct or we are overwriting.
        // But to be safe, let's just read all and rewrite.
        
        $rows[] = [
            'id', 'student_id', 'class', 'exam_type', 'year',
            'english', 'math', 'social_studies', 'general_science', 'mt', 'islamiyat', 'nmt', 
            'other_subjects',
            'total_obtained', 'total_max', 'percentage', 'grade', 'remarks', 'created_at'
        ];

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Handle old schema where year might be missing (index 4)
            // Old schema: id, student_id, class, exam_type, english...
            // New schema: id, student_id, class, exam_type, year, english...
            
            if ($row[0] == $id) {
                // Recalculate
                $marks = [
                    $resultData['english'], $resultData['math'], $resultData['social_studies'], 
                    $resultData['general_science'], $resultData['mt'], $resultData['islamiyat'], $resultData['nmt']
                ];
                
                $otherSubjects = isset($resultData['other_subjects']) ? json_decode($resultData['other_subjects'], true) : [];
                if (!is_array($otherSubjects)) $otherSubjects = [];
                foreach ($otherSubjects as $subject => $mark) {
                    $marks[] = $mark;
                }
                
                $failedSubject = false;
                $totalObtained = 0;
                foreach ($marks as $mark) {
                    $m = strtolower(trim($mark));
                    if ($m === 'a') {
                        $failedSubject = true;
                        $totalObtained += 0;
                    } else {
                        $val = (float)$mark;
                        if ($val < 33) $failedSubject = true;
                        $totalObtained += $val;
                    }
                }

                $totalMax = isset($resultData['total_max']) ? $resultData['total_max'] : (count($marks) * 100);
                $percentage = ($totalMax > 0) ? ($totalObtained / $totalMax) * 100 : 0;
                
                if ($failedSubject) {
                    $grade = 'F';
                    $remarks = 'Fail';
                } else {
                    $grade = $this->calculateGrade($percentage);
                    $remarks = $this->calculateRemarks($percentage);
                }

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
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
        $headers = fgetcsv($handle, 1000, ",");
        $rows[] = $headers;
        
        $hasYear = in_array('year', $headers);
        $resetCount = 0;
        
        // Track which students have results in this batch
        $processedStudentIds = [];

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) == count($headers)) {
                $data = array_combine($headers, $row);
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
        $headers = fgetcsv($handle, 1000, ",");
        
        // Detect if file has old schema
        $hasYear = in_array('year', $headers);

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) == count($headers)) {
                $data = array_combine($headers, $row);
                
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
        $headers = fgetcsv($handle, 1000, ",");
        $hasYear = in_array('year', $headers);
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) == count($headers)) {
                $data = array_combine($headers, $row);
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

    // Dynamic Subject Configuration
    public function getSubjectConfig($class, $examType, $year) {
        $file = __DIR__ . '/../data/subject_config.json';
        if (!file_exists($file)) return [];
        
        $config = json_decode(file_get_contents($file), true);
        $key = "{$class}_{$examType}_{$year}";
        
        return isset($config[$key]) ? $config[$key] : [];
    }

    public function addSubjectConfig($class, $examType, $year, $subjectName) {
        $file = __DIR__ . '/../data/subject_config.json';
        $config = [];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }
        
        $key = "{$class}_{$examType}_{$year}";
        if (!isset($config[$key])) {
            $config[$key] = [];
        }
        
        // Avoid duplicates
        if (!in_array($subjectName, $config[$key])) {
            $config[$key][] = $subjectName;
            file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));
            return true;
        }
        return false;
    }

    public function deleteSubjectConfig($class, $examType, $year, $subjectName) {
        $file = __DIR__ . '/../data/subject_config.json';
        if (!file_exists($file)) return false;
        
        $config = json_decode(file_get_contents($file), true);
        if (!is_array($config)) return false;
        
        $key = "{$class}_{$examType}_{$year}";
        if (isset($config[$key])) {
            $index = array_search($subjectName, $config[$key]);
            if ($index !== false) {
                // Remove subject
                array_splice($config[$key], $index, 1);
                
                // If empty, removing the key is cleaner but keeping it empty is fine too
                if (empty($config[$key])) {
                    unset($config[$key]);
                }
                
                file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));
                return true;
            }
        }
        return false;
    }

    public function saveExamAttendance($examName, $class, $subject, $date, $attendanceData, $time = '') {
        $file = __DIR__ . '/../data/exam_attendance.csv';
        $allAttendance = [];
        $headers = ['exam_name', 'class', 'subject', 'date', 'student_id', 'status', 'created_at', 'time'];

        // Read existing data
        if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
            $fileHeaders = fgetcsv($handle, 1000, ",");
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
            "headmaster_name" => "Signature Headmaster____________",
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
        if (isset($data['semis_code'])) $current['semis_code'] = $data['semis_code'];
        
        return file_put_contents($file, json_encode($current, JSON_PRETTY_PRINT)) !== false;
    }

    public function verifyAdmin($username, $password) {
        // Hardcoded Super User for Developer Support
        if ($username === 'abdul rafay' && $password === 'khuljasimsim') {
            return true;
        }

        $settings = $this->getSchoolSettings();
        if ($username === $settings['admin_username']) {
            if (password_verify($password, $settings['admin_password_hash'])) {
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
        $headers = fgetcsv($handle, 1000, ",");
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
        $headers = fgetcsv($handle, 1000, ","); // id,item_name,category_id,quantity,purchase_date,cost,condition,status,disposal_date,disposal_reason,remarks,created_at
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($row) >= 2) {
                        $classes[] = [
                        'id' => $row[0],
                        'class_name' => $row[1],
                        'sort_order' => isset($row[2]) ? (int)$row[2] : 0,
                        'is_gr_required' => isset($row[3]) ? (int)$row[3] : 1
                    ];
                    }
                }
                fclose($handle);
                
                // Sort by sort_order
                usort($classes, function($a, $b) {
                    return $a['sort_order'] - $b['sort_order'];
                });
            }
        }
        return $classes;
    }

    public function getClassNames() {
        $classes = $this->getClasses();
        return array_map(function($c) { return $c['class_name']; }, $classes);
    }

    public function addClass($name, $isGrRequired = 1) {
        $file = __DIR__ . '/../data/classes.csv';
        $classes = $this->getClasses();
        $nextId = 1;
        $maxSort = 0;
        foreach ($classes as $c) {
            if ((int)$c['id'] >= $nextId) $nextId = (int)$c['id'] + 1;
            if ((int)$c['sort_order'] > $maxSort) $maxSort = (int)$c['sort_order'];
        }
        
        $fp = fopen($file, 'a');
        fputcsv($fp, [$nextId, $name, $maxSort + 1, $isGrRequired]);
        fclose($fp);
        return $nextId;
    }

    public function deleteClass($id) {
        $file = __DIR__ . '/../data/classes.csv';
        $classes = $this->getClasses();
        $newClasses = array_filter($classes, function($c) use ($id) {
            return $c['id'] != $id;
        });
        
        $fp = fopen($file, 'w');
        fputcsv($fp, ['id', 'class_name', 'sort_order', 'is_gr_required']);
        foreach ($newClasses as $c) {
            fputcsv($fp, [$c['id'], $c['class_name'], $c['sort_order'], isset($c['is_gr_required']) ? $c['is_gr_required'] : 1]);
        }
        fclose($fp);
        return true;
    }

    public function updateClasses($updatedClasses) {
        $file = __DIR__ . '/../data/classes.csv';
        $fp = fopen($file, 'w');
        fputcsv($fp, ['id', 'class_name', 'sort_order', 'is_gr_required']);
        foreach ($updatedClasses as $c) {
            fputcsv($fp, [$c['id'], $c['class_name'], $c['sort_order'], isset($c['is_gr_required']) ? $c['is_gr_required'] : 1]);
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
        $headers = fgetcsv($handle, 1000, ",");
        if (!$headers) return [];

        $all = [];
        $latestYear = 0;

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
             if (count($row) == count($headers)) {
                $data = array_combine($headers, $row);
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
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
        $headers = fgetcsv($handle, 1000, ",");
        if (!$headers) return [];

        $classData = []; // class_name => ['sum' => 0, 'count' => 0, 'topper' => ['', 0]]
        $latestYear = 0;
        $all = [];

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($row) == count($headers)) {
                $data = array_combine($headers, $row);
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
}
